<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\AppUserPayment;
use App\Services\LeanService;
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
     *   PENDING                        — Payment intent created, not yet processed
     *   ACCEPTED_TECHNICAL_VALIDATION  — Bank accepted the request technically
     *   ACCEPTED_CUSTOMER_PROFILE      — Customer profile validated
     *   ACCEPTED_SETTLEMENT_IN_PROCESS — Settlement in progress
     *   ACCEPTED_SETTLEMENT_COMPLETED  — ✅ Money has moved — treat as paid
     *   REJECTED                       — Payment rejected by bank
     *   CANCELLED                      — Customer cancelled
     */
    public function handle(Request $request, LeanService $lean): JsonResponse
    {
        // ── Signature verification ────────────────────────────────────────────
        $rawBody   = $request->getContent();
        $signature = $request->header('lean-signature') ?? $request->header('x-lean-signature') ?? '';

        if (!$lean->verifyWebhookSignature($rawBody, $signature)) {
            Log::warning('Lean webhook: invalid signature', [
                'signature' => $signature,
                'ip'        => $request->ip(),
            ]);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        // ── Parse payload ─────────────────────────────────────────────────────
        $payload         = $request->all();
        $paymentIntentId = $payload['payment_intent_id'] ?? null;
        $isoStatus       = $payload['iso_status'] ?? $payload['status'] ?? null;

        Log::info('Lean webhook received', [
            'payment_intent_id' => $paymentIntentId,
            'iso_status'        => $isoStatus,
            'type'              => $payload['type'] ?? 'unknown',
        ]);

        if (!$paymentIntentId) {
            Log::warning('Lean webhook: missing payment_intent_id', ['payload' => $payload]);
            return response()->json(['message' => 'Missing payment_intent_id'], 400);
        }

        // ── Look up the payment record ────────────────────────────────────────
        $payment = AppUserPayment::where('lean_payment_intent_id', $paymentIntentId)
            ->with('invoice')
            ->first();

        if (!$payment) {
            Log::warning('Lean webhook: payment not found', [
                'payment_intent_id' => $paymentIntentId,
            ]);
            // Return 200 so Lean doesn't keep retrying for unknown payments
            return response()->json(['message' => 'Payment not found'], 200);
        }

        // ── Merge lean_metadata with the new payload ──────────────────────────
        $existingMeta = $payment->lean_metadata ?? [];
        $updatedMeta  = array_merge($existingMeta, [
            'latest_webhook' => $payload,
            'latest_iso_status' => $isoStatus,
            'latest_update_at' => now()->toIso8601String(),
        ]);

        // ── Update payment and invoice based on iso_status ────────────────────
        switch (true) {

            case $isoStatus === 'ACCEPTED_SETTLEMENT_COMPLETED':
                // ✅ The only status we treat as a confirmed successful payment
                if ($payment->status !== PaymentStatus::Complete) {
                    $payment->update([
                        'status'        => PaymentStatus::Complete,
                        'lean_metadata' => $updatedMeta,
                    ]);

                    if ($payment->invoice && $payment->invoice->status !== InvoiceStatus::Paid) {
                        $payment->invoice->update([
                            'status' => InvoiceStatus::Paid,
                        ]);

                        Log::info('Lean webhook: invoice marked as paid', [
                            'invoice_id'        => $payment->invoice->id,
                            'payment_intent_id' => $paymentIntentId,
                        ]);
                    }
                }
                break;

            case in_array($isoStatus, ['REJECTED', 'CANCELLED']):
                // Payment definitively failed / customer cancelled
                $payment->update([
                    'status'        => PaymentStatus::Failed,
                    'lean_metadata' => $updatedMeta,
                ]);

                if ($payment->invoice && $payment->invoice->status === InvoiceStatus::Draft) {
                    $payment->invoice->update([
                        'status' => InvoiceStatus::Failed,
                    ]);
                }
                break;

            default:
                // Intermediate status — update metadata only, don't change payment status
                $payment->update([
                    'lean_metadata' => $updatedMeta,
                ]);
                break;
        }

        return response()->json(['message' => 'Webhook processed'], 200);
    }
}
