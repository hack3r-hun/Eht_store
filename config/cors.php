<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Bearer-token API/mobile clients are not subject to CORS (it only applies
    | to browser-based JS). This only matters once a browser SPA is served from
    | a different origin than this API. Set CORS_ALLOWED_ORIGINS to a comma-
    | separated allow-list of real origins when that happens — never "*" while
    | supports_credentials is true, and avoid "*" in general for an API that
    | also accepts cookies/CSRF (Sanctum's stateful SPA auth).
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', ''))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
