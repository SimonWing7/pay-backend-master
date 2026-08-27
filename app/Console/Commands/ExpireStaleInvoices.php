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

    protected $description = 'Mark long-abandoned Draft invoices and stuck Initiated payments as Failed';

    public function handle(): int
    {
        $hours = (int) config('invoices.expiry_hours', 24);
        $cutoff = now()->subHours($hours);

        // "Open" links are deliberately excluded — they're designed to be
        // reused indefinitely (shared group/product links), not a one-shot
        // payment attempt, so they should never auto-expire.
        $invoiceCount = Invoice::where('status', InvoiceStatus::Draft)
            ->where(function ($q) {
                $q->whereNull('link_type')->orWhere('link_type', 'personal');
            })
            ->where('created_at', '<', $cutoff)
            ->update(['status' => InvoiceStatus::Failed]);

        // Payments that started (customer reached Lean's bank-connection
        // step) but never resolved — no webhook ever confirmed success or
        // failure, most commonly because the customer abandoned mid-flow.
        $paymentCount = AppUserPayment::where('status', PaymentStatus::Initiated)
            ->where('created_at', '<', $cutoff)
            ->update(['status' => PaymentStatus::Failed]);

        Log::info('invoices:expire-stale completed', [
            'expiry_hours'    => $hours,
            'invoices_failed' => $invoiceCount,
            'payments_failed' => $paymentCount,
        ]);

        $this->info("Expired {$invoiceCount} stale invoice(s) and {$paymentCount} stuck payment(s).");
        return self::SUCCESS;
    }
}
