<?php

namespace App\Console\Commands;

use App\Models\BillingStatement;
use App\Services\BillingService;
use Illuminate\Console\Command;

class ChargeBillingStatements extends Command
{
    protected $signature = 'billing:charge
        {--statement= : Only this statement ID}
        {--now : Charge immediately, ignoring the schedule (needs --statement)}';

    protected $description = 'Charge merchants\' registered cards for statements that are due (and retry declined ones)';

    public function handle(BillingService $billing): int
    {
        if (!config('billing.enabled')) {
            $this->warn('Billing is switched off (BILLING_ENABLED is not true) — nothing done.');
            return self::SUCCESS;
        }

        if ($this->option('now') && !$this->option('statement')) {
            $this->error('--now only works together with --statement=ID, so a scheduled run can never skip the schedule.');
            return self::FAILURE;
        }

        $stale = $billing->recoverStaleCharges();
        if ($stale > 0) {
            $this->warn("{$stale} statement(s) were stuck mid-charge and have been moved to review.");
        }

        if ($this->option('statement')) {
            $statement = BillingStatement::find($this->option('statement'));

            if (!$statement) {
                $this->error('Statement not found.');
                return self::FAILURE;
            }

            $this->line("{$statement->number}: " . $billing->charge($statement, (bool) $this->option('now')));

            return self::SUCCESS;
        }

        $tally = [];

        foreach ($billing->dueStatements() as $statement) {
            $result = $billing->charge($statement);
            $tally[$result] = ($tally[$result] ?? 0) + 1;
            $this->line("{$statement->number}: {$result}");
        }

        $this->info($tally ? 'Done: ' . collect($tally)->map(fn ($n, $k) => "{$n} {$k}")->implode(', ') . '.' : 'Nothing due.');

        return self::SUCCESS;
    }
}
