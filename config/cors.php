<?php

// Only the public banks list is opened up cross-origin — it's not
// merchant-specific or sensitive, just "which banks are currently live",
// so merchants' own checkout pages (Magento, custom platforms) can call it
// directly from the browser. Nothing else in the API is listed here on
// purpose: those routes are merchant-API-key authenticated and are only
// ever called server-to-server, not from a browser.
return [

    'paths' => ['api/v1/banks'],

    'allowed_methods' => ['GET'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,

];
