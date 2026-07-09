<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanonicalDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $canonicalHost = $this->canonicalHost();

        if (! $canonicalHost) {
            return $next($request);
        }

        $currentHost = strtolower($request->getHost());
        $canonicalHost = strtolower($canonicalHost);

        if ($currentHost === $canonicalHost) {
            return $next($request);
        }

        if ($this->sameDomainWithOptionalWww($currentHost, $canonicalHost)) {
            $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: $request->getScheme();
            $url = $scheme . '://' . $canonicalHost . $request->getRequestUri();

            return redirect()->to($url, 301);
        }

        return $next($request);
    }

    private function canonicalHost(): ?string
    {
        $host = env('APP_CANONICAL_HOST');

        if (! $host) {
            $host = parse_url((string) config('app.url'), PHP_URL_HOST);
        }

        $host = strtolower(trim((string) $host));

        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return null;
        }

        return $host;
    }

    private function sameDomainWithOptionalWww(string $currentHost, string $canonicalHost): bool
    {
        return $this->withoutWww($currentHost) === $this->withoutWww($canonicalHost);
    }

    private function withoutWww(string $host): string
    {
        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }
}
