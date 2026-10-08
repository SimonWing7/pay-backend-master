<?php
namespace App\Http\Controllers;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\AppUserPayment;
use App\Services\LeanService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class LeanWebhookController extends Controller
{
    /**
     * Handle incoming Lean webhook events.
     *
     * Registered URL: https://staging-pay.edfundo.com/webhook/lean
     *
     * Lean sends HMAC-SHA256 signed webhooks whenever a payment status changes.
     * We only mark a payment as complete when iso_status = ACCEPTED_SETTLEMENT_COMPLETED.
     *
     * Common iso_status values:
     *   PENDING                        - Payment intent created, not yet processed
     *   ACCEPTED_TECHNICAL_VALIDATION  - Bank accepted the request technically
     *   ACCEPTED_CUSTOMER_PROFILE      - Customer profile validated
     *   ACCEPTED_SETTLEMENT_IN_PROCESS - Settlement in progress
     *   ACCEPTED_SETTLEMENT_COMPLETED  - Money has moved - treat as paid
     *   REJECTED                       - Payment rejected by bank
     *   CANCELLED                      - Customer cancelled
     */
    public function handle(Request $request, LeanService $lean, PaymentService $paymentService): JsonResponse
    {
        // -- Signature verification ----------------------------------------
        $rawBody   = $request->getContent();
        $signature = $request->header('lean-signature') ?? $request->header('x-lean-signature') ?? '';

        if (!$lean->verifyWebhookSignature($rawBody, $signature)) {
            // error, not warning: a mismatched webhook secret silently
            // breaks every payment confirmation, not just this one request —
            // worth an immediate alert rather than something only found by
            // scrolling through logs after customers start complaining.
            Log::error('Lean webhook: invalid signature', [
                'signature' => $signature,
                'ip'        => $request->ip(),
            ]);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        // -- Parse payload -------------------------------------------------
        $payload         = $request->all();
        $innerPayload    = $payload['payload'] ?? [];
        $paymentIntentId = $innerPayload['intent_id'] ?? $innerPayload['payment_intent_id'] ?? $payload['payment_intent_id'] ?? null;
        $isoStatus       = $innerPayload['status'] ?? $payload['iso_status'] ?? $payload['status'] ?? null;

        Log::info('Lean webhook received', [
            'payment_intent_id' => $paymentIntentId,
            'iso_status'        => $isoStatus,
            'type'              => $payload['type'] ?? 'unknown',
        ]);

        // -- A bank was added or its availability changed — not a payment
        //    event, refresh the cached bank list immediately instead of
        //    waiting for the daily sync
        if (in_array($payload['type'] ?? null, ['bank.availability.new', 'bank.availability.updated'])) {
            \Illuminate\Support\Facades\Artisan::call('lean:sync-banks');
            return response()->json(['message' => 'Bank list resynced'], 200);
        }

        if (!$paymentIntentId) {
            Log::warning('Lean webhook: missing payment_intent_id', ['payload' => $payload]);
            return response()->json(['message' => 'Missing payment_intent_id'], 400);
        }

        // -- Look up the payment record ------------------------------------
        $payment = AppUserPayment::where('lean_payment_intent_id', $paymentIntentId)
            ->with('invoice.merchant')
            ->first();

        if (!$payment) {
            Log::warning('Lean webhook: payment not found', [
                'payment_intent_id' => $paymentIntentId,
            ]);
            // Return 200 so Lean does not keep retrying for unknown payments
            return response()->json(['message' => 'Payment not found'], 200);
        }

        // -- Merge lean_metadata with the new payload ----------------------
        $existingMeta = $payment->lean_metadata ?? [];
        $updatedMeta  = array_merge($existingMeta, [
            'latest_webhook'    => $payload,
            'latest_iso_status' => $isoStatus,
            'latest_update_at'  => now()->toIso8601String(),
        ]);

        // -- Update payment and invoice based on iso_status ----------------
        switch (true) {
            case in_array($isoStatus, ['ACCEPTED_BY_BANK', 'ACCEPTED_SETTLEMENT_COMPLETED']):
                // The only statuses we treat as a confirmed successful payment
                if ($payment->status !== PaymentStatus::Complete) {
                    $paymentService->confirmLeanPayment($payment, $updatedMeta);
                }
                break;

            case in_array($isoStatus, ['FAILED', 'REJECTED', 'CANCELLED']):
                // A late or out-of-order failure event must never downgrade a
                // payment already confirmed as paid.
                if ($payment->status === PaymentStatus::Complete) {
                    Log::warning('Lean webhook: ignoring failure event for an already-complete payment', [
                        'payment_intent_id' => $paymentIntentId,
                        'iso_status'        => $isoStatus,
                    ]);
                    break;
                }

                // Payment definitively failed / customer cancelled
                $payment->update([
                    'status'        => PaymentStatus::Failed,
                    'lean_metadata' => $updatedMeta,
                ]);
                if ($payment->invoice && $payment->invoice->status === InvoiceStatus::Draft && ($payment->invoice->link_type ?? 'personal') !== 'open') {
                    $payment->invoice->update([
                        'status' => InvoiceStatus::Failed,
                    ]);
                }
                break;

            default:
                // Intermediate status -- update metadata only
                $payment->update([
                    'lean_metadata' => $updatedMeta,
                ]);
                break;
        }

        return response()->json(['message' => 'Webhook processed'], 200);
    }
}
