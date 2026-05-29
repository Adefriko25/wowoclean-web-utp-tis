<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, $role)
    {
        $user = auth('api')->user();
        if (!$user || $user->role !== $role) {
            return response()->json(['message' => '403 Forbidden. Akses ditolak.'], 403);
        }
        return $next($request);
    }
}