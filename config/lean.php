<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lean Open Finance Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Lean Tech Open Finance integration (UAE)
    |
    */

    // App Token (Client ID) — used in the frontend Lean.pay() SDK call
    'app_token' => env('LEAN_APP_TOKEN', ''),

    // Client Secret — used for OAuth2 server-side API authentication
    'client_secret' => env('LEAN_CLIENT_SECRET', ''),

    // Environment: 'sandbox' or 'production'
    'environment' => env('LEAN_ENVIRONMENT', 'sandbox'),

    // Base URL for Lean API calls
    'base_url' => env('LEAN_BASE_URL', 'https://sandbox.leantech.me'),

    // Auth URL for OAuth2 token endpoint
    'auth_url' => env('LEAN_AUTH_URL', 'https://auth.sandbox.leantech.me'),

    // Webhook secret — used to verify HMAC-SHA256 webhook signatures
    'webhook_secret' => env('LEAN_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Payment Destination
    |--------------------------------------------------------------------------
    |
    | The Lean payment_destination_id is the registered bank account that
    | receives payments. This is configured per merchant in the Lean dashboard.
    | For sandbox testing, use the test destination ID provided by Lean.
    | In production, each merchant will have their own destination ID stored
    | on their merchant record.
    |
    */
    'payment_destination_id' => env('LEAN_PAYMENT_DESTINATION_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Payment Intent Settings
    |--------------------------------------------------------------------------
    */

    // Whether the SDK is in sandbox mode (passed to Lean.pay())
    'sandbox' => env('LEAN_ENVIRONMENT', 'sandbox') !== 'production',

    /*
    |--------------------------------------------------------------------------
    | Token Cache TTL (seconds)
    |--------------------------------------------------------------------------
    |
    | How long to cache the OAuth2 access token. Lean tokens expire after
    | 3600 seconds; we cache for 3500 to give a 100-second buffer.
    |
    */
    'token_cache_ttl' => 3500,
];
