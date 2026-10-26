<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\AppUserPayment;
use App\Models\Invoice;
use App\Services\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireStaleInvoices extends Command
{
    protected $signature = 'invoices:expire-stale';

    protected $description = 'Mark long-abandoned Draft invoices as Failed and stuck Initiated payments as Abandoned';

    public function handle(PaymentService $paymentService): int
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
        $invoices = Invoice::where('status', InvoiceStatus::Draft)
            ->where(function ($q) {
                $q->whereNull('link_type')->orWhere('link_type', 'personal');
            })
            ->where('created_at', '<', $cutoff)
            ->whereDoesntHave('appUserPayments', function ($q) {
                $q->where('lean_metadata->latest_iso_status', 'PENDING_WITH_BANK');
            })
            ->get();

        // Payments that started (customer reached Lean's bank-connection
        // step) but never resolved — no webhook ever confirmed success or
        // failure, most commonly because the customer abandoned mid-flow.
        // Marked Abandoned rather than Failed so a real bank failure rate
        // isn't inflated by people who simply left.
        $payments = AppUserPayment::where('status', PaymentStatus::Initiated)
            ->where('created_at', '<', $cutoff)
            ->where(function ($q) {
                $q->whereNull('lean_metadata->latest_iso_status')
                  ->orWhere('lean_metadata->latest_iso_status', '!=', 'PENDING_WITH_BANK');
            })
            ->with('invoice')
            ->get();

        // Which links have an attempt that is being abandoned right now: those
        // get the "abandoned" event, so they must not also get "expired".
        $linksWithAbandonedAttempt = $payments->pluck('invoice_id')->unique()->all();

        Invoice::whereIn('id', $invoices->pluck('id'))->update(['status' => InvoiceStatus::Failed]);
        AppUserPayment::whereIn('id', $payments->pluck('id'))->update(['status' => PaymentStatus::Abandoned]);

        $invoiceCount = $invoices->count();
        $paymentCount = $payments->count();

        // Tell merchants' webhook receivers that these will never complete, so
        // they can release the order instead of waiting. Never lets a failed
        // delivery affect the clean-up itself.
        foreach ($payments as $payment) {
            // An older attempt on a link that has since been paid is history,
            // not news: announcing it as failed could un-pay an order.
            if ($payment->invoice && $payment->invoice->status !== InvoiceStatus::Paid) {
                $this->safely(fn () => $paymentService->notifyPaymentFailed($payment, 'abandoned'));
            }
        }

        foreach ($invoices as $invoice) {
            $neverStarted = !in_array($invoice->id, $linksWithAbandonedAttempt, true)
                && !$invoice->appUserPayments()->exists();

            if ($neverStarted) {
                $this->safely(fn () => $paymentService->notifyLinkExpired($invoice));
            }
        }

        Log::info('invoices:expire-stale completed', [
            'expiry_hours'    => $hours,
            'invoices_failed' => $invoiceCount,
            'payments_failed' => $paymentCount,
        ]);

        $this->info("Expired {$invoiceCount} stale invoice(s) and {$paymentCount} stuck payment(s).");
        return self::SUCCESS;
    }

    private function safely(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::warning('invoices:expire-stale: webhook notification failed', ['error' => $e->getMessage()]);
        }
    }
}
