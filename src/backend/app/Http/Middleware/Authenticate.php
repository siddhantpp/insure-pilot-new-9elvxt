<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware; // laravel/framework ^10.0
use Illuminate\Http\Request; // laravel/framework ^10.0

/**
 * Authentication middleware that protects routes from unauthorized access in the Documents View feature.
 * 
 * This middleware extends Laravel's base authentication middleware to provide custom redirection logic
 * for unauthenticated users. It enforces authentication requirements for all protected routes,
 * ensuring only authenticated users can access document viewing functionality.
 * 
 * This implementation supports the security architecture specified in the technical documentation,
 * working with Laravel Sanctum for token-based authentication.
 */
class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo(Request $request): ?string
    {
        // For API requests (which expect JSON), return null to trigger a 401 Unauthorized response
        // instead of a redirect, following REST API best practices
        if ($request->expectsJson()) {
            return null;
        }

        // For web requests, redirect unauthenticated users to the login page
        // This ensures users are prompted to authenticate before accessing protected routes
        return route('login');
    }
}