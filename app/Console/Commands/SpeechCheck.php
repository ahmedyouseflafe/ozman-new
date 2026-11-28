<?php

namespace App\Console\Commands;

use App\Services\Assistant\Speech;
use Illuminate\Console\Command;

/** Makes Fulla say a short line with the natural voice set up on this server, and prints which voice and how big. */
class SpeechCheck extends Command
{
    protected $signature = 'ozman:speech-check {text=أهلين! أنا فلة، كيف بقدر أساعدك اليوم؟} {--lang=ar}';

    protected $description = 'Check Fulla\'s natural voice (Azure or Gemini) on this server';

    public function handle(Speech $speech): int
    {
        $provider = $speech->provider();
        $this->line('Voice: '.($provider ?? 'none (the phone reads it)'));
        if ($provider === null) {
            return self::FAILURE;
        }
        $started = microtime(true);
        try {
            $path = $speech->audio((string) $this->argument('text'), (string) $this->option('lang'));
            $seconds = microtime(true) - $started;
            $bytes = \Illuminate\Support\Facades\Storage::disk('local')->size($path);
            $this->line(sprintf('OK: %d KB of audio in %.1fs%s', intdiv($bytes, 1024), $seconds, $seconds < 0.3 ? ' (made before, from the cache)' : ''));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed: '.mb_substr($e->getMessage(), 0, 300));

            return self::FAILURE;
        }
    }
}
