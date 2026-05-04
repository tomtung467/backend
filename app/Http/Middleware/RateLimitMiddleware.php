<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Rate limiting is already handled by Laravel's built-in middleware
        // This is a placeholder for custom rate limiting logic if needed
        return $next($request);
    }
}
