<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Authentication required'
                ], 401);
            }
            return redirect()->route('login')->with('error', 'Authentication required');
        }

        // Check if user is admin
        if (!auth()->user()->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Unauthorized access. Admin privileges required'
                ], 403);
            }
            return redirect()->route('dashboard')->with('error', 'Unauthorized access. Admin privileges required');
        }

        return $next($request);
    }
}
