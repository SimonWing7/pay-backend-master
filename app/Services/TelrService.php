<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Telr payment gateway client, used to bill merchants their monthly fees.
 *
 * Flow: the merchant registers a card once on Telr's hosted page (3-D Secure),
 * which gives an authorised ECom sale whose transaction reference we keep
 * (encrypted). Each month the Remote API charges that reference with a
 * continuous-authority ("cont") sale. No card number ever touches our servers.
 *
 * Like LeanService, nothing here throws — failures come back as data and are
 * logged (never including the auth keys).
 */
class TelrService
{
    public const OUTCOME_AUTHORISED = 'authorised';
    public const OUTCOME_DECLINED = 'declined';
    public const OUTCOME_ERROR = 'error';
    // Telr may or may not have processed the request (timeout, odd response,
    // payment held for review). Callers must NOT retry these automatically —
    // a human checks the Telr dashboard first — or a merchant could be
    // charged twice.
    public const OUTCOME_UNKNOWN = 'unknown';

    public function isConfigured(): bool
    {
        return config('telr.store_id') !== '' && config('telr.auth_key') !== '' && config('telr.order_auth_key') !== '';
    }

    /**
     * Create a hosted payment page for the AED verification sale that
     * registers a card.
     *
     * @param array{authorised: string, declined: string, cancelled: string} $returnUrls
     * @return array{success: bool, order_ref?: string, url?: string, error?: string}
     */
    public function createCardSetupOrder(string $cartId, float $amount, array $returnUrls): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Telr is not configured.'];
        }

        try {
            $response = $this->client()->asJson()->acceptJson()->post(config('telr.order_url'), [
                'method'  => 'create',
                'store'   => (int) config('telr.store_id'),
                'authkey' => config('telr.order_auth_key'),
                'framed'  => 0,
                // Card entry only. Wallets such as Samsung Pay / Apple Pay pay
                // with a device-bound token (different last four digits and
                // expiry), which can't be relied on for later monthly charges.
                'panels'  => 'card',
                'order'   => [
                    'cartid'      => $cartId,
                    'test'        => config('telr.test_mode') ? '1' : '0',
                    'amount'      => $this->money($amount),
                    'currency'    => config('billing.currency', 'AED'),
                    'description' => 'Edfundo Pay card registration',
                ],
                'return' => $returnUrls,
            ]);
        } catch (\Throwable $e) {
            Log::error('Telr: card setup order request failed', ['cartid' => $cartId, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Could not reach the payment provider. Please try again.'];
        }

        $orderRef = $response->json('order.ref');
        $url = $response->json('order.url') ?? $response->json('url');

        if (!$response->successful() || !$orderRef || !$url) {
            Log::error('Telr: card setup order was rejected', [
                'cartid'  => $cartId,
                'status'  => $response->status(),
                'message' => $response->json('error.message'),
                'note'    => $response->json('error.note'),
                // Contains Telr's exception ID — look it up in the Telr
                // merchant admin to see exactly why the request was refused.
                'details' => $response->json('error.details'),
            ]);
            return ['success' => false, 'error' => 'The payment provider could not start the card registration. Please try again.'];
        }

        return ['success' => true, 'order_ref' => $orderRef, 'url' => $url];
    }

    /**
     * Ask Telr what happened to a hosted-page order.
     *
     * @return array{success: bool, paid?: bool, status_text?: string, tran_ref?: string, card?: array, error?: string}
     */
    public function checkOrder(string $orderRef): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Telr is not configured.'];
        }

        try {
            $response = $this->client()->asJson()->acceptJson()->post(config('telr.order_url'), [
                'method'  => 'check',
                'store'   => (int) config('telr.store_id'),
                'authkey' => config('telr.order_auth_key'),
                'order'   => ['ref' => $orderRef],
            ]);
        } catch (\Throwable $e) {
            Log::error('Telr: order check request failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Could not reach the payment provider.'];
        }

        $order = $response->json('order');

        if (!$response->successful() || !is_array($order)) {
            Log::error('Telr: order check was rejected', [
                'status'  => $response->status(),
                'message' => $response->json('error.message'),
                'details' => $response->json('error.details'),
            ]);
            return ['success' => false, 'error' => 'The payment provider could not confirm this card registration.'];
        }

        // status.code 3 = Paid; the sale itself must also be authorised ("A").
        $paid = (int) ($order['status']['code'] ?? 0) === 3
            && ($order['transaction']['status'] ?? null) === 'A'
            && !empty($order['transaction']['ref']);

        return [
            'success'     => true,
            'paid'        => $paid,
            'status_text' => (string) ($order['status']['text'] ?? 'Unknown'),
            'tran_ref'    => $order['transaction']['ref'] ?? null,
            'card'        => [
                'type'         => $order['card']['type'] ?? null,
                'last4'        => $order['card']['last4'] ?? null,
                'expiry_month' => $order['card']['expiry']['month'] ?? null,
                'expiry_year'  => $order['card']['expiry']['year'] ?? null,
            ],
        ];
    }

    /**
     * Charge a saved card with a continuous-authority sale.
     *
     * @return array{outcome: string, tran_ref: ?string, code: ?string, message: ?string}
     */
    public function chargeContinuous(string $tranRef, float $amount, string $cartId, string $description): array
    {
        return $this->remote('sale', 'cont', $tranRef, $amount, $cartId, $description);
    }

    /**
     * Refund (part of) an earlier ECom sale, e.g. the card-registration charge.
     *
     * @return array{outcome: string, tran_ref: ?string, code: ?string, message: ?string}
     */
    public function refund(string $tranRef, float $amount, string $cartId, string $description = 'Edfundo Pay card registration refund'): array
    {
        return $this->remote('refund', 'ecom', $tranRef, $amount, $cartId, $description);
    }

    private function remote(string $type, string $class, string $tranRef, float $amount, string $cartId, string $description): array
    {
        if (!$this->isConfigured()) {
            return $this->result(self::OUTCOME_ERROR, null, null, 'Telr is not configured.');
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<remote>'
            . '<store>' . $this->xml(config('telr.store_id')) . '</store>'
            . '<key>' . $this->xml(config('telr.auth_key')) . '</key>'
            . '<tran>'
            . '<type>' . $type . '</type>'
            . '<class>' . $class . '</class>'
            . '<cartid>' . $this->xml(Str::limit($cartId, 60, '')) . '</cartid>'
            . '<description>' . $this->xml(Str::limit($description, 60, '')) . '</description>'
            . '<test>' . (config('telr.test_mode') ? '1' : '0') . '</test>'
            . '<currency>' . $this->xml(config('billing.currency', 'AED')) . '</currency>'
            . '<amount>' . $this->money($amount) . '</amount>'
            . '<ref>' . $this->xml($tranRef) . '</ref>'
            . '</tran>'
            . '</remote>';

        try {
            $response = $this->client()
                ->withBody($xml, 'application/xml')
                ->post(config('telr.remote_url'));
        } catch (\Throwable $e) {
            // Could have timed out *after* Telr processed it — don't guess.
            Log::error('Telr: remote request failed — outcome unknown', ['type' => $type, 'cartid' => $cartId, 'error' => $e->getMessage()]);
            return $this->result(self::OUTCOME_UNKNOWN, null, null, 'No response from the payment provider.');
        }

        if (!$response->successful()) {
            Log::error('Telr: remote request returned an HTTP error — outcome unknown', ['type' => $type, 'cartid' => $cartId, 'status' => $response->status()]);
            return $this->result(self::OUTCOME_UNKNOWN, null, null, 'Unexpected response from the payment provider (HTTP ' . $response->status() . ').');
        }

        return $this->parseRemoteResponse($response->body(), $type, $cartId);
    }

    /** @return array{outcome: string, tran_ref: ?string, code: ?string, message: ?string} */
    public function parseRemoteResponse(string $body, string $type = 'sale', string $cartId = ''): array
    {
        $previous = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($body);
        libxml_use_internal_errors($previous);

        if ($doc === false) {
            Log::error('Telr: unreadable remote response — outcome unknown', ['type' => $type, 'cartid' => $cartId]);
            return $this->result(self::OUTCOME_UNKNOWN, null, null, 'Unreadable response from the payment provider.');
        }

        // Request-level problem (bad key, IP not allowed, bad reference…):
        // Telr reports it as <Response><Error>…</Error></Response> and
        // nothing was charged.
        if (isset($doc->Error) || isset($doc->error)) {
            $message = trim((string) ($doc->Error ?? $doc->error)) . ' ' . trim((string) ($doc->Details ?? $doc->details ?? ''));
            Log::error('Telr: remote request rejected', ['type' => $type, 'cartid' => $cartId, 'message' => trim($message)]);
            return $this->result(self::OUTCOME_ERROR, null, null, trim($message));
        }

        $auth = $doc->auth ?? null;
        if ($auth === null || !isset($auth->status)) {
            Log::error('Telr: remote response had no auth block — outcome unknown', ['type' => $type, 'cartid' => $cartId]);
            return $this->result(self::OUTCOME_UNKNOWN, null, null, 'Unrecognised response from the payment provider.');
        }

        $status = strtoupper(trim((string) $auth->status));
        $tranRef = trim((string) ($auth->tranref ?? '')) ?: null;
        $code = trim((string) ($auth->code ?? '')) ?: null;
        $message = trim((string) ($auth->message ?? '')) ?: null;

        $outcome = match ($status) {
            'A'     => self::OUTCOME_AUTHORISED,
            'D'     => self::OUTCOME_DECLINED,
            'E'     => self::OUTCOME_ERROR,
            // H = held for review, or anything we don't recognise: the money
            // may or may not have moved, so a human has to look.
            default => self::OUTCOME_UNKNOWN,
        };

        Log::info('Telr: remote response', ['type' => $type, 'cartid' => $cartId, 'outcome' => $outcome, 'status' => $status, 'tranref' => $tranRef, 'code' => $code]);

        return $this->result($outcome, $tranRef, $code, $message);
    }

    /**
     * Telr only accepts live Remote API calls from whitelisted IPv4 addresses,
     * and this server is dual-stack — without forcing IPv4 an outgoing request
     * could leave from an IPv6 address that isn't whitelisted. (Same approach
     * as WebhookService.)
     */
    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout((int) config('telr.timeout'))
            ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]]);
    }

    private function result(string $outcome, ?string $tranRef, ?string $code, ?string $message): array
    {
        return ['outcome' => $outcome, 'tran_ref' => $tranRef, 'code' => $code, 'message' => $message];
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    private function xml(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
