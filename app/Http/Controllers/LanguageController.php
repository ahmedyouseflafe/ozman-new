<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        $target = '/';
        foreach ([$request->query('return_to'), $request->header('Referer'), $request->session()->previousUrl()] as $candidate) {
            $path = $this->pagePath($request, $candidate);
            if ($path !== null) {
                $target = $path;
                break;
            }
        }

        $response = redirect()->to($target);
        if (! in_array($locale, ['ar', 'he', 'en'], true)) {
            return $response;
        }

        $request->session()->put('locale', $locale);
        if ($request->query('source') === 'device') {
            return $response;
        }

        return $response->withCookie(cookie('ozman_public_locale', $locale, 60 * 24 * 365 * 5,
            '/', null, $request->isSecure(), false, false, 'lax'));
    }

    private function pagePath(Request $request, mixed $candidate): ?string
    {
        if (! is_string($candidate) || $candidate === '' || preg_match('/[\\\\\x00-\x20]/', rawurldecode($candidate))) {
            return null;
        }

        $parts = parse_url($candidate);
        if ($parts === false || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['scheme']) && ! in_array($parts['scheme'], ['http', 'https'], true))
            || (isset($parts['host']) && strtolower($parts['host']) !== strtolower($request->getHost()))
            || (isset($parts['port']) && $parts['port'] !== $request->getPort())) {
            return null;
        }

        $path = $parts['path'] ?? '/';
        $decoded = rawurldecode($path);
        if (! str_starts_with($path, '/') || str_starts_with($decoded, '//')
            || preg_match('~(?:^|/)\.\.(?:/|$)|\.[a-z0-9]+/?$~i', $decoded)
            || preg_match('~^/(?:lang|front-assets|shop-stories|csrf-token)(?:/|$)~', $decoded)) {
            return null;
        }

        return $path.(isset($parts['query']) ? '?'.$parts['query'] : '').(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }
}
