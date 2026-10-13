<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telr payment gateway
    |--------------------------------------------------------------------------
    |
    | Used to bill merchants their monthly Edfundo fees by card: the merchant
    | registers a card once on Telr's hosted page (3-D Secure), then Edfundo
    | charges it each month via the Remote API (continuous authority).
    | Unrelated to Lean, which handles customer Pay-by-Bank payments.
    |
    */

    'store_id' => env('TELR_STORE_ID', ''),

    // Remote API authentication key — Telr admin panel > Integrations >
    // Remote API > Configuration. Server-side only, never shown to users.
    'auth_key' => env('TELR_AUTH_KEY', ''),

    // Hosted Payment Page API key (order.json). Telr may issue this separately
    // from the Remote API key above — if it's left blank the Remote key is used.
    'order_auth_key' => env('TELR_ORDER_AUTH_KEY') ?: env('TELR_AUTH_KEY', ''),

    // true = Telr test mode (no real money moves). Must be explicitly false
    // in production. For live Remote API calls Telr also has to whitelist
    // this server's public IP; test mode works from any IP.
    'test_mode' => (bool) env('TELR_TEST_MODE', true),

    'order_url'  => env('TELR_ORDER_URL', 'https://secure.telr.com/gateway/order.json'),
    'remote_url' => env('TELR_REMOTE_URL', 'https://secure.telr.com/gateway/remote.xml'),

    'timeout' => (int) env('TELR_TIMEOUT', 20),
];
