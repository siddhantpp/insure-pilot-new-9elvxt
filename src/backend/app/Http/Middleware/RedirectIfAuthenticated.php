<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Laravel ^10.0
use App\Providers\RouteServiceProvider;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * This middleware checks if the user is already authenticated and redirects
     * them away from guest-only routes (like login or registration pages) to
     * the application's home page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $guard = null)
    {
        // Check if the user is authenticated using the specified guard
        // or the default guard if none is specified
        if (Auth::guard($guard)->check()) {
            // User is authenticated, redirect them to the home page
            // defined in RouteServiceProvider
            return redirect(RouteServiceProvider::HOME);
        }

        // User is not authenticated, continue request pipeline
        return $next($request);
    }
}