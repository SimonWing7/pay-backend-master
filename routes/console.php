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

// Merchant-scheduled payment-chase emails for bulk-invoiced products.
Schedule::command('reminders:send')->dailyAt('08:00');

// Lean payments stuck at PENDING_WITH_BANK never resolve on their own —
// alert (via the error log channel) until someone reconciles them.
Schedule::command('payments:flag-pending')->dailyAt('09:00');

// Email merchants who can self-reconcile as soon as a payment becomes
// reviewable (stamped per payment, so each is only emailed once).
Schedule::command('payments:notify-merchants')->hourly();

// Sends queued payer payment links in batches (see payers:send-links).
Schedule::command('payers:send-links')->everyMinute()->withoutOverlapping();

// Monthly merchant fee billing (does nothing unless BILLING_ENABLED=true):
// statements go out on the 1st, cards are charged from the 3rd, and the same
// daily run picks up retries and merchants who have since added a card.
Schedule::command('billing:generate')->monthlyOn(1, '06:00')->timezone('Asia/Dubai')->withoutOverlapping();
Schedule::command('billing:charge')->dailyAt('09:00')->timezone('Asia/Dubai')->withoutOverlapping();
