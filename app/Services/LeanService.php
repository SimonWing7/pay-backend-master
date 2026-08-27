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
    // Banks
    // -------------------------------------------------------------------------

    /**
     * Fetch the current list of Open Finance (true Open Banking) banks from
     * Lean. REVERSE_ENGINEERED banks are deliberately excluded — that's a
     * different, older Lean product (credential-based bank connections),
     * not what our AlTareq/Open Finance checkout actually uses, even though
     * they show payments:true generically. Showing them here would list
     * banks as "live" that don't actually work in our real checkout flow.
     */
    public function fetchAvailableBanks(): array
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->timeout(15)
            ->get("{$this->baseUrl}/banks/v1", [
                'connection_types' => 'OPEN_BANKING',
            ]);

        if (!$response->successful()) {
            Log::error('Lean: failed to fetch banks list', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('Lean: could not fetch banks list: ' . $response->body());
        }

        $banks = $response->json('data') ?? $response->json() ?? [];

        return array_values(array_filter(array_map(function (array $bank) {
            $availability = $bank['availability'] ?? [];
            $isAvailable  = (bool) ($availability['active']['payments'] ?? false)
                && (bool) ($availability['enabled']['payments'] ?? false);

            return [
                'identifier'      => $bank['identifier'] ?? null,
                'name'            => $bank['name'] ?? null,
                'logo_url'        => $bank['logo'] ?? null,
                'connection_type' => $bank['connection_type'] ?? null,
                'is_available'    => $isAvailable,
            ];
        }, $banks), fn ($bank) => !empty($bank['identifier']) && !empty($bank['name'])));
    }

    // -------------------------------------------------------------------------
    // Customers
    // -------------------------------------------------------------------------

    public function createOrGetCustomer(string $appUserId): string
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->post("{$this->baseUrl}/customers/v1", [
                'app_user_id' => $appUserId,
            ]);

        $data = $response->json();

        if (!empty($data['customer_id'])) {
            return $data['customer_id'];
        }

        if ($response->status() === 409) {
            $fetch   = Http::withToken($accessToken)
                ->get("{$this->baseUrl}/customers/v1", ['app_user_id' => $appUserId]);
            $fetched = $fetch->json();

            if (!empty($fetched['data'][0]['customer_id'])) {
                return $fetched['data'][0]['customer_id'];
            }
        }

        Log::error('Lean: failed to create/get customer', [
            'app_user_id' => $appUserId,
            'status'      => $response->status(),
            'body'        => $response->body(),
        ]);

        throw new \RuntimeException('Lean: could not create or retrieve customer.');
    }

    // -------------------------------------------------------------------------
    // Payment Intents
    // -------------------------------------------------------------------------

    public function createPaymentIntent(Invoice $invoice, string $customerEmail = ''): array
    {
        try {
            $accessToken = $this->getAccessToken();
        } catch (\RuntimeException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        try {
            $appUserId      = $customerEmail ?: 'guest-inv-' . $invoice->id;
            $leanCustomerId = $this->createOrGetCustomer($appUserId);
        } catch (\RuntimeException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        $amount = round($invoice->total_fee, 2);

        // An invoice tagged with a specific entity (a merchant with multiple
        // trade licenses/companies, e.g. separate Dubai/Abu Dhabi entities)
        // routes to that entity's own destination first. Falls through to
        // the merchant's single destination for everyone else, unchanged.
        $destinationId = $invoice->merchantEntity?->lean_destination_id
            ?? $invoice->merchant->lean_destination_id
            ?? $this->paymentDestinationId;

        if (empty($destinationId)) {
            Log::error('Lean: no payment_destination_id configured', [
                'invoice_id'  => $invoice->id,
                'merchant_id' => $invoice->merchant_id,
            ]);
            return ['success' => false, 'error' => 'No Lean payment destination configured for this merchant.'];
        }

        $payload = [
            'amount'                 => $amount,
            'currency'               => 'AED',
            'payment_destination_id' => $destinationId,
            'customer_id'            => $leanCustomerId,
            'purpose_code'            => 'GDS',
            'description'            => substr('Pay ' . ($invoice->reference ?: $invoice->id) . ' ' . $invoice->merchant->name, 0, 32),
        ];

        Log::info('Lean: creating payment intent', [
            'invoice_id'  => $invoice->id,
            'amount_aed'  => $amount,
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
                    'lean_customer_id'  => $leanCustomerId,
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
    // Customer-scoped tokens (required by LinkSDK Lean.pay() and captureRedirect())
    // -------------------------------------------------------------------------

    public function getCustomerToken(string $leanCustomerId): string
    {
        $response = Http::asForm()->post("{$this->authUrl}/oauth2/token", [
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->appToken,
            'client_secret' => $this->clientSecret,
            'scope'         => 'customer.' . $leanCustomerId,
        ]);

        if (!$response->successful()) {
            Log::error('Lean: failed to obtain customer-scoped token', [
                'lean_customer_id' => $leanCustomerId,
                'status'           => $response->status(),
                'body'             => $response->body(),
            ]);
            throw new \RuntimeException('Lean: could not generate customer token: ' . $response->body());
        }

        $json = $response->json();

        if (empty($json['access_token'])) {
            throw new \RuntimeException('Lean: customer token response missing access_token');
        }

        return $json['access_token'];
    }

    // -------------------------------------------------------------------------
    // Webhook signature verification
    // -------------------------------------------------------------------------

    public function verifyWebhookSignature(string $rawBody, string $signature): bool
    {
        $secret = config('lean.webhook_secret');

        if (empty($secret)) {
            Log::warning('Lean webhook: no webhook secret configured — skipping signature check');
            return true;
        }

        if (str_contains($signature, 'v1=')) {
            $parts = [];
            foreach (explode(',', $signature) as $part) {
                [$key, $val] = explode('=', $part, 2);
                $parts[$key] = $val;
            }
            $hmacHex = $parts['v1'] ?? '';
        } else {
            $hmacHex = preg_replace('/^sha[0-9]+=/', '', $signature);
        }

        $expected = hash_hmac('sha512', $rawBody, $secret);

        return hash_equals($expected, strtolower($hmacHex));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function getAppToken(): string
    {
        return $this->appToken;
    }

    public function isSandbox(): bool
    {
        return $this->sandbox;
    }
}
