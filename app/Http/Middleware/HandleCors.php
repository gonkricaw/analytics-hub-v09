<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HandleCors Middleware
 * 
 * Purpose: Handle Cross-Origin Resource Sharing (CORS) for Analytics Hub
 * This middleware manages CORS headers for embedded content and external analytics platforms
 * 
 * Security Features:
 * - Validates origins against whitelist
 * - Sets appropriate headers for iframe embedding
 * - Handles preflight requests
 * - Provides secure cross-origin communication for Power BI, Tableau, etc.
 * 
 * @package App\Http\Middleware
 */
class HandleCors
{
    /**
     * Handle an incoming request.
     * 
     * Processes CORS headers for cross-origin requests, particularly for embedded content
     * from external analytics platforms like Power BI, Tableau, and Google Data Studio
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get CORS configuration
        $allowedOrigins = config('cors.allowed_origins', []);
        $allowedOriginPatterns = config('cors.allowed_origins_patterns', []);
        $allowedMethods = config('cors.allowed_methods', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']);
        $allowedHeaders = config('cors.allowed_headers', ['*']);
        $exposedHeaders = config('cors.exposed_headers', []);
        $maxAge = config('cors.max_age', 0);
        $supportsCredentials = config('cors.supports_credentials', false);

        // Get the origin of the request
        $origin = $request->header('Origin');

        // Handle preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 200);
        } else {
            $response = $next($request);
        }

        // Check if origin is allowed
        $isAllowedOrigin = false;
        
        if ($origin) {
            // Check exact matches
            if (in_array($origin, $allowedOrigins)) {
                $isAllowedOrigin = true;
            }
            
            // Check pattern matches
            foreach ($allowedOriginPatterns as $pattern) {
                if (preg_match($pattern, $origin)) {
                    $isAllowedOrigin = true;
                    break;
                }
            }
        }

        // Set CORS headers if origin is allowed
        if ($isAllowedOrigin) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        }

        // Set other CORS headers
        $response->headers->set('Access-Control-Allow-Methods', implode(', ', $allowedMethods));
        
        if (in_array('*', $allowedHeaders)) {
            $response->headers->set('Access-Control-Allow-Headers', '*');
        } else {
            $response->headers->set('Access-Control-Allow-Headers', implode(', ', $allowedHeaders));
        }

        if (!empty($exposedHeaders)) {
            $response->headers->set('Access-Control-Expose-Headers', implode(', ', $exposedHeaders));
        }

        if ($maxAge > 0) {
            $response->headers->set('Access-Control-Max-Age', $maxAge);
        }

        if ($supportsCredentials) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        // Additional security headers for iframe embedding
        if ($request->is('content/*') || $request->is('embed/*')) {
            // Allow embedding in iframes from trusted sources
            if ($isAllowedOrigin) {
                $response->headers->set('X-Frame-Options', "ALLOW-FROM $origin");
                $response->headers->set('Content-Security-Policy', "frame-ancestors 'self' $origin");
            } else {
                $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
                $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'");
            }
        }

        return $response;
    }
}
