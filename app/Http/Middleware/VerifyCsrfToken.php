<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '*',  // Matikan CSRF untuk semua endpoint
    ];

    /**
     * Handle an incoming request.
     * Override this method to completely disable CSRF.
     */
    public function handle($request, $next)
    {
        // Skip CSRF check entirely
        return $next($request);
    }
}