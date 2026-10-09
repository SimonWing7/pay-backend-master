<?php

namespace App\Console\Commands;

use App\Models\AppUserPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FlagPendingPayments extends Command
{
    protected $signature = 'payments:flag-pending';

    protected $description = 'Alert about Lean payments stuck at PENDING_WITH_BANK that need manual reconciliation';

    public function handle(): int
    {
        // Merchants who can reconcile their own payments are emailed instead
        // (payments:notify-merchants) — Edfundo is only alerted when the
        // merchant can't act on it, or hasn't within the escalation window.
        $escalateAfter = now()->subHours((int) config('invoices.pending_escalate_hours', 48));

        $payments = AppUserPayment::needsReview()
            ->where(function ($q) use ($escalateAfter) {
                $q->where('created_at', '<', $escalateAfter)
                  ->orWhereHas('invoice.merchant', fn ($m) => $m->where('allow_payment_reconciliation', false));
            })
            ->with('invoice.merchant')
            ->orderBy('created_at')
            ->get();

        if ($payments->isEmpty()) {
            $this->info('No payments awaiting manual reconciliation.');
            return self::SUCCESS;
        }

        // error level so it reaches Slack, same as other things that need a
        // human to act rather than just a log line to scroll past.
        Log::error('Payments stuck at PENDING_WITH_BANK need manual reconciliation', [
            'count'    => $payments->count(),
            'review'   => route('admin.payments.index', ['status' => 'review']),
            'payments' => $payments->map(fn ($p) => sprintf(
                '#%d | %s | %s | AED %s | %s',
                $p->id,
                $p->invoice->merchant->name ?? '—',
                $p->invoice->reference ?? '—',
                number_format($p->invoice->total_fee ?? 0, 2),
                $p->created_at->diffForHumans()
            ))->all(),
        ]);

        $this->warn("{$payments->count()} payment(s) need manual reconciliation.");
        return self::SUCCESS;
    }
}
