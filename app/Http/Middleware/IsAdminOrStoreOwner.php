<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsAdminOrStoreOwner
{
    public function handle($request, Closure $next)
    {
        // Check if the user is an admin
        if (Auth::check() && Auth::user()->role == 'admin') {
            return $next($request);
        }

        // Check if the user is a store owner
        if (Auth::check() && Auth::user()->role == 'store_owner') {
            return $next($request);
        }

        // If neither condition is met, deny access
        return response()->json(['message' => 'Access denied'], 403);
    }
}
