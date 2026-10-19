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

        if (!empty($filters['entity_id'])) {
            $query->where('merchant_entity_id', $filters['entity_id']);
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
            'merchant_entity_id' => $data['merchant_entity_id'] ?? null,
            'total_fee' => $data['total_fee'],
            'status' => InvoiceStatus::Draft,
            'reference' => $data['reference'] ?? null,
            'link_type' => $data['link_type'] ?? 'personal',
            'custom_fields' => $data['custom_fields'] ?? null,
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

    /**
     * Invoice a list of individuals for one product — the payers of that
     * product. With $combine, individuals who share a contact (email or
     * mobile) are one family and get ONE invoice with a line per child, so
     * the parent receives one email and makes one payment.
     *
     * Safe to repeat: anyone who already has a live (unpaid or paid) invoice
     * line for this product is skipped, so re-uploading a file or adding the
     * same person twice never double-bills them. New invoices start as
     * "not sent" — emailing is a separate, deliberate step.
     *
     * @param list<int> $consumerIds in the order they should appear
     * @return array{invoices: SupportCollection, children: int, already: int}
     */
    public function addPayersToProduct(Product $product, array $consumerIds, bool $combine = true): array
    {
        $order = array_flip($consumerIds);

        $consumers = Consumer::where('merchant_id', $product->merchant_id)
            ->whereIn('id', $consumerIds)
            ->get()
            ->sortBy(fn (Consumer $c) => $order[$c->id] ?? PHP_INT_MAX)
            ->values();

        $alreadyInvoiced = $this->consumerIdsAlreadyInvoicedFor($product);
        $fresh = $consumers->reject(fn (Consumer $c) => $alreadyInvoiced->contains($c->id))->values();

        $groups = $combine
            ? $this->groupIntoFamilies($fresh)
            : $fresh->map(fn (Consumer $c) => collect([$c]));

        $invoices = collect();

        foreach ($groups as $members) {
            // The invoice (and its email) goes to a family member we can
            // actually email, if there is one.
            $primary = $members->first(fn (Consumer $c) => !empty($c->email)) ?? $members->first();

            $invoice = Invoice::create([
                'consumer_id' => $primary->id,
                'merchant_id' => $product->merchant_id,
                'total_fee' => round($product->fee * $members->count(), 2),
                'status' => InvoiceStatus::Draft,
                // Not matched by ExpireStaleInvoices' personal/null check, so
                // these stay open for as long as reminders keep running
                // instead of auto-expiring after invoices.expiry_hours.
                'link_type' => 'batch',
                'link_email_status' => 'not_sent',
            ]);

            foreach ($members as $member) {
                InvoiceDetail::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'consumer_id' => $member->id,
                    'fee' => $product->fee,
                    'title' => $this->lineTitle($product->name, $member, $members->count()),
                ]);
            }

            $invoices->push($invoice->load(['consumer', 'merchant', 'invoiceDetails.product', 'invoiceDetails.consumer']));
        }

        return [
            'invoices' => $invoices,
            'children' => $fresh->count(),
            'already'  => $consumers->count() - $fresh->count(),
        ];
    }

    /**
     * Everyone who already has a live invoice line for this product — via a
     * line's own consumer, or (for older invoices without one) the invoice's
     * consumer. Archived and failed invoices don't count, so those people can
     * be invoiced again.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function consumerIdsAlreadyInvoicedFor(Product $product): SupportCollection
    {
        $live = [InvoiceStatus::Draft->value, InvoiceStatus::Paid->value];

        $fromLines = InvoiceDetail::where('product_id', $product->id)
            ->whereNotNull('consumer_id')
            ->whereHas('invoice', fn ($q) => $q->whereIn('status', $live))
            ->pluck('consumer_id');

        $fromInvoices = Invoice::whereIn('status', $live)
            ->whereNotNull('consumer_id')
            ->whereHas('invoiceDetails', fn ($q) => $q->where('product_id', $product->id))
            ->pluck('consumer_id');

        return $fromLines->merge($fromInvoices)->unique()->values();
    }

    /** "Term 1 Fees — Henry Daniels", so it's clear which child a line is for. */
    private function lineTitle(string $productName, Consumer $member, int $familySize): string
    {
        if (!empty($member->student_name)) {
            return "{$productName} — {$member->student_name}";
        }

        return $familySize > 1 ? "{$productName} — {$member->name}" : $productName;
    }

    /**
     * Group individuals into families: anyone sharing an email or a mobile
     * number (matched on the last 9 digits) with someone else in the list ends
     * up in the same group, directly or through another sibling. People with
     * no contact details are on their own. Order follows the first appearance.
     *
     * @param \Illuminate\Support\Collection<int, Consumer> $consumers
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, Consumer>>
     */
    private function groupIntoFamilies($consumers): SupportCollection
    {
        $root = [];
        foreach ($consumers as $c) {
            $root[$c->id] = $c->id;
        }

        $find = function (int $id) use (&$root) {
            while ($root[$id] !== $id) {
                $root[$id] = $root[$root[$id]];
                $id = $root[$id];
            }

            return $id;
        };

        $seen = [];
        foreach ($consumers as $c) {
            $keys = [];
            if (!empty($c->email)) {
                $keys[] = 'e:' . strtolower(trim($c->email));
            }
            $digits = preg_replace('/\D+/', '', (string) $c->mobile_number);
            if (strlen($digits) >= 9) {
                $keys[] = 'm:' . substr($digits, -9);
            }

            foreach ($keys as $key) {
                if (isset($seen[$key])) {
                    $a = $find($c->id);
                    $b = $find($seen[$key]);
                    if ($a !== $b) {
                        $root[max($a, $b)] = min($a, $b);
                    }
                } else {
                    $seen[$key] = $c->id;
                }
            }
        }

        return $consumers
            ->groupBy(fn (Consumer $c) => $find($c->id))
            ->values()
            ->map(fn ($group) => $group->values());
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

