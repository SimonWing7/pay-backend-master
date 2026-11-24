<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    private const TIMEOUT_SECONDS = 10;

    /**
     * Seconds to wait before each retry: 1 min, 5 min, 15 min, 1 h, 3 h.
     * With the first attempt that is six tries over roughly four hours — long
     * enough to ride out a merchant's outage or a firewall rule being fixed.
     */
    public const RETRY_DELAYS = [60, 300, 900, 3600, 10800];

    /**
     * Send a signed webhook to a merchant's configured webhook URL.
     *
     * The payload is signed with HMAC-SHA256 using the merchant's webhook_secret.
     * The signature is sent in the X-Edfundo-Signature header as: sha256=<hex>
     *
     * Every notification is recorded in webhook_deliveries. If the first
     * attempt fails it is retried in the background (`webhooks:retry`).
     * Failures are logged but never thrown — webhook delivery must never
     * affect the payment flow for the customer.
     */
    public function dispatch(Merchant $merchant, string $event, array $data): ?WebhookDelivery
    {
        if (empty($merchant->webhook_url) || empty($merchant->webhook_secret)) {
            return null;
        }

        $delivery = WebhookDelivery::create([
            'merchant_id' => $merchant->id,
            'event'       => $event,
            'reference'   => isset($data['reference']) ? (string) $data['reference'] : null,
            'payload'     => json_encode([
                'event'      => $event,
                'created_at' => now()->toIso8601String(),
                'data'       => $data,
            ]),
            'status'      => WebhookDelivery::PENDING,
        ]);

        $this->attempt($delivery, $merchant);

        return $delivery;
    }

    /**
     * Make one delivery attempt and record the outcome. On failure the next
     * attempt is scheduled, or the delivery is marked failed once the retries
     * are used up. Returns true when the merchant accepted it.
     */
    public function attempt(WebhookDelivery $delivery, ?Merchant $merchant = null): bool
    {
        $merchant ??= $delivery->merchant;

        if (!$merchant || empty($merchant->webhook_url) || empty($merchant->webhook_secret)) {
            $delivery->update([
                'status'          => WebhookDelivery::FAILED,
                'next_attempt_at' => null,
                'last_error'      => 'Merchant has no webhook URL or secret configured.',
            ]);
            return false;
        }

        // Signed with the merchant's *current* secret, so a retry still works
        // after the secret has been rotated.
        $signature = 'sha256=' . hash_hmac('sha256', $delivery->payload, $merchant->webhook_secret);
        $attempt   = $delivery->attempts + 1;
        $status    = null;
        $error     = null;

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withHeaders([
                    'Content-Type'         => 'application/json',
                    'X-Edfundo-Signature'  => $signature,
                    'X-Edfundo-Delivery'   => (string) $delivery->id,
                    'X-Edfundo-Attempt'    => (string) $attempt,
                    'User-Agent'           => 'EdfundoPay-Webhook/1.0',
                ])
                // Force IPv4 so merchants can reliably IP-whitelist this server
                // (it's dual-stack and would otherwise default to IPv6 outbound).
                ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                ->send('POST', $merchant->webhook_url, ['body' => $delivery->payload]);

            $status = $response->status();

            if ($response->successful()) {
                $delivery->update([
                    'status'           => WebhookDelivery::DELIVERED,
                    'attempts'         => $attempt,
                    'next_attempt_at'  => null,
                    'last_http_status' => $status,
                    'last_error'       => null,
                    'delivered_at'     => now(),
                ]);

                Log::info('Webhook delivered', [
                    'merchant_id' => $merchant->id,
                    'event'       => $delivery->event,
                    'reference'   => $delivery->reference,
                    'http_status' => $status,
                    'attempt'     => $attempt,
                ]);

                return true;
            }

            $error = substr($response->body(), 0, 500);
            Log::warning('Webhook delivery failed', [
                'merchant_id'  => $merchant->id,
                'event'        => $delivery->event,
                'reference'    => $delivery->reference,
                'webhook_url'  => $merchant->webhook_url,
                'http_status'  => $status,
                'attempt'      => $attempt,
                'response'     => $error,
            ]);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            Log::error('Webhook delivery exception', [
                'merchant_id' => $merchant->id,
                'event'       => $delivery->event,
                'reference'   => $delivery->reference,
                'webhook_url' => $merchant->webhook_url,
                'attempt'     => $attempt,
                'error'       => $error,
            ]);
        }

        $delay = self::RETRY_DELAYS[$attempt - 1] ?? null;

        $delivery->update([
            'attempts'         => $attempt,
            'status'           => $delay === null ? WebhookDelivery::FAILED : WebhookDelivery::PENDING,
            'next_attempt_at'  => $delay === null ? null : now()->addSeconds($delay),
            'last_http_status' => $status,
            'last_error'       => $error,
        ]);

        if ($delay === null) {
            Log::error('Webhook gave up after all retries', [
                'merchant_id' => $merchant->id,
                'event'       => $delivery->event,
                'reference'   => $delivery->reference,
                'delivery_id' => $delivery->id,
            ]);
        }

        return false;
    }

    /**
     * Retry every delivery whose next attempt is due. Returns how many were
     * attempted and how many of those got through.
     *
     * @return array{attempted:int, delivered:int, superseded:int}
     */
    public function retryDue(int $limit = 100): array
    {
        $due = WebhookDelivery::where('status', WebhookDelivery::PENDING)
            ->whereNotNull('next_attempt_at')
            ->where('next_attempt_at', '<=', now())
            ->orderBy('next_attempt_at')
            ->limit($limit)
            ->get();

        $result = ['attempted' => 0, 'delivered' => 0, 'superseded' => 0];

        foreach ($due as $delivery) {
            if ($this->isSuperseded($delivery)) {
                $delivery->update(['status' => WebhookDelivery::SUPERSEDED, 'next_attempt_at' => null]);
                $result['superseded']++;
                continue;
            }

            $result['attempted']++;
            if ($this->attempt($delivery)) {
                $result['delivered']++;
            }
        }

        return $result;
    }

    /**
     * A queued "payment failed" is pointless — and could wrongly cancel an
     * order — if the customer has since paid. Skip it in that case.
     */
    private function isSuperseded(WebhookDelivery $delivery): bool
    {
        if ($delivery->event !== 'payment.failed' || $delivery->reference === null) {
            return false;
        }

        return Invoice::where('merchant_id', $delivery->merchant_id)
            ->where('reference', $delivery->reference)
            ->where('status', InvoiceStatus::Paid)
            ->exists();
    }

    /**
     * Generate a new random webhook secret (32 bytes = 64 hex chars).
     */
    public static function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }
}
