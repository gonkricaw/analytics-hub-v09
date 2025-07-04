<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | Analytics Hub: Custom CORS configuration for embedded content and iframe security
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Allowed Methods
    |--------------------------------------------------------------------------
    |
    | This value determines which HTTP methods are allowed for cross-origin
    | requests. You may specify any methods that you want to allow.
    |
    */

    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | This value determines which origins are allowed to make cross-origin
    | requests. You may specify specific origins or use patterns.
    |
    | Analytics Hub: Restricted to trusted domains for security
    |
    */

    'allowed_origins' => [
        // Microsoft Power BI domains
        'https://app.powerbi.com',
        'https://analysis.windows.net',
        'https://api.powerbi.com',
        
        // Tableau domains
        'https://*.tableau.com',
        'https://*.tableauusercontent.com',
        
        // Google Data Studio domains
        'https://datastudio.google.com',
        'https://lookerstudio.google.com',
        
        // Local development
        'http://localhost',
        'http://127.0.0.1',
        
        // Add your production domains here
        env('APP_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins Patterns
    |--------------------------------------------------------------------------
    |
    | You may use patterns to specify allowed origins. This is useful when
    | you need to allow subdomains or have dynamic origin requirements.
    |
    */

    'allowed_origins_patterns' => [
        '/^https:\/\/.*\.powerbi\.com$/',
        '/^https:\/\/.*\.tableau\.com$/',
        '/^https:\/\/.*\.tableauusercontent\.com$/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Headers
    |--------------------------------------------------------------------------
    |
    | This value determines which headers are allowed for cross-origin
    | requests. You may specify any headers that you want to allow.
    |
    */

    'allowed_headers' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Exposed Headers
    |--------------------------------------------------------------------------
    |
    | This value determines which headers are exposed to the browser in
    | the response for cross-origin requests.
    |
    */

    'exposed_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Max Age
    |--------------------------------------------------------------------------
    |
    | This value determines the maximum age for preflight request caching.
    | This is specified in seconds and helps reduce the number of preflight
    | requests for repeated cross-origin requests.
    |
    */

    'max_age' => 0,

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    |
    | This value determines whether the request can include user credentials
    | like cookies, authorization headers or TLS client certificates.
    |
    | Analytics Hub: Disabled for security with embedded content
    |
    */

    'supports_credentials' => false,

];
