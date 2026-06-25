<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Consumer;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Log;

class InvoiceService extends Service
{
    public function getAllByMerchant(int $merchantId, array $filters = [], string $sortBy = 'created_at', string $sortDir = 'desc', int $perPage = 15)
    {
        $query = Invoice::where('merchant_id', $merchantId)
            ->with(['consumer', 'invoiceDetails.product', 'groups']);

        // Apply filters
        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== null) {
            // Show only the selected status (including Archived if explicitly chosen)
            $query->where('status', $filters['status']);
        } else {
            // By default exclude archived links
            $query->where('status', '!=', InvoiceStatus::Archived->value);
        }

        if (isset($filters['consumer_id'])) {
            $query->where('consumer_id', $filters['consumer_id']);
        }

        if (isset($filters['group_id'])) {
            $query->whereHas('groups', function ($q) use ($filters) {
                $q->where('groups.id', $filters['group_id']);
            });
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                  ->orWhereHas('consumer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Apply sorting
        $allowedSorts = ['created_at', 'updated_at', 'total_fee', 'status'];
        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'created_at';
        $sortDir = in_array(strtolower($sortDir), ['asc', 'desc']) ? strtolower($sortDir) : 'desc';
        $query->orderBy($sortBy, $sortDir);

        // Apply pagination
        return $query->paginate($perPage)->withQueryString();
    }

    public function getAllByAppUser(int $appUserId): Collection
    {
        return Invoice::whereHas('appUserPayments', function ($query) use ($appUserId) {
            $query->where('app_user_id', $appUserId);
        })
            ->with(['consumer', 'merchant', 'invoiceDetails.product'])
            ->get();
    }

    public function getById(int $id, ?int $merchantId = null): ?Invoice
    {
        $query = Invoice::where('id', $id);

        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }

        return $query->with(['consumer', 'merchant', 'invoiceDetails.product'])->first();
    }

    public function create(array $data): Invoice
    {
        $groupIds = $data['group_ids'] ?? [];
        unset($data['group_ids']);

        $invoice = Invoice::create([
            'consumer_id' => $data['consumer_id'] ?? null,
            'merchant_id' => $data['merchant_id'],
            'total_fee' => $data['total_fee'],
            'status' => InvoiceStatus::Draft,
        ]);

        if (isset($data['invoice_details']) && is_array($data['invoice_details'])) {
            foreach ($data['invoice_details'] as $detail) {
                InvoiceDetail::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $detail['product_id'] ?: null,
                    'fee' => $detail['fee'],
                    'title' => $detail['title'] ?? '',
                ]);
            }
        }

        if (!empty($groupIds)) {
            $invoice->groups()->sync($groupIds);
        }

        return $invoice->load(['consumer', 'merchant', 'invoiceDetails.product', 'groups']);
    }

    public function createForGroup(array $data): SupportCollection
    {
        $invoices = collect();
        $group = \App\Models\Group::where('id', $data['group_id'])
            ->where('merchant_id', $data['merchant_id'])
            ->first();

        if (!$group) {
            throw new \Exception('Group not found or does not belong to this merchant');
        }

        // Get all consumers in the group for this merchant
        $consumers = $group->consumers()
            ->where('merchant_id', $data['merchant_id'])
            ->get();

        if ($consumers->isEmpty()) {
            throw new \Exception('No consumers found in this group');
        }

        // Create one invoice for each consumer in the group
        foreach ($consumers as $consumer) {
            $invoice = Invoice::create([
                'consumer_id' => $consumer->id,
                'merchant_id' => $data['merchant_id'],
                'total_fee' => $data['total_fee'],
                'status' => InvoiceStatus::Draft,
            ]);

            // Add invoice details for each invoice
            if (isset($data['invoice_details']) && is_array($data['invoice_details'])) {
                foreach ($data['invoice_details'] as $detail) {
                    InvoiceDetail::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $detail['product_id'],
                        'fee' => $detail['fee'],
                        'title' => $detail['title'] ?? '',
                    ]);
                }
            }

            // Attach the group to this invoice
            $invoice->groups()->attach($group->id);

            // Also attach additional groups if provided
            if (isset($data['group_ids']) && is_array($data['group_ids'])) {
                $invoice->groups()->syncWithoutDetaching($data['group_ids']);
            }

            $invoices->push($invoice->load(['consumer', 'invoiceDetails.product', 'groups']));
        }

        return $invoices;
    }

    public function createBulk(array $data): SupportCollection
    {
        $invoices = collect();

        $product = Product::find($data['product_id']);
        if (!$product) {
            throw new \Exception('Product not found');
        }

        foreach ($data['consumer_ids'] as $consumerId) {
            $consumer = Consumer::find($consumerId);
            if (!$consumer) {
                continue;
            }

            $invoice = Invoice::create([
                'consumer_id' => $consumerId,
                'merchant_id' => $data['merchant_id'],
                'total_fee' => $product->fee,
                'status' => InvoiceStatus::Draft,
            ]);

            InvoiceDetail::create([
                'invoice_id' => $invoice->id,
                'product_id' => $product->id,
                'fee' => $product->fee,
                'title' => $product->name,
            ]);

            $invoices->push($invoice->load(['consumer', 'invoiceDetails.product']));
        }

        return $invoices;
    }

    public function initiate(int $invoiceId, int $appUserId): ?\App\Models\AppUserPayment
    {
        $invoice = Invoice::with('merchant')->find($invoiceId);
        if (!$invoice) {
            return null;
        }

        if (!$invoice->merchant || !$invoice->merchant->is_active) {
            return null;
        }

        $appUser = \App\Models\AppUser::find($appUserId);
        if (!$appUser) {
            return null;
        }

        // Initialize NymCard service
        $nymCardService = new NymCardService();

        // Build payment request
        $paymentRequest = $nymCardService->buildPaymentRequest($invoice, $appUser);

        Log::info('Nymcard Payment Initiate Payload: ' . json_encode($paymentRequest));
        // Call NymCard API
        $nymCardResponse = $nymCardService->initiatePayment($paymentRequest);

        Log::info('Nymcard Response: ' . json_encode($nymCardResponse));
        if (!$nymCardResponse['success']) {
            \Log::error('NymCard payment initiation failed', [
                'invoice_id' => $invoiceId,
                'error' => $nymCardResponse['error'] ?? 'Unknown error',
            ]);
            return null;
        }

        $nymCardData = $nymCardResponse['data'];
        $nymCardToken = $nymCardData['token'] ?? null;

        // Create payment record
        $payment = \App\Models\AppUserPayment::create([
            'app_user_id' => $appUserId,
            'invoice_id' => $invoiceId,
            'token' => bin2hex(random_bytes(32)), // Internal token
            'nymcard_token' => $nymCardToken, // NymCard SDK token
            'nymcard_resource_id' => $nymCardData['resourceId'] ?? null,
            'nymcard_metadata' => $nymCardData,
            'status' => \App\Enums\PaymentStatus::Initiated,
        ]);

        return $payment;
    }

    public function initiateByUuid(string $invoiceUuid, int $appUserId): ?\App\Models\AppUserPayment
    {
        $invoice = Invoice::where('uuid', $invoiceUuid)->first();
        if (!$invoice) {
            return null;
        }

        return $this->initiate($invoice->id, $appUserId);
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        $invoice->update($data);
        return $invoice->fresh()->load(['consumer', 'merchant', 'invoiceDetails.product']);
    }

    public function delete(Invoice $invoice): bool
    {
        return $invoice->delete();
    }
}

