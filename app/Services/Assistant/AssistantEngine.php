<?php

namespace App\Services\Assistant;

use App\Models\AssistantConversation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** The Claude conversation loop shared by the assistants: history, tool calls and the final answer. */
class AssistantEngine
{
    public const MAX_STEPS = 6;

    /** Earlier turns of a conversation, as strictly alternating user/assistant messages that end with the assistant. */
    public function history(AssistantConversation $conversation, int $limit = 12): array
    {
        $messages = [];
        foreach ($conversation->messages()->latest('id')->limit($limit)->get()->reverse() as $turn) {
            // Keep strict alternation even if an earlier turn was lost.
            if ($messages && end($messages)['role'] === $turn->role) {
                array_pop($messages);
            }
            $messages[] = ['role' => $turn->role, 'content' => mb_substr($turn->content, 0, 6000)];
        }
        if ($messages && $messages[0]['role'] === 'assistant') {
            array_shift($messages);
        }
        if ($messages && end($messages)['role'] === 'user') {
            array_pop($messages);
        }

        return $messages;
    }

    public function userTurn(string $text, ?UploadedFile $image = null): array
    {
        $content = [];
        if ($image) {
            $content[] = ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $image->getMimeType(),
                'data' => base64_encode((string) file_get_contents($image->getRealPath()))]];
        }
        $content[] = ['type' => 'text', 'text' => mb_substr($text, 0, 2000)];

        return ['role' => 'user', 'content' => $content];
    }

    /**
     * Runs the conversation until Claude answers without calling a tool.
     *
     * @param  callable(string $name, array $input): array  $runTool
     * @return array{text: string, tools: string[], input_tokens: int, output_tokens: int}
     */
    public function run(string $model, string $system, array $tools, array $messages, callable $runTool, int $maxTokens = 2500): array
    {
        $used = [];
        $usage = ['in' => 0, 'out' => 0];
        $response = [];
        for ($step = 0; $step < self::MAX_STEPS; $step++) {
            $payload = ['model' => $model, 'max_tokens' => $maxTokens, 'system' => $system, 'tools' => $tools, 'messages' => $messages];
            if ($step === self::MAX_STEPS - 1) {
                $payload['tool_choice'] = ['type' => 'none'];
            }
            $response = $this->send($payload);
            $usage['in'] += (int) ($response['usage']['input_tokens'] ?? 0);
            $usage['out'] += (int) ($response['usage']['output_tokens'] ?? 0);
            if (($response['stop_reason'] ?? null) !== 'tool_use') {
                break;
            }
            // PHP decodes an empty JSON object as []; tool inputs must go back as objects.
            $messages[] = ['role' => 'assistant', 'content' => array_map(
                fn ($block) => ($block['type'] ?? null) === 'tool_use' ? ['input' => (object) ($block['input'] ?? [])] + $block : $block,
                $response['content'],
            )];
            $results = [];
            foreach ($response['content'] as $block) {
                if (($block['type'] ?? null) !== 'tool_use') {
                    continue;
                }
                $used[] = $block['name'];
                $result = $runTool($block['name'], (array) ($block['input'] ?? []));
                $json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
                $results[] = ['type' => 'tool_result', 'tool_use_id' => $block['id'], 'content' => mb_substr((string) $json, 0, 30000),
                    'is_error' => isset($result['error'])];
            }
            $messages[] = ['role' => 'user', 'content' => $results];
        }

        return [
            'text' => trim((string) collect($response['content'] ?? [])->where('type', 'text')->pluck('text')->implode("\n")),
            'tools' => array_values(array_unique($used)),
            'input_tokens' => $usage['in'],
            'output_tokens' => $usage['out'],
        ];
    }

    private function send(array $payload): array
    {
        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
        ])->acceptJson()->timeout(90)->post('https://api.anthropic.com/v1/messages', $payload);
        if ($response->failed()) {
            throw new RuntimeException((string) ($response->json('error.message') ?: 'Claude request failed ('.$response->status().')'));
        }

        return (array) $response->json();
    }
}
