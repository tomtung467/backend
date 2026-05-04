<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiResponseMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Add standard headers for API responses
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('X-Requested-With', 'XMLHttpRequest');

        // Ensure status code is properly set
        if (!$response->getStatusCode()) {
            $response->setStatusCode(200);
        }

        return $response;
    }
}

