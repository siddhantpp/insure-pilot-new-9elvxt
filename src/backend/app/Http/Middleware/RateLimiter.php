<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter as RateLimiterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter as RateLimiterFacade;
use Symfony\Component\HttpFoundation\Response;

/**
 * Custom rate limiting middleware for the Documents View feature.
 * 
 * This middleware extends Laravel's rate limiting capabilities with specific
 * configurations for document-related endpoints to prevent abuse and ensure
 * system stability.
 */
class RateLimiter
{
    /**
     * The named rate limiters.
     *
     * @var array
     */
    protected $limiters = [];

    /**
     * Maps endpoint patterns to the corresponding rate limiter.
     *
     * @var array
     */
    protected $limitsByEndpoint = [];

    /**
     * Initialize the rate limiter middleware with predefined limiters and endpoint configurations.
     */
    public function __construct()
    {
        $this->limiters = [
            'api', 'documents', 'metadata', 'document-processing', 'document-history'
        ];

        $this->limitsByEndpoint = [
            'documents' => [
                'api/documents',
                'api/documents/*'
            ],
            'metadata' => [
                'api/documents/*/metadata'
            ],
            'document-processing' => [
                'api/documents/*/process',
                'api/documents/*/trash'
            ],
            'document-history' => [
                'api/documents/*/history'
            ]
        ];

        $this->registerLimiters();
    }

    /**
     * Handle an incoming request and apply rate limiting.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $limiterName = $this->getLimiterForRoute($request->path());

        // Check if the request exceeds the rate limit
        if (RateLimiterFacade::tooManyAttempts($limiterName, 1)) {
            $seconds = RateLimiterFacade::availableIn($limiterName);
            
            return response()->json([
                'error' => 'Too many requests',
                'message' => 'You have exceeded the rate limit for this endpoint.',
                'retry_after' => $seconds
            ], 429)->header('Retry-After', $seconds);
        }

        // Record this attempt
        RateLimiterFacade::hit($limiterName);

        // Process the request
        $response = $next($request);

        // Add rate limit headers to the response
        return $this->addRateLimitHeaders($response, $limiterName, $request);
    }

    /**
     * Determine which rate limiter to use based on the request path.
     *
     * @param  string  $path
     * @return string
     */
    protected function getLimiterForRoute(string $path): string
    {
        foreach ($this->limitsByEndpoint as $limiter => $patterns) {
            foreach ($patterns as $pattern) {
                if ($this->patternMatches($pattern, $path)) {
                    return $limiter;
                }
            }
        }

        // Default to the api limiter if no specific limiter is found
        return 'api';
    }

    /**
     * Check if a path matches a given pattern.
     *
     * @param  string  $pattern
     * @param  string  $path
     * @return bool
     */
    protected function patternMatches(string $pattern, string $path): bool
    {
        $pattern = preg_quote($pattern, '#');
        $pattern = str_replace('\*', '.*', $pattern);
        
        return (bool) preg_match('#^' . $pattern . '$#i', $path);
    }

    /**
     * Add rate limit headers to the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  string  $limiter
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addRateLimitHeaders(Response $response, string $limiter, Request $request): Response
    {
        $maxAttempts = RateLimiterFacade::limiter($limiter)->attempts($request) + 
                       RateLimiterFacade::remaining($limiter, 1);
        
        $remaining = RateLimiterFacade::remaining($limiter, 1);
        $resetAt = RateLimiterFacade::availableIn($limiter);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => $resetAt
        ]);
    }

    /**
     * Register the rate limiters with Laravel's RateLimiter facade.
     *
     * @return void
     */
    protected function registerLimiters(): void
    {
        // Default API rate limiter: 60 requests per minute
        RateLimiterFacade::for('api', function (Request $request) {
            return RateLimiterFacade::perMinute(60)->by($request->ip());
        });

        // Document retrieval: 100 requests per minute
        RateLimiterFacade::for('documents', function (Request $request) {
            return RateLimiterFacade::perMinute(100)->by($request->ip());
        });

        // Metadata updates: 60 requests per minute
        RateLimiterFacade::for('metadata', function (Request $request) {
            return RateLimiterFacade::perMinute(60)->by($request->ip());
        });

        // Document processing actions: 30 requests per minute
        RateLimiterFacade::for('document-processing', function (Request $request) {
            return RateLimiterFacade::perMinute(30)->by($request->ip());
        });

        // Document history retrieval: 60 requests per minute
        RateLimiterFacade::for('document-history', function (Request $request) {
            return RateLimiterFacade::perMinute(60)->by($request->ip());
        });
    }
}