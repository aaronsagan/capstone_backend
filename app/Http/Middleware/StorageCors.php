<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StorageCors
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedOrigins = [
            'http://localhost:8080',
            'http://127.0.0.1:8080',
            'http://localhost:8081',
            'http://127.0.0.1:8081',
            'http://localhost:3000',
            'http://127.0.0.1:3000',
            'http://localhost:5173',
            'http://127.0.0.1:5173',
        ];

        $origin = $request->headers->get('Origin');
        $allowOrigin = null;
        if ($origin && in_array($origin, $allowedOrigins, true)) {
            $allowOrigin = $origin;
        }

        // Handle preflight requests early
        if ($request->getMethod() === 'OPTIONS') {
            $preflight = new Response('', 204);
            if ($allowOrigin) {
                $preflight->headers->set('Access-Control-Allow-Origin', $allowOrigin);
            }
            $preflight->headers->set('Vary', 'Origin');
            $preflight->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
            $preflight->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
            $preflight->headers->set('Access-Control-Allow-Credentials', 'true');
            $preflight->headers->set('Access-Control-Max-Age', '86400');
            $preflight->headers->set('Cross-Origin-Resource-Policy', 'cross-origin');
            return $preflight;
        }

        // Process the request first
        $response = $next($request);

        // Add CORS headers to the response
        if ($allowOrigin) {
            $response->headers->set('Access-Control-Allow-Origin', $allowOrigin);
        }
        $response->headers->set('Vary', 'Origin');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Cross-Origin-Resource-Policy', 'cross-origin');

        return $response;
    }
}
