<?php

return [
    /*
    |--------------------------------------------------------------------------
    | NymCard Open Finance Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for NymCard Open Finance Platform integration
    |
    */

    'api_key' => env('NYMCARD_API_KEY', ''),
    
    'environment' => env('NYMCARD_ENVIRONMENT', 'sandbox'), // sandbox or production
    
    'base_url' => env('NYMCARD_BASE_URL', 'https://api.of.dev.platform.nm-2.nymcard.com'),
    
    'webhook_secret' => env('NYMCARD_WEBHOOK_SECRET', ''),
    
    /*
    |--------------------------------------------------------------------------
    | Mock NymCard API
    |--------------------------------------------------------------------------
    |
    | When set to true, NymCard API calls will be mocked and return
    | fake responses. Useful for local development and testing.
    |
    */
    'mock' => env('NYMCARD_MOCK', env('APP_ENV') === 'local'),
];

