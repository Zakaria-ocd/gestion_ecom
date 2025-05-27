<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBuyerRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated and has buyer role
        if (!$request->user() || $request->user()->role !== 'buyer') {
            return response()->json([
                'message' => 'Access denied. Only buyers can access this resource.'
            ], 403);
        }

        return $next($request);
    }
} 