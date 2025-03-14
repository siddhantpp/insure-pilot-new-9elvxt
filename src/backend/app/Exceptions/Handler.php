<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler; // ^10.0
use Illuminate\Http\Request; // ^10.0
use Illuminate\Http\Response; // ^10.0
use Illuminate\Http\JsonResponse; // ^10.0
use Illuminate\Support\Facades\Log; // ^10.0
use Illuminate\Auth\AuthenticationException; // ^10.0
use Illuminate\Auth\Access\AuthorizationException; // ^10.0
use Illuminate\Database\Eloquent\ModelNotFoundException; // ^10.0
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException; // ^6.0
use Throwable; // 8.2
use Illuminate\Validation\ValidationException; // ^10.0

/**
 * Custom exception handler for the Documents View feature that extends Laravel's base exception handler.
 * This class is responsible for handling all exceptions that occur within the application,
 * providing appropriate responses based on the exception type and request context.
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        AuthenticationException::class,
        AuthorizationException::class,
        ValidationException::class,
        \Illuminate\Session\TokenMismatchException::class,
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register custom exception handling callbacks.
     *
     * @return void
     */
    public function register(): void
    {
        parent::register();

        $this->reportable(function (Throwable $e) {
            // Custom reporting logic can be added here
        });

        // Custom renderable callbacks for specific exception types
        $this->renderable(function (ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $this->handleApiException($request, $e);
            }
        });

        $this->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $this->handleApiException($request, $e);
            }
        });

        $this->renderable(function (AuthorizationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $this->handleApiException($request, $e);
            }
        });

        $this->renderable(function (ModelNotFoundException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $this->handleApiException($request, $e);
            }
        });

        $this->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $this->handleApiException($request, $e);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function render($request, Throwable $exception)
    {
        // Check if the request expects JSON (API request)
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $exception);
        }

        // Log the exception for web requests
        if (!in_array(get_class($exception), $this->dontReport)) {
            Log::error('Exception: ' . $exception->getMessage(), [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'url' => $request->fullUrl(),
                'user' => $request->user() ? $request->user()->id : 'unauthenticated',
                'request_method' => $request->method(),
                'request_ip' => $request->ip()
            ]);
        }

        // For web requests, use the default Laravel exception handling
        return parent::render($request, $exception);
    }

    /**
     * Handle exceptions for API requests with appropriate JSON responses.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleApiException(Request $request, Throwable $exception): JsonResponse
    {
        // Get status code and message
        $statusCode = $this->getStatusCode($exception);
        $message = $this->getExceptionMessage($exception);

        // Create response data
        $responseData = [
            'success' => false,
            'message' => $message,
            'status' => $statusCode
        ];

        // Add validation errors if applicable
        if ($exception instanceof ValidationException) {
            $responseData['errors'] = $exception->errors();
        }

        // Add debug information in non-production environments
        if (config('app.env') !== 'production') {
            $responseData['exception'] = get_class($exception);
            $responseData['file'] = $exception->getFile();
            $responseData['line'] = $exception->getLine();
            $responseData['trace'] = collect($exception->getTrace())
                ->map(function ($trace) {
                    // Remove arguments from trace to prevent sensitive data leakage
                    if (isset($trace['args'])) {
                        unset($trace['args']);
                    }
                    return $trace;
                })
                ->take(10) // Limit trace size to avoid overly large responses
                ->toArray();
        }

        // Log the exception (if not already logged in render method)
        if (!in_array(get_class($exception), $this->dontReport)) {
            Log::error('API Exception: ' . $exception->getMessage(), [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'url' => $request->fullUrl(),
                'user' => $request->user() ? $request->user()->id : 'unauthenticated',
                'request_method' => $request->method(),
                'request_ip' => $request->ip()
            ]);
        }

        return new JsonResponse($responseData, $statusCode);
    }

    /**
     * Get the appropriate status code for an exception.
     *
     * @param  \Throwable  $exception
     * @return int
     */
    protected function getStatusCode(Throwable $exception): int
    {
        if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
            return 404; // Not Found
        }

        if ($exception instanceof ValidationException) {
            return 422; // Unprocessable Entity
        }

        if ($exception instanceof AuthenticationException) {
            return 401; // Unauthorized
        }

        if ($exception instanceof AuthorizationException) {
            return 403; // Forbidden
        }

        // Check if the exception has a specific status code method
        if (method_exists($exception, 'getStatusCode')) {
            return $exception->getStatusCode();
        }

        // Default to 500 Internal Server Error
        return 500;
    }

    /**
     * Get a user-friendly message for an exception.
     *
     * @param  \Throwable  $exception
     * @return string
     */
    protected function getExceptionMessage(Throwable $exception): string
    {
        if ($exception instanceof ModelNotFoundException) {
            return 'Resource not found.';
        }

        if ($exception instanceof ValidationException) {
            return 'The given data was invalid.';
        }

        if ($exception instanceof AuthenticationException) {
            return 'Unauthenticated.';
        }

        if ($exception instanceof AuthorizationException) {
            return 'Unauthorized action.';
        }

        if ($exception instanceof NotFoundHttpException) {
            return 'The requested resource was not found.';
        }

        // In production, show a generic message to avoid exposing sensitive details
        if (config('app.env') === 'production') {
            return 'An unexpected error occurred. Our team has been notified.';
        }

        // In non-production environments, show actual error message for debugging
        return $exception->getMessage();
    }
}