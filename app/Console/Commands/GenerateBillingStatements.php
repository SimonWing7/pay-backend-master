<?php

namespace App\Console\Commands;

use App\Models\Merchant;
use App\Services\BillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateBillingStatements extends Command
{
    protected $signature = 'billing:generate
        {--period= : Month to bill as YYYY-MM (defaults to last month)}
        {--merchant= : Only this merchant ID}
        {--dry-run : Show what would be billed without creating anything}';

    protected $description = 'Issue monthly fee statements to merchants with billing enabled, and email them';

    public function handle(BillingService $billing): int
    {
        if (!config('billing.enabled')) {
            $this->warn('Billing is switched off (BILLING_ENABLED is not true) — nothing done.');
            return self::SUCCESS;
        }

        $tz = $billing->timezone();

        if ($this->option('period')) {
            try {
                $period = Carbon::createFromFormat('!Y-m', $this->option('period'), $tz);
            } catch (\Throwable) {
                $this->error('--period must look like 2026-10.');
                return self::FAILURE;
            }

            if ($period->gt(now($tz)->startOfMonth())) {
                $this->error('That month has not started yet.');
                return self::FAILURE;
            }
        } else {
            $period = $billing->previousMonth();
        }

        $merchants = Merchant::where('billing_enabled', true)
            ->where('is_active', true)
            ->when($this->option('merchant'), fn ($q, $id) => $q->whereKey($id))
            ->orderBy('id')
            ->get();

        $created = 0;
        $existing = 0;
        $skipped = 0;

        foreach ($merchants as $merchant) {
            if ($this->option('dry-run')) {
                [$start, $end] = $billing->monthBounds($period);
                $volume = $billing->volumeFor($merchant, $start, $end);
                $fee = $billing->feeFor($volume, (float) $merchant->fee_percentage);
                $this->line("[dry run] {$merchant->name}: volume AED " . number_format($volume, 2) . " x {$merchant->fee_percentage}% = AED " . number_format($fee, 2));
                continue;
            }

            $statement = $billing->generateStatement($merchant, $period);

            if (!$statement) {
                $skipped++;
                continue;
            }

            if (!$statement->wasRecentlyCreated) {
                $existing++;
                continue;
            }

            $created++;
            $billing->sendStatementEmail($statement);
            $this->info("Issued {$statement->number} to {$merchant->name}: AED " . number_format($statement->fee_amount, 2));
        }

        if (!$this->option('dry-run')) {
            $this->info("{$period->format('F Y')}: {$created} issued, {$existing} already existed, {$skipped} with nothing to bill.");
        }

        return self::SUCCESS;
    }
}
