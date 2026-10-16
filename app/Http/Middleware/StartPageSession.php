<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;

class StartPageSession extends StartSession
{
    protected function storeCurrentUrl(Request $request, $session)
    {
        // WebView/PWA requests for Laravel-served assets must not become the
        // destination of back() when the browser omits the Referer header.
        $destination = $request->header('Sec-Fetch-Dest', '');
        if (($destination !== '' && ! in_array($destination, ['document', 'iframe'], true))
            || $request->expectsJson()
            || preg_match('/\.[a-z0-9]+$/i', $request->path())
            || $request->routeIs('lang.switch', 'shop-stories.feed', 'csrf.refresh', 'merchant-app.push.public-key')) {
            return;
        }

        parent::storeCurrentUrl($request, $session);
    }
}
