<?php

namespace App\Console\Commands;

use App\Models\{ApiToken, Shop, User};
use Illuminate\Console\Command;
use Illuminate\Http\Request;

/** Calls one app API endpoint on this server as a demo user and prints the answer, to check the app's endpoints on staging. */
class ApiCheck extends Command
{
    protected $signature = 'ozman:api-check {method} {path} {--shop= : act as this shop\'s owner (slug)} {--user= : act as this user (email)} {--data= : JSON body} {--force : run outside staging/local}';

    protected $description = 'Call an /api/v1 endpoint as a demo user and print the status and the response.';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to run on production without --force.');

            return self::FAILURE;
        }
        $user = $this->option('user') ? User::where('email', $this->option('user'))->first()
            : ($this->option('shop') ? Shop::where('slug', $this->option('shop'))->first()?->user : null);
        [$token, $plain] = $user ? ApiToken::issue($user) : [null, null];
        $path = '/api/v1/'.ltrim((string) $this->argument('path'), '/');
        $data = json_decode((string) $this->option('data'), true) ?: [];
        // Built on the site's real address, so links in the answer look the way the app gets them.
        $request = Request::create(rtrim((string) config('app.url'), '/').$path, strtoupper((string) $this->argument('method')), [], [], [], array_filter([
            'HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT_LANGUAGE' => 'ar',
            'HTTP_AUTHORIZATION' => $plain ? 'Bearer '.$plain : null,
        ]), $data ? json_encode($data) : null);
        try {
            $started = microtime(true);
            $response = app(\Illuminate\Contracts\Http\Kernel::class)->handle($request);
            $took = (int) round((microtime(true) - $started) * 1000);
            $this->line('Status: '.$response->getStatusCode().($user ? ' (as '.$user->email.')' : '').' in '.$took.'ms');
            $body = (string) $response->getContent();
            $json = json_decode($body, true);
            $this->line(mb_substr($json !== null ? json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : $body, 0, 6000));
        } finally {
            $token?->delete();
        }

        return self::SUCCESS;
    }
}
