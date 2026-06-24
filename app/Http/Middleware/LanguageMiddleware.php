<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class LanguageMiddleware
{
    private array $supportedLocales = ['ar', 'he', 'en'];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Session::has('locale')) {
            $locale = Session::get('locale');
        } else {
            $locale = $request->getPreferredLanguage($this->supportedLocales) ?: config('app.locale', 'ar');
            Session::put('locale', $locale);
        }

        if (! in_array($locale, $this->supportedLocales, true)) {
            $locale = 'ar';
            Session::put('locale', $locale);
        }

        App::setLocale($locale);

        return $next($request);
    }
}
