<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests from the following domains / hosts will receive stateful API
    | authentication cookies. Typically, these should include your local
    | and production domains which access your API via a frontend SPA.
    |
    */

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', implode(',', [
        'localhost',
        'localhost:3000',
        '127.0.0.1',
        '127.0.0.1:8000',
        '::1',
        'insurepilot.local',
    ]))),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | This array contains the authentication guards that will be checked when
    | Sanctum is trying to authenticate a request. If none of these guards
    | are able to authenticate the request, Sanctum will use the bearer
    | token that's present on an incoming request for authentication.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will be
    | considered expired. This is used for token-based authentication and
    | ensures tokens don't remain valid indefinitely for security reasons.
    | Set to 24 hours (1440 minutes) as per security requirements.
    |
    */

    'expiration' => 60 * 24, // 24 hours in minutes

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    |
    | Specifies the prefix used for token storage to avoid collisions with
    | other applications or services using the same storage mechanisms.
    |
    */
    
    'prefix' => env('SANCTUM_TOKEN_PREFIX', 'insurepilot_token'),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | When using Sanctum with a frontend SPA, these middleware will be applied
    | to every request. The EncryptCookies middleware encrypts cookies while
    | the VerifyCsrfToken middleware verifies the CSRF token for security.
    |
    */

    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configures how the authentication tokens are stored. Using HttpOnly
    | cookies prevents JavaScript access to tokens for enhanced security.
    | The sameSite policy helps prevent CSRF attacks.
    |
    */
    
    'token_storage' => [
        'driver' => 'cookie',
        'options' => [
            'path' => '/',
            'domain' => null,
            'secure' => env('APP_ENV') !== 'local',
            'httpOnly' => true,
            'sameSite' => 'lax',
        ],
    ],
];