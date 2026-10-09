<?php

namespace App\Console\Commands;

use App\Mail\PaymentsNeedReviewMail;
use App\Models\AppUserPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyMerchantsPendingPayments extends Command
{
    protected $signature = 'payments:notify-merchants';

    protected $description = 'Email merchants (who can self-reconcile) a digest of payments stuck at PENDING_WITH_BANK that they should check against their bank account';

    public function handle(): int
    {
        $payments = AppUserPayment::needsReview()
            ->whereNull('review_notified_at')
            ->whereHas('invoice.merchant', fn ($q) => $q->where('allow_payment_reconciliation', true))
            ->with('invoice.merchant')
            ->orderBy('created_at')
            ->get();

        $sent = 0;

        foreach ($payments->groupBy(fn ($p) => $p->invoice->merchant_id) as $group) {
            $merchant = $group->first()->invoice->merchant;
            $to = $merchant->notification_email ?: $merchant->support_email ?: $merchant->email;

            try {
                Mail::to($to)->send(new PaymentsNeedReviewMail($merchant, $group));
                AppUserPayment::whereIn('id', $group->pluck('id'))->update(['review_notified_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                // Left un-stamped so the next hourly run retries it.
                Log::error('Pending-payment merchant email failed', [
                    'merchant_id' => $merchant->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        $this->info("Sent {$sent} merchant digest email(s).");
        return self::SUCCESS;
    }
}
