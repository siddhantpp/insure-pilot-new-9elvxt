<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider; // Laravel ^10.0
use Illuminate\Support\Facades\Route; // Laravel ^10.0
use Illuminate\Http\Request; // Laravel ^10.0
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Document;
use App\Models\Policy;
use App\Models\Loss;
use App\Models\Claimant;
use App\Models\Producer;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * The controller namespace for the application.
     *
     * @var string|null
     */
    protected $namespace = 'App\\Http\\Controllers';

    /**
     * The middleware priority list.
     *
     * This forces non-global middleware to always be in the given order.
     *
     * @var array
     */
    protected $middlewarePriority = [
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
        \Illuminate\Routing\Middleware\ThrottleRequests::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Auth\Middleware\Authorize::class,
    ];

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureModelBinding();
        $this->configureRateLimiting();
        
        parent::boot();
        
        $this->routes();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function routes()
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/api.php'));

        Route::middleware('web')
            ->group(base_path('routes/web.php'));
            
        Route::middleware('web')
            ->group(base_path('routes/channels.php'));
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        // Default API rate limiter: 60 requests per minute per user
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
        
        // Document viewing rate limiter: 100 requests per minute per user
        RateLimiter::for('document_view', function (Request $request) {
            return Limit::perMinute(100)->by(optional($request->user())->id ?: $request->ip());
        });
        
        // Document metadata update rate limiter: 60 requests per minute per user
        RateLimiter::for('document_update', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
        
        // Document processing rate limiter: 30 requests per minute per user
        RateLimiter::for('document_process', function (Request $request) {
            return Limit::perMinute(30)->by(optional($request->user())->id ?: $request->ip());
        });
    }
    
    /**
     * Configure route model binding for document-related models.
     *
     * @return void
     */
    protected function configureModelBinding()
    {
        // Bind 'document' route parameter to Document model, including trashed documents
        Route::bind('document', function ($value) {
            return Document::withTrashed()->findOrFail($value);
        });
        
        // Bind 'policy' route parameter to Policy model
        Route::bind('policy', function ($value) {
            return Policy::findOrFail($value);
        });
        
        // Bind 'loss' route parameter to Loss model
        Route::bind('loss', function ($value) {
            return Loss::findOrFail($value);
        });
        
        // Bind 'claimant' route parameter to Claimant model
        Route::bind('claimant', function ($value) {
            return Claimant::findOrFail($value);
        });
        
        // Bind 'producer' route parameter to Producer model
        Route::bind('producer', function ($value) {
            return Producer::findOrFail($value);
        });
    }
}