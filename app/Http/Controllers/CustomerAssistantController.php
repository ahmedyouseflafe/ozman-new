<?php

namespace App\Http\Controllers;

use App\Models\{AssistantConversation, AssistantMessage, Shop};
use App\Services\Assistant\{CustomerAssistant, CustomerAssistantTools};
use Illuminate\Http\{JsonResponse, Request};

/** The customers' shopping assistant. The same JSON endpoints serve the website widget and the app. */
class CustomerAssistantController extends Controller
{
    public function __construct(private CustomerAssistant $assistant) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $conversation = AssistantConversation::query()->where('kind', 'customer')->where('user_id', $user->id)->latest('id')->first();
        $messages = $conversation ? $conversation->messages()->latest('id')->limit(30)->get()->reverse()->values() : collect();
        $shop = $this->shop($request);

        return response()->json([
            'enabled' => $this->assistant->enabled(),
            'conversation_id' => $conversation?->id,
            'messages' => $messages->map(fn ($m) => $this->present($m))->all(),
            'usage' => $this->usage($request),
            'suggestions' => $this->suggestions($shop),
        ], 200, ['Cache-Control' => 'no-store, private']);
    }

    public function send(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'conversation_id' => ['nullable', 'integer'],
            'shop_id' => ['nullable', 'integer'],
        ]);
        if (! $this->assistant->enabled()) {
            return response()->json(['message' => __('المساعد مش مفعّل هلقيت.')], 503);
        }
        if ($this->assistant->usedToday($user) >= $this->assistant->dailyLimit()) {
            return response()->json(['message' => __('وصلت حد أسئلة المساعد لليوم. بترجع بكرة 🙏')], 429);
        }
        if ($this->assistant->siteBusyToday()) {
            return response()->json(['message' => __('المساعد مشغول كثير اليوم، جرّب بكرة.')], 429);
        }
        $conversation = $this->assistant->conversation($user, $data['conversation_id'] ?? null);
        try {
            $message = $this->assistant->reply($conversation, $user, $data['message'], $this->shop($request));
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => __('المساعد ما قدر يرد هلقيت، جرّب كمان شوي.')], 502);
        }

        return response()->json(['conversation_id' => $conversation->id, 'message' => $this->present($message), 'usage' => $this->usage($request)],
            200, ['Cache-Control' => 'no-store, private']);
    }

    public function reset(Request $request): JsonResponse
    {
        $conversation = AssistantConversation::create(['kind' => 'customer', 'user_id' => $request->user()->id]);

        return response()->json(['conversation_id' => $conversation->id, 'messages' => [], 'usage' => $this->usage($request)]);
    }

    private function present(AssistantMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'created_at' => $message->created_at?->toIso8601String(),
        ] + ($message->role === 'assistant' ? CustomerAssistantTools::cardsFor($message->attachments) : ['products' => [], 'shops' => []]);
    }

    private function usage(Request $request): array
    {
        return ['used' => $this->assistant->usedToday($request->user()), 'limit' => $this->assistant->dailyLimit()];
    }

    private function shop(Request $request): ?Shop
    {
        $id = $request->integer('shop_id');

        return $id ? Shop::where('is_active', true)->find($id) : null;
    }

    private function suggestions(?Shop $shop): array
    {
        if ($shop) {
            return array_values(array_filter([
                __('شو بتنصحني من هالمحل؟'), __('في عروض هلقيت؟'), __('هل المحل مفتوح هلقيت؟'),
                $shop->catalog_type === 'restaurant' ? __('شو أكثر وجبة بيطلبوها الناس؟') : null,
            ]));
        }

        return [__('بدي إشي آكله سريع وتحت 40 شيكل'), __('هدية لأختي تحت 150 شيكل'), __('وين طلبي؟'), __('كم نقطة عندي؟'), __('شو العروض اليوم؟')];
    }
}
