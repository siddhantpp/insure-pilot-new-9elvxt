<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS) Configuration
|--------------------------------------------------------------------------
|
| This file configures the CORS settings for the Documents View feature
| and other API endpoints. CORS determines which origins, methods, and
| headers are permitted when the frontend or other external systems
| make cross-origin requests to our API.
|
| Security Note: These settings are critical for protecting the API from
| unauthorized cross-origin access while allowing legitimate clients to
| communicate with the backend services.
|
| Laravel CORS Package v3.0.0
|
*/

return [
    /*
    |--------------------------------------------------------------------------
    | CORS Paths
    |--------------------------------------------------------------------------
    |
    | Define which paths should be CORS enabled. Paths may use wildcards (*).
    |
    */
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Request Origins
    |--------------------------------------------------------------------------
    |
    | Define which origins are allowed to make cross-origin requests.
    | For security, this should be limited to trusted domains only.
    |
    */
    'allowed_origins' => [
        'http://localhost:3000',
        'https://*.insurepilot.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins Patterns
    |--------------------------------------------------------------------------
    |
    | Define regex patterns for origins that should be allowed.
    | Use this for more complex origin matching requirements.
    |
    */
    'allowed_origins_patterns' => [],

    /*
    |--------------------------------------------------------------------------
    | Allowed HTTP Methods
    |--------------------------------------------------------------------------
    |
    | Define which HTTP methods are allowed for cross-origin requests.
    | This should be limited to only the methods required by the API.
    |
    */
    'allowed_methods' => [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'OPTIONS',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Request Headers
    |--------------------------------------------------------------------------
    |
    | Define which HTTP headers are allowed in cross-origin requests.
    |
    */
    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'Authorization',
        'X-CSRF-TOKEN',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exposed Headers
    |--------------------------------------------------------------------------
    |
    | Define which HTTP headers are exposed to the browser.
    |
    */
    'exposed_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Max Age
    |--------------------------------------------------------------------------
    |
    | Define how long the results of a preflight request can be cached.
    | A value of 0 means the results are not cached.
    |
    */
    'max_age' => 0,

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    |
    | Define whether cookies and authentication headers are allowed in
    | cross-origin requests. Required for token-based authentication.
    |
    */
    'supports_credentials' => true,
];