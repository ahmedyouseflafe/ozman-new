<?php

namespace Tests\Unit;

use App\Services\Assistant\AssistantEngine;
use PHPUnit\Framework\TestCase;

/** The tools and the conversation so far are marked for Anthropic's prompt cache, so replies come sooner. */
class AssistantPromptCacheTest extends TestCase
{
    public function test_the_last_tool_and_the_newest_message_are_marked(): void
    {
        $payload = AssistantEngine::cached([
            'tools' => [['name' => 'a'], ['name' => 'b']],
            'messages' => [['role' => 'user', 'content' => 'أهلين'], ['role' => 'assistant', 'content' => 'هلا'], ['role' => 'user', 'content' => 'شو في؟']],
        ]);

        $this->assertArrayNotHasKey('cache_control', $payload['tools'][0]);
        $this->assertSame(['type' => 'ephemeral'], $payload['tools'][1]['cache_control']);
        $this->assertSame('أهلين', $payload['messages'][0]['content']);
        $this->assertSame([['type' => 'text', 'text' => 'شو في؟', 'cache_control' => ['type' => 'ephemeral']]], $payload['messages'][2]['content']);

        // Tool results: the last block carries the mark.
        $payload = AssistantEngine::cached(['tools' => [], 'messages' => [['role' => 'user', 'content' => [
            ['type' => 'tool_result', 'tool_use_id' => 'x', 'content' => '{}'], ['type' => 'tool_result', 'tool_use_id' => 'y', 'content' => '{}'],
        ]]]]);
        $this->assertArrayNotHasKey('cache_control', $payload['messages'][0]['content'][0]);
        $this->assertSame(['type' => 'ephemeral'], $payload['messages'][0]['content'][1]['cache_control']);
    }
}
