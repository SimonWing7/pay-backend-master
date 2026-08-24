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
