<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TextToSpeechTest extends TestCase
{
    public function test_the_reader_works_but_cannot_be_used_as_an_open_relay(): void
    {
        Http::fake(['translate.google.com/*' => Http::response('ID3-audio', 200)]);
        $this->get('/tts/arabic?text='.urlencode('مرحبا'))->assertOk()->assertHeader('Content-Type', 'audio/mpeg');
        for ($i = 0; $i < 59; $i++) {
            $this->get('/tts/hebrew?text=hi'.$i);
        }
        $this->get('/tts/arabic?text=again')->assertStatus(429);
    }
}
