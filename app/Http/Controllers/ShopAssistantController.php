<?php

namespace App\Http\Controllers;

use App\Models\{AssistantAction, AssistantConversation, AssistantMessage, Shop, User};
use App\Services\Assistant\{ShopAssistant, ShopAssistantActions};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\View\View;

/** The shop owner's assistant. The same JSON endpoints serve the dashboard page and the app. */
class ShopAssistantController extends Controller
{
    public function __construct(private ShopAssistant $assistant, private ShopAssistantActions $actions) {}

    public function page(Request $request, Shop $shop): View
    {
        $this->authorizeShop($request->user(), $shop);

        return view('admin.assistant.index', ['shop' => $shop, 'state' => $this->state($request, $shop)]);
    }

    public function show(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeShop($request->user(), $shop);

        return response()->json($this->state($request, $shop), 200, ['Cache-Control' => 'no-store, private']);
    }

    public function send(Request $request, Shop $shop): JsonResponse
    {
        $user = $request->user();
        $this->authorizeShop($user, $shop);
        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:2000', 'required_without:image'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'conversation_id' => ['nullable', 'integer'],
        ]);
        if (! $this->assistant->enabled()) {
            return response()->json(['message' => __('المساعد مش مفعّل هلقيت.')], 503);
        }
        if ($this->assistant->usedThisMonth($shop) >= $this->assistant->monthlyLimit()) {
            return response()->json(['message' => __('خلصت أسئلة المساعد لهالشهر (:limit سؤال). بترجع أول الشهر الجاي.', ['limit' => $this->assistant->monthlyLimit()])], 429);
        }
        $conversation = $this->assistant->conversation($shop, $user, $data['conversation_id'] ?? null);
        try {
            $message = $this->assistant->reply($conversation, $shop, $user, (string) ($data['message'] ?? ''), $request->file('image'));
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => __('المساعد ما قدر يرد هلقيت، جرّب كمان شوي.')], 502);
        }

        return response()->json([
            'conversation_id' => $conversation->id,
            'message' => $this->present($message->load('actions')),
            'usage' => $this->usage($shop),
        ], 200, ['Cache-Control' => 'no-store, private']);
    }

    /** Starts a fresh conversation (the old one stays stored). */
    public function reset(Request $request, Shop $shop): JsonResponse
    {
        $user = $request->user();
        $this->authorizeShop($user, $shop);
        $conversation = AssistantConversation::create(['kind' => 'shop_owner', 'shop_id' => $shop->id, 'user_id' => $user->id]);

        return response()->json(['conversation_id' => $conversation->id, 'messages' => [], 'usage' => $this->usage($shop)]);
    }

    public function confirm(Request $request, AssistantAction $action): JsonResponse
    {
        $user = $this->authorizeAction($request, $action);
        $wasPending = $action->status === AssistantAction::PENDING;
        $result = $this->actions->confirm($action, $user, $request);
        $action = $action->fresh();

        return response()->json(['action' => $this->action($action), 'message' => $result],
            $wasPending && $action->status === AssistantAction::DONE ? 200 : 422);
    }

    public function cancel(Request $request, AssistantAction $action): JsonResponse
    {
        $this->authorizeAction($request, $action);
        $this->actions->cancel($action);

        return response()->json(['action' => $this->action($action->fresh())]);
    }

    private function state(Request $request, Shop $shop): array
    {
        $conversation = AssistantConversation::query()->where('kind', 'shop_owner')->where('shop_id', $shop->id)
            ->where('user_id', $request->user()->id)->latest('id')->first();
        $messages = $conversation
            ? $conversation->messages()->with('actions')->latest('id')->limit(40)->get()->reverse()->values()
            : collect();

        return [
            'enabled' => $this->assistant->enabled(),
            'shop' => ['id' => $shop->id, 'name' => $shop->name, 'type' => $shop->catalog_type],
            'conversation_id' => $conversation?->id,
            'messages' => $messages->map(fn ($m) => $this->present($m))->all(),
            'usage' => $this->usage($shop),
            'suggestions' => $this->suggestions($shop),
        ];
    }

    private function present(AssistantMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'has_image' => $message->has_image,
            'created_at' => $message->created_at?->toIso8601String(),
            'actions' => $message->role === 'assistant' ? $message->actions->map(fn ($a) => $this->action($a))->values()->all() : [],
        ];
    }

    private function action(AssistantAction $action): array
    {
        $status = $action->status === AssistantAction::PENDING && $action->isStale() ? AssistantAction::EXPIRED : $action->status;

        return ['id' => $action->id, 'type' => $action->type, 'summary' => $action->summary, 'status' => $status, 'result' => $action->result];
    }

    private function usage(Shop $shop): array
    {
        return ['used' => $this->assistant->usedThisMonth($shop), 'limit' => $this->assistant->monthlyLimit()];
    }

    private function suggestions(Shop $shop): array
    {
        $common = [__('كيف كان البيع هالأسبوع مقارنة بالأسبوع الماضي؟'), __('شو أكثر 5 منتجات بتنباع؟'), __('مين الزباين اللي ما رجعوا يطلبوا من شهر؟')];
        $byType = match ($shop->catalog_type) {
            'restaurant' => [__('في طلبات مستنية من أكثر من 20 دقيقة؟'), __('اكتبلي منشور إنستغرام لعرض الغدا')],
            'cosmetics' => [__('شو مواعيد الصالون اليوم؟'), __('اكتبلي منشور لعرض نهاية الأسبوع')],
            'real_estate' => [__('اكتبلي إعلان لشقة 3 غرف للإيجار')],
            default => [__('شو المنتجات اللي ما انباعت هالشهر؟'), __('اكتبلي منشور لعرض جديد')],
        };

        return array_merge($common, $byType);
    }

    private function authorizeShop(?User $user, Shop $shop): void
    {
        abort_unless($user && ($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true)), 403);
    }

    private function authorizeAction(Request $request, AssistantAction $action): User
    {
        $user = $request->user();
        // Only the person who asked confirms; the shop check covers access removed since then.
        abort_unless($user && $action->user_id === $user->id, 404);
        $this->authorizeShop($user, $action->shop);

        return $user;
    }
}
