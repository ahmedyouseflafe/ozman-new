<?php

namespace App\Console\Commands;

use App\Models\{AssistantConversation, Shop};
use App\Services\Assistant\ShopAssistant;
use Illuminate\Console\Command;

/** Asks the shop owner's assistant one question against the real Claude API, to check it works on a server. */
class AssistantCheck extends Command
{
    protected $signature = 'ozman:assistant-check {question=شو أكثر 3 منتجات عندي وأسعارها؟} {--shop=demo-clothing} {--force : run outside staging/local} {--keep : keep the conversation, so it shows in the owner\'s app}';

    protected $description = 'Ask the shop assistant one question and print the answer (the conversation is deleted afterwards).';

    public function handle(ShopAssistant $assistant): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to run on production without --force.');

            return self::FAILURE;
        }
        if (! $assistant->enabled()) {
            $this->error('The assistant is not enabled (no ANTHROPIC_API_KEY, or SHOP_ASSISTANT_ENABLED=false).');

            return self::FAILURE;
        }
        $shop = Shop::where('slug', $this->option('shop'))->first();
        if (! $shop || ! $shop->user) {
            $this->error('Shop not found or has no owner: '.$this->option('shop'));

            return self::FAILURE;
        }
        $conversation = AssistantConversation::create(['kind' => 'shop_owner', 'shop_id' => $shop->id, 'user_id' => $shop->user->id, 'title' => 'server check']);
        $started = microtime(true);
        try {
            $message = $assistant->reply($conversation, $shop, $shop->user, (string) $this->argument('question'));
            $this->line('Shop: '.$shop->name);
            $this->line('Tools: '.(implode(', ', $message->tools ?? []) ?: '-'));
            $this->line(sprintf('Tokens: %d in / %d out · %.1fs', $message->input_tokens, $message->output_tokens, microtime(true) - $started));
            $this->line('Proposals: '.$message->actions()->count());
            $this->newLine();
            $this->line($message->content);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            if (! $this->option('keep')) {
                $conversation->delete();
            }
        }
    }
}
