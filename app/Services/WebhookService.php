<?php

namespace App\Services;

use App\Models\Merchant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    private const TIMEOUT_SECONDS = 10;

    /**
     * Dispatch a signed webhook to a merchant's configured webhook URL.
     *
     * The payload is signed with HMAC-SHA256 using the merchant's webhook_secret.
     * The signature is sent in the X-Edfundo-Signature header as: sha256=<hex>
     *
     * Failures are logged but never thrown — webhook delivery must never affect
     * the payment flow for the customer.
     */
    public function dispatch(Merchant $merchant, string $event, array $data): void
    {
        if (empty($merchant->webhook_url) || empty($merchant->webhook_secret)) {
            return;
        }

        $payload = json_encode([
            'event'      => $event,
            'created_at' => now()->toIso8601String(),
            'data'       => $data,
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, $merchant->webhook_secret);

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withHeaders([
                    'Content-Type'         => 'application/json',
                    'X-Edfundo-Signature'  => $signature,
                    'User-Agent'           => 'EdfundoPay-Webhook/1.0',
                ])
                // Force IPv4 so merchants can reliably IP-whitelist this server
                // (it's dual-stack and would otherwise default to IPv6 outbound).
                ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                ->send('POST', $merchant->webhook_url, ['body' => $payload]);

            if (!$response->successful()) {
                Log::warning('Webhook delivery failed', [
                    'merchant_id'  => $merchant->id,
                    'event'        => $event,
                    'webhook_url'  => $merchant->webhook_url,
                    'http_status'  => $response->status(),
                    'response'     => substr($response->body(), 0, 500),
                ]);
            } else {
                Log::info('Webhook delivered', [
                    'merchant_id' => $merchant->id,
                    'event'       => $event,
                    'http_status' => $response->status(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Webhook delivery exception', [
                'merchant_id' => $merchant->id,
                'event'       => $event,
                'webhook_url' => $merchant->webhook_url,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate a new random webhook secret (32 bytes = 64 hex chars).
     */
    public static function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }
}
