<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * These cookies may include third-party cookies that need to be read by external services
     * or cookies that don't contain sensitive information and don't require encryption.
     *
     * For the Documents View feature, all authentication and session cookies are encrypted
     * to protect user sessions and document access tokens.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Add any cookies that should not be encrypted here
        // For example:
        // 'analytics_cookie',
        // 'preference_cookie',
    ];
}