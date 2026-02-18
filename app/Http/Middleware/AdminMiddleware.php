<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authentication required'
            ], 401);
        }

        // Check if user is admin
        if (strtolower($user->type) !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Admin access required'
            ], 403);
        }

        return $next($request);
    }
}
