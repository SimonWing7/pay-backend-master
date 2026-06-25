<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUserPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NymCardWebhookController extends Controller
{
    /**
     * Handle NymCard webhook events
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        
        Log::info('NymCard webhook received', [
            'event' => $payload['event'] ?? 'unknown',
            'resourceId' => $payload['resourceId'] ?? null,
        ]);

        $event = $payload['event'] ?? null;
        $resourceId = $payload['resourceId'] ?? null;
        $status = $payload['status'] ?? null;
        $metadata = $payload['metadata'] ?? [];

        if (!$resourceId) {
            Log::warning('NymCard webhook missing resourceId', ['payload' => $payload]);
            return response()->json(['message' => 'Missing resourceId'], 400);
        }

        // Find payment by NymCard resource ID
        $payment = AppUserPayment::where('nymcard_resource_id', $resourceId)->first();

        if (!$payment) {
            Log::warning('NymCard webhook: Payment not found', [
                'resourceId' => $resourceId,
                'event' => $event,
            ]);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // Update payment based on event type
        switch ($event) {
            case 'payment.flow.initiated':
                // Payment flow started - already in Initiated status
                $payment->update([
                    'nymcard_metadata' => array_merge($payment->nymcard_metadata ?? [], $metadata),
                ]);
                break;

            case 'payment.flow.completed':
                // Payment completed successfully
                $payment->update([
                    'status' => \App\Enums\PaymentStatus::Complete,
                    'nymcard_metadata' => array_merge($payment->nymcard_metadata ?? [], $metadata),
                ]);
                
                // Update invoice status to Paid
                if ($payment->invoice) {
                    $payment->invoice->update([
                        'status' => \App\Enums\InvoiceStatus::Paid,
                    ]);
                }
                break;

            case 'payment.flow.failed':
                // Payment failed
                $payment->update([
                    'status' => \App\Enums\PaymentStatus::Failed,
                    'nymcard_metadata' => array_merge($payment->nymcard_metadata ?? [], [
                        'failure_reason' => $metadata['reason'] ?? 'Payment failed',
                        'failed_at' => $payload['timestamp'] ?? now()->toIso8601String(),
                    ]),
                ]);
                
                // Update invoice status to Failed
                if ($payment->invoice) {
                    $payment->invoice->update([
                        'status' => \App\Enums\InvoiceStatus::Failed,
                    ]);
                }
                break;

            case 'consent.granted':
            case 'consent.revoked':
            case 'consent.expired':
                // Update metadata with consent information
                $payment->update([
                    'nymcard_metadata' => array_merge($payment->nymcard_metadata ?? [], [
                        'consent_event' => $event,
                        'consent_timestamp' => $payload['timestamp'] ?? now()->toIso8601String(),
                    ]),
                ]);
                break;

            case 'system.error':
                Log::error('NymCard system error', [
                    'resourceId' => $resourceId,
                    'metadata' => $metadata,
                ]);
                break;

            default:
                Log::info('NymCard webhook: Unhandled event', [
                    'event' => $event,
                    'resourceId' => $resourceId,
                ]);
        }

        return response()->json(['message' => 'Webhook processed'], 200);
    }
}

