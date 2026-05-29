<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role)
    {
        if (!auth('api')->check() || auth('api')->user()->role !== $role) {
            return response()->json(['message' => 'Forbidden: Akses ditolak'], 403);
        }

        return $next($request);
    }
}