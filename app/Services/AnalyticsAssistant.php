<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Claude reads the analytics report and answers the admin: a period analysis and follow-up questions. */
class AnalyticsAssistant
{
    public function configured(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    /**
     * @param  array  $report  ShopAnalytics::report()
     * @param  array  $history  earlier turns: [['role' => 'user'|'assistant', 'content' => string], ...]
     */
    public function ask(array $report, array $history, ?string $question): string
    {
        if (! $this->configured()) {
            throw new RuntimeException('ANTHROPIC_API_KEY is not set.');
        }
        $question = trim((string) $question) ?: 'حلّل هالفترة: شو الأشياء المهمة اللي صارت، وين المشاكل، وشو أعمل هالأسبوع؟';

        // The data goes in the first message only; later turns refer back to it.
        $messages = [['role' => 'user', 'content' => "بيانات لوحة أوزمان للفترة المختارة (JSON):\n".$this->context($report)]];
        $messages[] = ['role' => 'assistant', 'content' => 'وصلتني البيانات. شو بدك تعرف؟'];
        foreach (array_slice($history, -8) as $turn) {
            $messages[] = ['role' => $turn['role'], 'content' => mb_substr($turn['content'], 0, 4000)];
        }
        $messages[] = ['role' => 'user', 'content' => mb_substr($question, 0, 1000)];

        $payload = [
            'model' => config('services.anthropic.model'),
            // Thinking counts toward max_tokens, so leave room for it plus the answer.
            'max_tokens' => 8000,
            'output_config' => ['effort' => 'medium'],
            'system' => $this->system(),
            'messages' => $messages,
        ];
        $response = $this->send($payload);
        if ($response->status() === 400 && str_contains((string) $response->body(), 'output_config')) {
            unset($payload['output_config']);
            $response = $this->send($payload);
        }
        if ($response->failed()) {
            throw new RuntimeException((string) ($response->json('error.message') ?: 'Claude request failed ('.$response->status().')'));
        }
        $text = trim((string) collect($response->json('content', []))->where('type', 'text')->pluck('text')->implode("\n"));
        if ($text === '') {
            throw new RuntimeException('Claude returned no text (stop reason: '.($response->json('stop_reason') ?: 'unknown').').');
        }

        return $text;
    }

    private function system(): string
    {
        return <<<'TXT'
You are the data analyst inside the admin dashboard of Ozman (ozman.online), a local multi-shop marketplace in Palestine
(restaurants, cosmetics, electronics, clothing, sweets, furniture, services). The reader is the marketplace owner.

Rules:
- Answer in Arabic, in a friendly Palestinian/Levantine dialect, short and direct. Money is in shekels (₪).
- Use ONLY the numbers in the JSON. Never invent figures, shops or causes. If something can't be known from the data, say so
  briefly and say which data would answer it. Small samples (a few orders) are not trends: say that.
- "sales"/"revenue" = confirmed sales (completed/delivered). "orders" = orders placed. Visitors = unique browsers.
- Be practical: name the shop, the number, and one concrete action (call the owner, run an offer at the peak hour,
  fix photos/prices, speed the kitchen, activate notifications/wheel, post on social at peak times...).
- Format: short sections with "## " headings, "- " bullets and **bold** for key numbers. No tables, no code, no links.
- For a full analysis use these sections: ## الخلاصة (2-3 lines) · ## الإشي المنيح · ## المشاكل · ## شو تعمل هالأسبوع (3-5 numbered steps, most important first).
- For a specific question, answer just that question in a few lines.
TXT;
    }

    /** A compact copy of the report without customer contact details. */
    private function context(array $report): string
    {
        $data = Arr::only($report, ['period', 'summary', 'shops', 'top_products', 'statuses', 'chart', 'insights']);
        $data['shops'] = collect($data['shops'] ?? [])->sortByDesc('revenue')->take(40)->values()->all();
        $data['chart'] = collect($data['chart'] ?? [])->map(fn ($d) => Arr::only((array) $d, ['day', 'revenue', 'sales']))->all();
        if (isset($data['insights']['customers'])) {
            // Names only; phone numbers never leave the server.
            $data['insights']['customers'] = collect($data['insights']['customers'])
                ->map(fn ($c) => Arr::except((array) $c, ['phone', 'whatsapp']))->all();
        }
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);

        return mb_substr((string) $json, 0, 60000);
    }

    private function send(array $payload)
    {
        return Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
        ])->acceptJson()->timeout(90)->post('https://api.anthropic.com/v1/messages', $payload);
    }
}
