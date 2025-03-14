<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode as BaseCheckForMaintenanceMode;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckForMaintenanceMode extends BaseCheckForMaintenanceMode
{
    /**
     * The URIs that should be accessible while maintenance mode is enabled.
     *
     * @var array
     */
    protected $except = [
        'api/health-check',
        'admin/maintenance/*',
        'api/documents/*/status', // Allow document status checks during maintenance
        'api/auth/maintenance-auth', // Allow maintenance authentication
        'api/documents/*/file', // Allow document file retrieval for critical cases
    ];

    /**
     * Create a new middleware instance with application dependency injection.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    /**
     * Handle an incoming request and check if the application is in maintenance mode.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, \Closure $next): Response
    {
        if ($this->app->isDownForMaintenance()) {
            if ($this->shouldPassThrough($request)) {
                return $next($request);
            }
            
            // Get maintenance data
            $data = $this->getDownData();
            
            // Return custom maintenance response
            return $this->getCustomMaintenanceResponse($request, $data);
        }

        return $next($request);
    }

    /**
     * Determine if the request has a URI that should pass through maintenance mode.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldPassThrough(Request $request): bool
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->is($except)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the down data from the application.
     *
     * @return array
     */
    protected function getDownData(): array
    {
        $downFilePath = $this->app->storagePath().'/framework/down';
        
        if (file_exists($downFilePath)) {
            return json_decode(file_get_contents($downFilePath), true) ?: [];
        }
        
        return [];
    }

    /**
     * Get a custom maintenance mode response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $data
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function getCustomMaintenanceResponse(Request $request, array $data): Response
    {
        $defaultMessage = 'The application is currently undergoing scheduled maintenance. We expect to be back online shortly.';
        $message = $data['message'] ?? $defaultMessage;
        $retryAfter = $data['retry'] ?? 3600; // Default to 1 hour if not specified
        $estimatedTimeToCompletion = $data['etc'] ?? null;
        
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'status' => 'maintenance',
                'retry_after' => $retryAfter,
                'estimated_completion' => $estimatedTimeToCompletion,
                'documents_view_status' => 'temporarily_unavailable'
            ], 503)->header('Retry-After', $retryAfter);
        }
        
        // Return view response for regular requests
        return response(
            view('errors.maintenance', [
                'message' => $message,
                'retry_after' => $retryAfter,
                'estimated_completion' => $estimatedTimeToCompletion,
                'data' => $data
            ]), 
            503
        )->header('Retry-After', $retryAfter);
    }
}