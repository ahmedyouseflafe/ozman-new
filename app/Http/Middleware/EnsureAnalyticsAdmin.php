<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAnalyticsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->isSuperAdmin() && $request->user()->is_active, 403);
        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store');
        return $response;
    }
}
