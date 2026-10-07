<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\AppUserPayment;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireStaleInvoices extends Command
{
    protected $signature = 'invoices:expire-stale';

    protected $description = 'Mark long-abandoned Draft invoices as Failed and stuck Initiated payments as Abandoned';

    public function handle(): int
    {
        $hours = (int) config('invoices.expiry_hours', 24);
        $cutoff = now()->subHours($hours);

        // "Open" links are deliberately excluded — they're designed to be
        // reused indefinitely (shared group/product links), not a one-shot
        // payment attempt, so they should never auto-expire.
        // A payment Lean last reported as PENDING_WITH_BANK is not abandoned
        // — the bank accepted the initiation but hadn't confirmed yet, and
        // the money may still land (or already have) with the final
        // ACCEPTED_BY_BANK update arriving later. Expiring those as
        // failed/abandoned showed genuinely paid invoices as Failed, so
        // they're left alone until Lean says something final.
        $invoiceCount = Invoice::where('status', InvoiceStatus::Draft)
            ->where(function ($q) {
                $q->whereNull('link_type')->orWhere('link_type', 'personal');
            })
            ->where('created_at', '<', $cutoff)
            ->whereDoesntHave('appUserPayments', function ($q) {
                $q->where('lean_metadata->latest_iso_status', 'PENDING_WITH_BANK');
            })
            ->update(['status' => InvoiceStatus::Failed]);

        // Payments that started (customer reached Lean's bank-connection
        // step) but never resolved — no webhook ever confirmed success or
        // failure, most commonly because the customer abandoned mid-flow.
        // Marked Abandoned rather than Failed so a real bank failure rate
        // isn't inflated by people who simply left.
        $paymentCount = AppUserPayment::where('status', PaymentStatus::Initiated)
            ->where('created_at', '<', $cutoff)
            ->where(function ($q) {
                $q->whereNull('lean_metadata->latest_iso_status')
                  ->orWhere('lean_metadata->latest_iso_status', '!=', 'PENDING_WITH_BANK');
            })
            ->update(['status' => PaymentStatus::Abandoned]);

        Log::info('invoices:expire-stale completed', [
            'expiry_hours'    => $hours,
            'invoices_failed' => $invoiceCount,
            'payments_failed' => $paymentCount,
        ]);

        $this->info("Expired {$invoiceCount} stale invoice(s) and {$paymentCount} stuck payment(s).");
        return self::SUCCESS;
    }
}
