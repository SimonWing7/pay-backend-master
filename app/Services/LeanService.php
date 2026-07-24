<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LeanService extends Service
{
    protected string $appToken;
    protected string $clientSecret;
    protected string $baseUrl;
    protected string $authUrl;
    protected string $paymentDestinationId;
    protected bool $sandbox;

    public function __construct()
    {
        $this->appToken             = config('lean.app_token');
        $this->clientSecret         = config('lean.client_secret');
        $this->baseUrl              = rtrim(config('lean.base_url'), '/');
        $this->authUrl              = rtrim(config('lean.auth_url'), '/');
        $this->paymentDestinationId = config('lean.payment_destination_id');
        $this->sandbox              = (bool) config('lean.sandbox');
    }

    // -------------------------------------------------------------------------
    // OAuth2 — get a cached access token via client_credentials
    // -------------------------------------------------------------------------

    /**
     * Get an OAuth2 access token from Lean's auth server.
     * Token is cached for `lean.token_cache_ttl` seconds to minimise round-trips.
     */
    public function getAccessToken(): string
    {
        $cacheKey = 'lean_access_token';

        return Cache::remember($cacheKey, config('lean.token_cache_ttl', 3500), function () {
            $response = Http::asForm()->post("{$this->authUrl}/oauth2/token", [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->appToken,
                'client_secret' => $this->clientSecret,
                'scope'         => 'api',
            ]);

            if (!$response->successful()) {
                Log::error('Lean: failed to obtain OAuth2 access token', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                throw new \RuntimeException('Lean authentication failed: ' . $response->body());
            }

            $json = $response->json();

            if (empty($json['access_token'])) {
                throw new \RuntimeException('Lean auth response missing access_token');
            }

            return $json['access_token'];
        });
    }

    // -------------------------------------------------------------------------
    // Payment Intents
    // -------------------------------------------------------------------------

    /**
     * Create a Lean payment intent for an invoice.
     *
     * Returns array with:
     *   'success' => bool
     *   'payment_intent_id' => string  (on success)
     *   'data' => array                (full response, on success)
     *   'error' => string              (on failure)
     */
    public function createPaymentIntent(Invoice $invoice): array
    {
        try {
            $accessToken = $this->getAccessToken();
        } catch (\RuntimeException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        // Lean amounts are in the smallest currency unit (fils for AED: 1 AED = 100 fils)
        $amountInFils = (int) round($invoice->total_fee * 100);

        // Use per-merchant destination if set, otherwise fall back to global config
        $destinationId = $invoice->merchant->lean_destination_id ?? $this->paymentDestinationId;

        if (empty($destinationId)) {
            Log::error('Lean: no payment_destination_id configured', [
                'invoice_id' => $invoice->id,
                'merchant_id' => $invoice->merchant_id,
            ]);
            return ['success' => false, 'error' => 'No Lean payment destination configured for this merchant.'];
        }

        $payload = [
            'amount'                 => $amountInFils,
            'currency'               => 'AED',
            'payment_destination_id' => $destinationId,
            'description'            => 'Payment link #' . ($invoice->reference ?: $invoice->id) . ' — ' . $invoice->merchant->name,
            'merchant_order_id'      => 'INV-' . $invoice->id,
        ];

        Log::info('Lean: creating payment intent', [
            'invoice_id'  => $invoice->id,
            'amount_fils' => $amountInFils,
        ]);

        try {
            $response = Http::withToken($accessToken)
                ->post("{$this->baseUrl}/payments/v1/intents", $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Lean: payment intent created', [
                    'invoice_id'        => $invoice->id,
                    'payment_intent_id' => $data['payment_intent_id'] ?? null,
                ]);
                return [
                    'success'           => true,
                    'payment_intent_id' => $data['payment_intent_id'] ?? null,
                    'data'              => $data,
                ];
            }

            Log::error('Lean: payment intent creation failed', [
                'invoice_id' => $invoice->id,
                'status'     => $response->status(),
                'body'       => $response->body(),
            ]);

            return [
                'success' => false,
                'error'   => 'Lean returned ' . $response->status() . ': ' . $response->body(),
            ];

        } catch (\Exception $e) {
            Log::error('Lean: exception during payment intent creation', [
                'invoice_id' => $invoice->id,
                'message'    => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // Webhook signature verification
    // -------------------------------------------------------------------------

    /**
     * Verify a Lean webhook HMAC-SHA256 signature.
     *
     * Lean signs the raw request body with the webhook secret using HMAC-SHA256.
     * The signature is sent in the 'lean-signature' header.
     *
     * Expected header format: "t=<timestamp>,v1=<hex_hmac>"
     * Or simply: "<hex_hmac>"
     *
     * @param  string  $rawBody   The raw (undecoded) request body
     * @param  string  $signature The value of the 'lean-signature' header
     * @return bool
     */
    public function verifyWebhookSignature(string $rawBody, string $signature): bool
    {
        $secret = config('lean.webhook_secret');

        if (empty($secret)) {
            // If no secret configured, skip verification (development only)
            Log::warning('Lean webhook: no webhook secret configured — skipping signature check');
            return true;
        }

        // Parse "t=1234567890,v1=abc..." format (Stripe-style)
        if (str_contains($signature, 'v1=')) {
            $parts = [];
            foreach (explode(',', $signature) as $part) {
                [$key, $val] = explode('=', $part, 2);
                $parts[$key] = $val;
            }
            $hmacHex = $parts['v1'] ?? '';
        } else {
            // Plain hex HMAC
            $hmacHex = $signature;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, strtolower($hmacHex));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * The app_token (client_id) used in the frontend Lean.pay() call.
     */
    public function getAppToken(): string
    {
        return $this->appToken;
    }

    /**
     * Whether to run in sandbox mode (used in frontend Lean.pay() call).
     */
    public function isSandbox(): bool
    {
        return $this->sandbox;
    }
}
