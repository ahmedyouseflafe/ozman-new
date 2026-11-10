<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Console\Commands\ImportIphoneCatalog;
use App\Console\Commands\ImportSalemKhatibMenu;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([ImportIphoneCatalog::class, ImportSalemKhatibMenu::class])
    ->withMiddleware(function (Middleware $middleware): void {
        // Protected pages must send logged-out visitors to the login screen.
        // Without this Laravel returns an authentication error instead of a redirect.
        // Customer, promoter and free-market pages (/community/*, /promoter/*, /free-market/*) use the community login, never the dashboard one.
        $middleware->redirectGuestsTo(fn (\Illuminate\Http\Request $request) => $request->is('community', 'community/*', 'promoter', 'promoter/*', 'free-market/*')
            ? route('community.login')
            : '/login');
        // Signed-in visitors opening a login page: customers go to their account, everyone else to the dashboard.
        $middleware->redirectUsersTo(fn (\Illuminate\Http\Request $request) => $request->user()?->isCustomer() ? route('community.account') : '/dashboard');

        $middleware->web(replace: [
            \Illuminate\Session\Middleware\StartSession::class => \App\Http\Middleware\StartPageSession::class,
        ], append: [
            \App\Http\Middleware\CanonicalDomain::class,
            \App\Http\Middleware\LanguageMiddleware::class,
            \App\Http\Middleware\AddSiteIcon::class,
            \App\Http\Middleware\RecordShopVisit::class,
        ]);

        // Mobile app API: JSON only, language from the app, Bearer-token login.
        $middleware->api(prepend: [\App\Http\Middleware\ApiRequest::class]);

        $middleware->alias([
            'admin.access' => \App\Http\Middleware\EnsureAdminAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
