<?php

namespace App\Console\Commands;

use App\Models\LeanBank;
use App\Services\LeanService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncLeanBanks extends Command
{
    protected $signature = 'lean:sync-banks';

    protected $description = 'Fetch the current live bank list from Lean and cache it locally';

    public function handle(LeanService $lean): int
    {
        try {
            $banks = $lean->fetchAvailableBanks();
        } catch (\Throwable $e) {
            Log::error('lean:sync-banks failed', ['error' => $e->getMessage()]);
            $this->error('Failed to fetch banks from Lean: ' . $e->getMessage());
            return self::FAILURE;
        }

        $seenIdentifiers = [];

        foreach ($banks as $bank) {
            LeanBank::updateOrCreate(
                ['identifier' => $bank['identifier']],
                $bank
            );
            $seenIdentifiers[] = $bank['identifier'];
        }

        // Anything no longer returned by Lean at all (not just disabled) is
        // treated as unavailable rather than deleted, so a transient Lean-side
        // hiccup can't make a bank vanish from our own history/logs.
        LeanBank::whereNotIn('identifier', $seenIdentifiers)->update(['is_available' => false]);

        $this->info(count($banks) . ' banks synced from Lean.');
        return self::SUCCESS;
    }
}
