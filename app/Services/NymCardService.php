<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\AppUser;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NymCardService extends Service
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $environment;
    protected bool $mockMode;

    public function __construct()
    {
        $this->apiKey = config('nymcard.api_key');
        $this->environment = config('nymcard.environment', 'sandbox');
        $this->baseUrl = config('nymcard.base_url');
        $this->mockMode = config('nymcard.mock', false);
    }

    /**
     * Initiate a payment with NymCard.
     * Returns array with 'success' bool and either 'data' or 'error'.
     * In mock mode, returns a simulated successful response.
     */
    public function initiatePayment(array $paymentData): array
    {
        if ($this->mockMode) {
            Log::info('NymCard mock mode: simulating payment initiation', $paymentData);

            return [
                'success' => true,
                'data' => [
                    'token' => 'mock-sdk-token-' . uniqid(),
                    'resourceId' => 'mock-resource-' . uniqid(),
                    'userId' => 'mock-user-' . uniqid(),
                ],
            ];
        }

        try {
            $response = Http::withHeaders([
                'apiKey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/v1/payments/initiate", $paymentData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::error('NymCard payment initiation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $response->json() ?? ['message' => 'Payment initiation failed'],
            ];
        } catch (\Exception $e) {
            Log::error('NymCard API exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => ['message' => $e->getMessage()],
            ];
        }
    }

    /**
     * Get payment details from NymCard
     */
    public function getPayment(string $paymentId): array
    {
        if ($this->mockMode) {
            return [
                'success' => true,
                'data' => [
                    'resourceId' => $paymentId,
                    'status' => 'completed',
                ],
            ];
        }

        try {
            $response = Http::withHeaders([
                'apiKey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/v1/payments/{$paymentId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json() ?? ['message' => 'Failed to get payment'],
            ];
        } catch (\Exception $e) {
            Log::error('NymCard get payment exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => ['message' => $e->getMessage()],
            ];
        }
    }

    /**
     * Build payment request data for a web-based (hosted page) payment.
     * No AppUser required — consumer is identified via the invoice.
     * Uses the Embedded Web Flow (Direct Client) which returns an sdkToken.
     *
     * PaymentReference format required by NymCard: ^[A-Z0-9]{3}-[A-Z]{4}-TL.+-[0-9]{4}$
     * Example: EDF-PAYM-TL000042-2026
     */
    public function buildWebPaymentRequest(Invoice $invoice): array
    {
        $merchant = $invoice->merchant;
        $consumer = $invoice->consumer;

        if (App::environment('production') && empty($merchant->iban)) {
            throw new \Exception('Merchant IBAN is required for NymCard payments. Please set IBAN in merchant settings.');
        }

        $year = now()->format('Y');
        $paymentReference = sprintf('EDF-PAYM-TL%06d-%s', $invoice->id, $year);

        if (App::environment('production')) {
            return [
                'creditor' => [
                    'name' => $merchant->name,
                    'identification' => $merchant->iban,
                    'schemeName' => 'IBAN',
                    'tradingName' => $merchant->merchant_trading_name,
                    'accountType' => 'Retail',
                ],
                'debtor' => [
                    'name' => $consumer?->name ?? 'Customer',
                ],
                'paymentPurposeCode' => 'ACM',
                'paymentReference' => $paymentReference,
                'paymentType' => 'p2p',
                'consent' => [
                    'consentScheduleType' => 'SingleInstantPayment',
                    'amount' => number_format($invoice->total_fee, 2, '.', ''),
                    'currency' => 'AED',
                ],
            ];
        }

        // Sandbox — use hardcoded test creditor as required by NymCard sandbox
        return [
            'creditor' => [
                'name' => 'Mario International',
                'identification' => '10000109010101',
                'schemeName' => 'BICFI',
                'tradingName' => 'Mario International',
                'accountType' => 'Retail',
            ],
            'debtor' => [
                'name' => $consumer?->name ?? 'Customer',
            ],
            'paymentPurposeCode' => 'ACM',
            'paymentReference' => $paymentReference,
            'paymentType' => 'p2p',
            'consent' => [
                'consentScheduleType' => 'SingleInstantPayment',
                'amount' => '1.00',
                'currency' => 'AED',
                'permissions' => [
                    'ReadAccountsBasic',
                    'ReadAccountsDetail',
                    'ReadBalances',
                    'ReadRefundAccount',
                ],
            ],
        ];
    }

    /**
     * Build payment request data from invoice for mobile app (legacy).
     */
    public function buildPaymentRequest(Invoice $invoice, AppUser $appUser): array
    {
        $merchant = $invoice->merchant;
        $consumer = $invoice->consumer;

        if (empty($merchant->iban)) {
            throw new \Exception('Merchant IBAN is required for NymCard payments. Please set IBAN in merchant settings.');
        }

        if (App::environment('production')) {
            return [
                'creditor' => [
                    'name' => $merchant->name,
                    'identification' => $merchant->iban,
                    'schemeName' => 'IBAN',
                    'tradingName' => $merchant->merchant_trading_name,
                    'accountType' => 'Retail',
                ],
                'debtor' => [
                    'userId' => $appUser->uuid,
                    'name' => $appUser->name ?? 'User ' . $appUser->id,
                ],
                'paymentPurposeCode' => 'ACM',
                'paymentReference' => "Invoice {$invoice->id}",
                'paymentType' => 'p2p',
                'consent' => [
                    'consentScheduleType' => 'SingleInstantPayment',
                    'amount' => number_format($invoice->total_fee, 2, '.', ''),
                    'currency' => 'AED',
                ],
            ];
        }

        return [
            'creditor' => [
                'name' => 'Mario International',
                'identification' => '10000109010101',
                'schemeName' => 'BICFI',
                'tradingName' => 'Mario International',
                'accountType' => 'Retail',
            ],
            'debtor' => [
                'userId' => '1c5ec6ad-d16a-4fd7-b742-8e2da2f51e4a',
            ],
            'paymentPurposeCode' => 'ACM',
            'paymentType' => 'p2p',
            'consent' => [
                'consentScheduleType' => 'SingleInstantPayment',
                'amount' => '1.00',
                'currency' => 'AED',
                'permissions' => [
                    'ReadAccountsBasic',
                    'ReadAccountsDetail',
                    'ReadBalances',
                    'ReadRefundAccount',
                ],
            ],
        ];
    }
}
