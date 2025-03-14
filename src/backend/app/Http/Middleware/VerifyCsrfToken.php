<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * This middleware protects the Documents View feature against Cross-Site Request Forgery (CSRF) attacks
     * by verifying that all state-changing requests include a valid CSRF token. URIs listed here are
     * exceptions that bypass CSRF protection - use with caution and only when necessary.
     *
     * @var array<int, string>
     */
    protected $except = [
        // API endpoints use token-based authentication via Laravel Sanctum instead of CSRF
        'api/*',
        
        // Adobe PDF viewer callbacks
        'document-viewer/adobe-callback/*',
        
        // External service webhooks
        'webhooks/*',
    ];
}