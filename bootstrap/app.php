<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Console\Commands\ImportIphoneCatalog;
use App\Console\Commands\ImportSalemKhatibMenu;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([ImportIphoneCatalog::class, ImportSalemKhatibMenu::class])
    ->withMiddleware(function (Middleware $middleware): void {
        // Protected pages must send logged-out visitors to the login screen.
        // Without this Laravel returns an authentication error instead of a redirect.
        $middleware->redirectGuestsTo('/login');

        $middleware->web(append: [
            \App\Http\Middleware\CanonicalDomain::class,
            \App\Http\Middleware\LanguageMiddleware::class,
            \App\Http\Middleware\AddSiteIcon::class,
        ]);

        $middleware->alias([
            'admin.access' => \App\Http\Middleware\EnsureAdminAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
