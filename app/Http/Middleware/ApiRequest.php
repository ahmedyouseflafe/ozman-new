<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every mobile API request: always answer JSON, pick the language, and sign the user in from
 * a Bearer token when one is sent. Routes that need a user add the "api.auth" middleware.
 */
class ApiRequest
{
    public const LOCALES = ['ar', 'he', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        $lang = strtolower(substr((string) ($request->query('lang') ?: $request->header('Accept-Language', 'ar')), 0, 2));
        app()->setLocale(in_array($lang, self::LOCALES, true) ? $lang : 'ar');

        if ($bearer = $request->bearerToken()) {
            $token = ApiToken::findValid($bearer);
            if ($token) {
                Auth::setUser($token->user);
                $request->attributes->set('api_token', $token);
                // Writing on every request is wasteful; once a minute is enough to show activity.
                if (! $token->last_used_at || $token->last_used_at->lt(now()->subMinute())) {
                    $token->forceFill(['last_used_at' => now()])->saveQuietly();
                }
            } else {
                $request->attributes->set('api_token_invalid', true);
            }
        }

        return $next($request);
    }
}
