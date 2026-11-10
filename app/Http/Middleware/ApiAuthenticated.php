<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Requires a valid mobile login token (set by ApiRequest). */
class ApiAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->attributes->get('api_token')) {
            return response()->json(['message' => __('سجّل دخولك أولاً.'), 'code' => 'unauthenticated'], 401);
        }

        return $next($request);
    }
}
