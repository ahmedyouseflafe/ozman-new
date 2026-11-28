<?php

namespace App\Http\Controllers;

use App\Models\{AssistantConversation, AssistantMessage, Shop, AssistantAction};
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
            // The website widget posts with this; storefront pages may be cached, so it isn't baked into them.
            'csrf' => $request->hasSession() ? csrf_token() : null,
        ], 200, ['Cache-Control' => 'no-store, private']);
    }

    public function send(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'conversation_id' => ['nullable', 'integer'],
            'shop_id' => ['nullable', 'integer'],
            // "robot": spoken to Fulla the robot in the app.
            'via' => ['nullable', 'string', 'in:chat,robot'],
            // With "robot": what is in the app's basket now, so she can send part of it.
            'basket' => ['nullable', 'array', 'max:30'],
            'basket.*.key' => ['required_with:basket', 'string', 'regex:/^L\d{1,2}$/'],
            'basket.*.name' => ['required_with:basket', 'string', 'max:120'],
            'basket.*.qty' => ['required_with:basket', 'integer', 'min:1', 'max:99'],
            'basket.*.shop' => ['required_with:basket', 'string', 'max:120'],
            'basket.*.price' => ['nullable', 'numeric', 'min:0'],
            'basket.*.options' => ['nullable', 'string', 'max:160'],
            // With "robot": the page the customer is looking at in the app.
            'screen' => ['nullable', 'string', 'max:200'],
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
            $basket = collect($data['basket'] ?? [])->keyBy('key')->map(fn ($line) => [
                'name' => $line['name'], 'qty' => (int) $line['qty'], 'shop' => $line['shop'],
                'price' => (float) ($line['price'] ?? 0), 'options' => (string) ($line['options'] ?? ''),
            ])->all();
            $message = $this->assistant->reply($conversation, $user, $data['message'], $this->shop($request), ($data['via'] ?? null) === 'robot', $basket, (string) ($data['screen'] ?? ''));
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => __('المساعد ما قدر يرد هلقيت، جرّب كمان شوي.')], 502);
        }

        return response()->json(['conversation_id' => $conversation->id, 'message' => $this->present($message), 'usage' => $this->usage($request)],
            200, ['Cache-Control' => 'no-store, private']);
    }

    /** The customer presses "book" under a booking the assistant prepared. */
    public function confirm(Request $request, AssistantAction $action): JsonResponse
    {
        $user = $request->user();
        abort_unless($action->user_id === $user->id && $action->type === 'salon_booking'
            && $action->conversation?->kind === 'customer', 404);
        if ($action->status !== AssistantAction::PENDING || $action->isStale()) {
            if ($action->status === AssistantAction::PENDING) {
                $action->update(['status' => AssistantAction::EXPIRED, 'decided_at' => now()]);
            }

            return response()->json(['action' => CustomerAssistantTools::presentAction($action->fresh()),
                'message' => __('هاد الحجز مش مستني تأكيد. اطلبه من المساعد مرة ثانية.')], 422);
        }
        // Claimed first, so a double tap can't book twice.
        if (! AssistantAction::whereKey($action->id)->where('status', AssistantAction::PENDING)->update(['status' => 'running', 'decided_at' => now()])) {
            return response()->json(['action' => CustomerAssistantTools::presentAction($action->fresh()), 'message' => __('هاد الحجز مش مستني تأكيد.')], 422);
        }
        $payload = $action->payload;
        // The booking form's own code, with the name and phone on the account.
        $sub = Request::create('/assistant/booking', 'POST', array_filter([
            'service' => $payload['service'] ?? null, 'date' => $payload['date'] ?? null, 'time' => $payload['time'] ?? null,
            'name' => $user->name, 'phone' => $user->phone, 'notes' => $payload['notes'] ?? null,
        ], fn ($v) => $v !== null && $v !== ''));
        $sub->headers->set('Accept', 'application/json');
        $sub->setUserResolver(fn () => $user);
        try {
            $response = app()->call([app(\App\Http\Controllers\Api\V1\SalonController::class), 'store'], ['request' => $sub, 'shop' => $action->shop]);
            $ok = $response->getStatusCode() === 201;
            $message = $ok ? __('تم الحجز. الصالون رح يأكد معك، والموعد صار بـ«مواعيدي».') : ($response->getData(true)['message'] ?? __('ما زبط الحجز.'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            $ok = false;
            $message = collect($e->errors())->flatten()->first() ?: __('ما زبط الحجز.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            // The salon closed or stopped booking since the proposal: say so instead of leaving it half-done.
            $ok = false;
            $message = __('الصالون مش مستقبل حجوزات هلقيت.');
        }
        $action->update(['status' => $ok ? AssistantAction::DONE : AssistantAction::FAILED, 'result' => mb_substr($message, 0, 490)]);

        return response()->json(['action' => CustomerAssistantTools::presentAction($action->fresh()), 'message' => $message], $ok ? 200 : 422);
    }

    public function cancel(Request $request, AssistantAction $action): JsonResponse
    {
        abort_unless($action->user_id === $request->user()->id && $action->type === 'salon_booking', 404);
        AssistantAction::whereKey($action->id)->where('status', AssistantAction::PENDING)->update(['status' => AssistantAction::CANCELLED, 'decided_at' => now()]);

        return response()->json(['action' => CustomerAssistantTools::presentAction($action->fresh())]);
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
        ] + ($message->role === 'assistant' ? CustomerAssistantTools::cardsFor($message->attachments) : ['products' => [], 'shops' => [], 'actions' => [], 'basket' => null, 'guide' => [], 'open' => null, 'send' => []]);
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
