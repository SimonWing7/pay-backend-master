<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Also refreshed on-demand by the `bank.availability.updated` Lean webhook —
// this daily run is just the fallback in case that webhook is ever missed.
Schedule::command('lean:sync-banks')->daily();

// Hourly rather than daily — a customer who abandons checkout should stop
// showing as "Pending" in the merchant's dashboard well before the full
// expiry window elapses, not just once a day.
Schedule::command('invoices:expire-stale')->hourly();
