<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Monthly merchant fee billing
    |--------------------------------------------------------------------------
    |
    | Edfundo bills each merchant a percentage of their completed payments:
    | a statement is issued on the 1st for the previous calendar month
    | (Asia/Dubai) and the merchant's registered card is charged on the 3rd
    | via Telr. See App\Services\BillingService.
    |
    */

    // Master switch. While false, billing:generate and billing:charge do
    // nothing, so deploying this feature can't bill anyone. A merchant is
    // additionally only billed once an admin ticks "billing enabled" for them.
    'enabled' => (bool) env('BILLING_ENABLED', false),

    // Fee percentage given to a merchant until an admin changes it.
    'default_fee_percentage' => 1.5,

    'timezone' => 'Asia/Dubai',

    // Day of the following month the card is charged.
    'charge_day' => 3,

    // Failed charges are retried this many days after the FIRST failed
    // attempt: 3 then 7 days later. Three attempts in total.
    'retry_after_days' => [3, 7],

    // Small real sale made when a merchant registers a card (Telr needs an
    // authorised 3-D Secure sale to create a reusable reference). It must NOT
    // be refunded straight away: a same-day refund is processed as a void,
    // which cancels the sale the monthly charges depend on.
    'verification_amount' => 1.00,

    'currency' => 'AED',
];
