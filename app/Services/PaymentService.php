<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\AppUserPayment;
use Illuminate\Database\Eloquent\Collection;

class PaymentService extends Service
{
    public function __construct(protected WebhookService $webhookService) {}

    private function buildPaymentQuery(?int $merchantId, array $filters, string $sortBy, string $sortDir)
    {
        $query = AppUserPayment::with(['appUser', 'invoice.merchant', 'invoice.consumer', 'invoice.invoiceDetails']);

        if ($merchantId) {
            $query->whereHas('invoice', function ($q) use ($merchantId) {
                $q->where('merchant_id', $merchantId);
            });
        }

        // Apply filters
        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('token', 'like', "%{$search}%")
                  // customer_name/email/mobile are what's actually captured
                  // on real web/hosted-checkout payments — appUser is only
                  // ever set for the mobile app's own logged-in users, null
                  // for virtually everything a merchant would ask about.
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_mobile', 'like', "%{$search}%")
                  ->orWhereHas('appUser', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('device_id', 'like', "%{$search}%");
                  })
                  ->orWhereHas('invoice', function ($q) use ($search) {
                      $q->where('uuid', 'like', "%{$search}%");
                  });
            });
        }

        // Apply sorting
        $allowedSorts = ['created_at', 'updated_at', 'status'];
        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'created_at';
        $sortDir = in_array(strtolower($sortDir), ['asc', 'desc']) ? strtolower($sortDir) : 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query;
    }

    public function getAll(?int $merchantId = null, array $filters = [], string $sortBy = 'created_at', string $sortDir = 'desc', int $perPage = 15)
    {
        return $this->buildPaymentQuery($merchantId, $filters, $sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getAllForExport(?int $merchantId = null, array $filters = [], string $sortBy = 'created_at', string $sortDir = 'desc'): Collection
    {
        return $this->buildPaymentQuery($merchantId, $filters, $sortBy, $sortDir)->get();
    }

    public function getById(int $id, ?int $merchantId = null): ?AppUserPayment
    {
        $query = AppUserPayment::where('id', $id);

        if ($merchantId) {
            $query->whereHas('invoice', function ($q) use ($merchantId) {
                $q->where('merchant_id', $merchantId);
            });
        }

        return $query->with(['appUser', 'invoice.merchant', 'invoice.consumer'])->first();
    }

    public function getPaymentStats(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $payments = AppUserPayment::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Create a complete date range
        $dateRange = [];
        $stats = [];

        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dateLabel = now()->subDays($i)->format('M d');
            $dateRange[] = $dateLabel;
            $stats[$date] = 0;
        }

        // Fill in actual data
        foreach ($payments as $payment) {
            $date = $payment->date;
            if (isset($stats[$date])) {
                $stats[$date] = $payment->count;
            }
        }

        return [
            'labels' => $dateRange,
            'data' => array_values($stats),
            'total' => AppUserPayment::count(),
        ];
    }

    public function getTotalAmountAllTime(): float
    {
        $total = AppUserPayment::where('app_user_payments.status', \App\Enums\PaymentStatus::Complete->value)
            ->join('invoices', 'app_user_payments.invoice_id', '=', 'invoices.id')
            ->whereNull('app_user_payments.deleted_at')
            ->whereNull('invoices.deleted_at')
            ->sum('invoices.total_fee');

        return (float) ($total ?? 0);
    }

    public function getTotalAmountCurrentMonth(): float
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $total = AppUserPayment::where('app_user_payments.status', \App\Enums\PaymentStatus::Complete->value)
            ->whereBetween('app_user_payments.created_at', [$startOfMonth, $endOfMonth])
            ->join('invoices', 'app_user_payments.invoice_id', '=', 'invoices.id')
            ->whereNull('app_user_payments.deleted_at')
            ->whereNull('invoices.deleted_at')
            ->sum('invoices.total_fee');

        return (float) ($total ?? 0);
    }

    public function getPaymentStatsForMerchant(int $merchantId, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $payments = AppUserPayment::selectRaw('DATE(app_user_payments.created_at) as date, COUNT(*) as count')
            ->where('app_user_payments.created_at', '>=', $startDate)
            ->where('app_user_payments.status', \App\Enums\PaymentStatus::Complete->value)
            ->join('invoices', 'app_user_payments.invoice_id', '=', 'invoices.id')
            ->where('invoices.merchant_id', $merchantId)
            ->whereNull('app_user_payments.deleted_at')
            ->whereNull('invoices.deleted_at')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Create a complete date range
        $dateRange = [];
        $stats = [];

        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dateLabel = now()->subDays($i)->format('M d');
            $dateRange[] = $dateLabel;
            $stats[$date] = 0;
        }

        // Fill in actual data
        foreach ($payments as $payment) {
            $date = $payment->date;
            if (isset($stats[$date])) {
                $stats[$date] = $payment->count;
            }
        }

        return [
            'labels' => $dateRange,
            'data' => array_values($stats),
            'total' => AppUserPayment::where('status', \App\Enums\PaymentStatus::Complete->value)
                ->whereHas('invoice', function ($q) use ($merchantId) {
                    $q->where('merchant_id', $merchantId);
                })
                ->whereNull('deleted_at')
                ->count(),
        ];
    }

    public function getIncomeStatsForMerchant(int $merchantId, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $income = AppUserPayment::selectRaw('DATE(app_user_payments.created_at) as date, SUM(invoices.total_fee) as total')
            ->where('app_user_payments.created_at', '>=', $startDate)
            ->where('app_user_payments.status', \App\Enums\PaymentStatus::Complete->value)
            ->join('invoices', 'app_user_payments.invoice_id', '=', 'invoices.id')
            ->where('invoices.merchant_id', $merchantId)
            ->whereNull('app_user_payments.deleted_at')
            ->whereNull('invoices.deleted_at')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Create a complete date range
        $dateRange = [];
        $stats = [];

        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dateLabel = now()->subDays($i)->format('M d');
            $dateRange[] = $dateLabel;
            $stats[$date] = 0;
        }

        // Fill in actual data
        foreach ($income as $item) {
            $date = $item->date;
            if (isset($stats[$date])) {
                $stats[$date] = (float) ($item->total ?? 0);
            }
        }

        return [
            'labels' => $dateRange,
            'data' => array_values($stats),
        ];
    }

    public function getTotalIncomeForMerchant(int $merchantId): float
    {
        $total = AppUserPayment::where('app_user_payments.status', \App\Enums\PaymentStatus::Complete->value)
            ->join('invoices', 'app_user_payments.invoice_id', '=', 'invoices.id')
            ->where('invoices.merchant_id', $merchantId)
            ->whereNull('app_user_payments.deleted_at')
            ->whereNull('invoices.deleted_at')
            ->sum('invoices.total_fee');

        return (float) ($total ?? 0);
    }

    public function getTotalIncomeCurrentMonthForMerchant(int $merchantId): float
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $total = AppUserPayment::where('app_user_payments.status', \App\Enums\PaymentStatus::Complete->value)
            ->whereBetween('app_user_payments.created_at', [$startOfMonth, $endOfMonth])
            ->join('invoices', 'app_user_payments.invoice_id', '=', 'invoices.id')
            ->where('invoices.merchant_id', $merchantId)
            ->whereNull('app_user_payments.deleted_at')
            ->whereNull('invoices.deleted_at')
            ->sum('invoices.total_fee');

        return (float) ($total ?? 0);
    }

    /**
     * Handle payment flow success event from SDK
     * Records the success event but keeps status as Initiated until webhook confirms
     */
    public function handleFlowSuccess(string $token, array $payload): ?AppUserPayment
    {
        // Find payment by token (could be internal token or nymcard_token)
        $payment = AppUserPayment::where('token', $token)
            ->orWhere('nymcard_token', $token)
            ->first();

        if (!$payment) {
            return null;
        }

        // Record success event but don't change status - webhook will verify
        $payment->update([
            'flow_success_data' => $payload,
            'flow_success_at' => now(),
            'status' => PaymentStatus::Complete
        ]);

        $payment = $payment->fresh(['invoice.merchant', 'invoice.consumer', 'invoice.invoiceDetails']);

        // Fire webhook to merchant if configured
        $merchant = $payment->invoice?->merchant;
        if ($merchant) {
            $this->webhookService->dispatch($merchant, 'payment.completed', $this->buildWebhookPayload($payment));
        }

        return $payment;
    }

    /**
     * Handle payment flow failure event from SDK
     */
    public function handleFlowFailure(string $token, array $payload): ?AppUserPayment
    {
        // Find payment by token (could be internal token or nymcard_token)
        $payment = AppUserPayment::where('token', $token)
            ->orWhere('nymcard_token', $token)
            ->first();

        if (!$payment) {
            return null;
        }

        // Mark as failed and record failure event
        $payment->update([
            'status' => \App\Enums\PaymentStatus::Failed,
            'flow_failure_data' => $payload,
            'flow_failure_at' => now(),
        ]);

        // Update invoice status to Failed
        if ($payment->invoice) {
            $payment->invoice->update([
                'status' => InvoiceStatus::Failed,
            ]);
        }

        $payment = $payment->fresh(['invoice.merchant', 'invoice.consumer', 'invoice.invoiceDetails']);

        // Fire webhook to merchant if configured
        $merchant = $payment->invoice?->merchant;
        if ($merchant) {
            $this->webhookService->dispatch($merchant, 'payment.failed', $this->buildWebhookPayload($payment));
        }

        return $payment;
    }

    /**
     * Build the data payload sent to merchant webhooks.
     */
    private function buildWebhookPayload(AppUserPayment $payment): array
    {
        $invoice  = $payment->invoice;
        $consumer = $invoice?->consumer;

        return [
            'payment_link_id' => $invoice?->uuid,
            'payment_id'      => $payment->id,
            'amount'          => (float) ($invoice?->total_fee ?? 0),
            'currency'        => 'AED',
            'status'          => $payment->status === PaymentStatus::Complete ? 'paid' : 'failed',
            'description'     => $invoice?->invoiceDetails?->first()?->title,
            'customer'        => $consumer ? [
                'name'   => $consumer->name,
                'email'  => $consumer->email,
                'mobile' => $consumer->mobile_number,
            ] : null,
            'paid_at' => $payment->status === PaymentStatus::Complete
                ? $payment->updated_at?->toIso8601String()
                : null,
        ];
    }

    /**
     * Handle payment flow done event from SDK
     * Just records the event without changing status
     */
    public function handleFlowDone(string $token, array $payload): ?AppUserPayment
    {
        // Find payment by token (could be internal token or nymcard_token)
        $payment = AppUserPayment::where('token', $token)
            ->orWhere('nymcard_token', $token)
            ->first();

        if (!$payment) {
            return null;
        }

        // Just record the done event
        $payment->update([
            'flow_done_data' => $payload,
            'flow_done_at' => now(),
        ]);

        return $payment->fresh();
    }
}

