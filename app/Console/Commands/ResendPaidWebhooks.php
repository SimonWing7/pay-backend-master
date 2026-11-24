<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Models\AppUserPayment;
use App\Services\PaymentService;
use Illuminate\Console\Command;

class ResendPaidWebhooks extends Command
{
    protected $signature = 'webhooks:resend-paid
        {--merchant= : Merchant ID (required unless --payment is given)}
        {--since= : Only payments completed on/after this date, e.g. 2026-11-01}
        {--reference= : Only the invoice with this reference (e.g. a Magento order number)}
        {--payment= : Resend for this one payment ID}
        {--dry-run : List what would be sent without sending}
        {--force : Don\'t ask for confirmation}';

    protected $description = 'Replay payment.paid to a merchant\'s webhook for payments they may have missed';

    public function handle(PaymentService $payments): int
    {
        if (!$this->option('payment') && !$this->option('merchant')) {
            $this->error('Give --merchant=ID (with --since and/or --reference) or --payment=ID.');
            return self::FAILURE;
        }

        $query = AppUserPayment::with('invoice.merchant')
            ->where('status', PaymentStatus::Complete)
            ->whereHas('invoice');

        if ($this->option('payment')) {
            $query->where('id', $this->option('payment'));
        }
        if ($this->option('merchant')) {
            $query->whereHas('invoice', fn ($q) => $q->where('merchant_id', $this->option('merchant')));
        }
        if ($this->option('reference')) {
            $query->whereHas('invoice', fn ($q) => $q->where('reference', $this->option('reference')));
        }
        if ($this->option('since')) {
            $query->where('updated_at', '>=', $this->option('since'));
        }

        // One notification per invoice — its most recent completed payment.
        $rows = $query->orderBy('updated_at')->get()->groupBy('invoice_id')->map->last()->values();

        if ($rows->isEmpty()) {
            $this->warn('No matching paid payments.');
            return self::SUCCESS;
        }

        $this->table(
            ['Payment', 'Reference', 'Merchant', 'Amount (AED)', 'Paid at'],
            $rows->map(fn ($p) => [
                '#' . $p->id, $p->invoice->reference ?? '—', $p->invoice->merchant->name ?? '—',
                number_format($p->invoice->total_fee, 2), $p->updated_at->format('Y-m-d H:i:s'),
            ])->all()
        );

        if ($this->option('dry-run')) {
            $this->info($rows->count() . ' payment(s) would be sent. Dry run — nothing sent.');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm("Send payment.paid to the merchant's webhook for these {$rows->count()} payment(s)?")) {
            $this->info('Cancelled — nothing sent.');
            return self::SUCCESS;
        }

        $ok = 0;
        foreach ($rows as $p) {
            $delivery = $payments->resendPaidWebhook($p);
            $state = $delivery?->status ?? 'not sent (no webhook configured)';
            $ok += $delivery && $delivery->status === 'delivered' ? 1 : 0;
            $this->line('#' . $p->id . ' ' . ($p->invoice->reference ?? '') . ' → ' . $state
                . ($delivery && $delivery->last_http_status ? ' (HTTP ' . $delivery->last_http_status . ')' : ''));
        }

        $this->info("{$ok} of {$rows->count()} delivered. Any that failed will be retried automatically.");
        return self::SUCCESS;
    }
}
