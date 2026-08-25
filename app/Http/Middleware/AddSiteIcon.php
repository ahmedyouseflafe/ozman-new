<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSiteIcon
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $contentType = (string) $response->headers->get('Content-Type');

        if (! str_contains($contentType, 'text/html') || ! method_exists($response, 'getContent')) {
            return $response;
        }

        $content = $response->getContent();

        if (! is_string($content) || stripos($content, '</head>') === false || stripos($content, 'rel="icon"') !== false) {
            return $response;
        }

        $icon = '<link rel="icon" type="image/svg+xml" href="'.asset('favicon.svg').'?v=2">';
        $content = preg_replace('/<\/head>/i', "    {$icon}\n</head>", $content, 1);
        $response->setContent($content);

        return $response;
    }
}
