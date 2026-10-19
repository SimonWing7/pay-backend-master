<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The people who need to pay for one product ("payers"): adding them from a
 * CSV or one at a time, queueing their payment links for sending, and the
 * summary / filtered list shown on the product page. A payer is an invoice for
 * the product (one invoice per family when siblings are combined).
 */
class PayerService
{
    public function __construct(
        protected ConsumerImportService $consumerImport,
        protected InvoiceService $invoiceService,
    ) {
    }

    /**
     * Add the people in a CSV to a product: save/update them as contacts and
     * create their invoices for this product, all in one transaction so a
     * failed upload leaves nothing half-done. Nothing is emailed.
     *
     * @return array{contacts_created: int, contacts_updated: int, skipped: int, skipped_rows: list<array{row: int, reason: string}>, invoices: int, children: int, already: int}
     * @throws \InvalidArgumentException when the file is too large
     */
    public function importCsv(Product $product, string $csv, bool $combine = true): array
    {
        return DB::transaction(function () use ($product, $csv, $combine) {
            $import = $this->consumerImport->import($csv, $product->merchant_id);
            $added = $this->invoiceService->addPayersToProduct($product, $import['consumer_ids'], $combine);

            return [
                'contacts_created' => $import['created'],
                'contacts_updated' => $import['updated'],
                'skipped'          => $import['skipped'],
                'skipped_rows'     => $import['skipped_rows'],
                'invoices'         => $added['invoices']->count(),
                'children'         => $added['children'],
                'already'          => $added['already'],
            ];
        });
    }

    /**
     * Add one person by hand, through the same path as a CSV row.
     *
     * @param array{name: string, student_name?: ?string, email?: ?string, mobile?: ?string} $payer
     */
    public function addOne(Product $product, array $payer): array
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['Parent Name', 'Student/Player Name', 'Parent Email', 'Parent Mobile']);
        fputcsv($out, [$payer['name'], $payer['student_name'] ?? '', $payer['email'] ?? '', $payer['mobile'] ?? '']);
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $this->importCsv($product, $csv, false);
    }

    /** Invoices for this product that still count (archived ones are removed from the list). */
    public function payersQuery(Product $product): Builder
    {
        return Invoice::query()
            ->where('merchant_id', $product->merchant_id)
            ->where('status', '!=', InvoiceStatus::Archived->value)
            ->whereHas('invoiceDetails', fn ($q) => $q->where('product_id', $product->id));
    }

    /**
     * @param array{status?: ?string, link?: ?string, q?: ?string} $filters
     */
    public function paginate(Product $product, array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $query = $this->payersQuery($product)->with(['consumer', 'invoiceDetails.consumer']);

        match ($filters['status'] ?? null) {
            'paid'   => $query->where('status', InvoiceStatus::Paid->value),
            'unpaid' => $query->whereIn('status', [InvoiceStatus::Draft->value, InvoiceStatus::Failed->value]),
            default  => null,
        };

        match ($filters['link'] ?? null) {
            'not_sent' => $query->where('link_email_status', 'not_sent'),
            'queued'   => $query->whereIn('link_email_status', ['queued', 'sending']),
            'sent'     => $query->where(fn ($q) => $q->where('link_email_status', 'sent')->orWhereNull('link_email_status')),
            'failed'   => $query->where('link_email_status', 'failed'),
            'no_email' => $query->whereHas('consumer', fn ($q) => $q->whereNull('email')->orWhere('email', '')),
            default    => null,
        };

        if (!empty($filters['q'])) {
            $term = '%' . trim($filters['q']) . '%';
            $like = fn ($q) => $q->where('name', 'like', $term)
                ->orWhere('student_name', 'like', $term)
                ->orWhere('email', 'like', $term)
                ->orWhere('mobile_number', 'like', $term);

            $query->where(fn ($q) => $q
                ->whereHas('consumer', $like)
                ->orWhereHas('invoiceDetails.consumer', $like));
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    /**
     * Headline numbers for the product page, from aggregate queries so it
     * stays fast with thousands of payers.
     *
     * @return array<string, int|float>
     */
    public function summary(Product $product): array
    {
        $base = fn () => $this->payersQuery($product);

        $invoices = $base()->count();
        $paid = $base()->where('status', InvoiceStatus::Paid->value)->count();
        $linkCounts = $base()->selectRaw('link_email_status, COUNT(*) as n')->groupBy('link_email_status')->pluck('n', 'link_email_status');

        return [
            'invoices'    => $invoices,
            'children'    => (int) $product->invoiceDetails()
                ->whereHas('invoice', fn ($q) => $q->where('status', '!=', InvoiceStatus::Archived->value))->count(),
            'paid'        => $paid,
            'unpaid'      => $invoices - $paid,
            'expected'    => round((float) $base()->sum('total_fee'), 2),
            'collected'   => round((float) $base()->where('status', InvoiceStatus::Paid->value)->sum('total_fee'), 2),
            'not_sent'    => (int) ($linkCounts['not_sent'] ?? 0),
            'queued'      => (int) (($linkCounts['queued'] ?? 0) + ($linkCounts['sending'] ?? 0)),
            // NULL (invoices from before this tracking existed) counts as already sent.
            // PHP turns a NULL array key into '', so that's the one to read.
            'sent'        => (int) (($linkCounts['sent'] ?? 0) + ($linkCounts[''] ?? 0)),
            'failed'      => (int) ($linkCounts['failed'] ?? 0),
            'sendable'    => $this->sendableQuery($product, ['not_sent', 'failed'])->count(),
            'retryable'   => $this->sendableQuery($product, ['failed'])->count(),
            'no_email'    => $base()->where('status', InvoiceStatus::Draft->value)
                ->whereHas('consumer', fn ($q) => $q->whereNull('email')->orWhere('email', ''))->count(),
        ];
    }

    /** Unpaid payers with an email address whose link is in one of these states. */
    private function sendableQuery(Product $product, array $linkStatuses): Builder
    {
        return $this->payersQuery($product)
            ->where('status', InvoiceStatus::Draft->value)
            ->whereIn('link_email_status', $linkStatuses)
            ->whereHas('consumer', fn ($q) => $q->whereNotNull('email')->where('email', '!=', ''));
    }

    /**
     * Hand everyone who hasn't been emailed (or whose email failed) to the
     * background sender. Returns instantly; payers:send-links does the sending.
     */
    public function queueLinks(Product $product, array $linkStatuses = ['not_sent', 'failed']): int
    {
        return $this->sendableQuery($product, $linkStatuses)->update([
            'link_email_status' => 'queued',
            'link_email_error'  => null,
        ]);
    }

    /** Take a payer off the list (unpaid only) without deleting any history. */
    public function remove(Product $product, int $invoiceId): bool
    {
        $invoice = $this->payersQuery($product)->whereKey($invoiceId)
            ->whereIn('status', [InvoiceStatus::Draft->value, InvoiceStatus::Failed->value])
            ->first();

        if (!$invoice) {
            return false;
        }

        $invoice->update(['status' => InvoiceStatus::Archived]);

        return true;
    }
}
