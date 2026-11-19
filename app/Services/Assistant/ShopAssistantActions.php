<?php

namespace App\Services\Assistant;

use App\Http\Controllers\{RestaurantController, ShopOrdersController};
use App\Models\{AssistantAction, FrontOrder, Product, User};
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Runs a change the assistant proposed, once the user confirms it. Order statuses go through the dashboards' own code. */
class ShopAssistantActions
{
    /** @return string what happened, shown to the user */
    public function confirm(AssistantAction $action, User $user, Request $request): string
    {
        if ($action->status !== AssistantAction::PENDING) {
            return __('هاد التغيير مش مستني تأكيد.');
        }
        if ($action->isStale()) {
            $action->update(['status' => AssistantAction::EXPIRED, 'decided_at' => now()]);

            return __('مرّ وقت طويل على هاد الاقتراح. اطلبه من المساعد مرة ثانية عشان يعتمد على الأرقام الحالية.');
        }
        // Claim it first, so a double tap can't run it twice.
        $claimed = AssistantAction::whereKey($action->id)->where('status', AssistantAction::PENDING)->update(['status' => 'running', 'decided_at' => now()]);
        if (! $claimed) {
            return __('هاد التغيير مش مستني تأكيد.');
        }
        try {
            $result = match ($action->type) {
                'product_change' => $this->product($action),
                'order_status' => $this->orderStatus($action, $user, $request),
                'accepting_orders' => $this->accepting($action),
                'coupon_create' => $this->coupon($action, $user),
                'review_reply' => $this->reviewReply($action, $user),
                'social_post' => $this->socialPost($action, $user),
                default => throw new \InvalidArgumentException('Unknown action.'),
            };
            $action->update(['status' => AssistantAction::DONE, 'result' => $result]);

            return $result;
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?: __('ما زبط التغيير.');
        } catch (HttpException $e) {
            $message = $e->getMessage() ?: __('ما عندك صلاحية لهاد التغيير.');
        } catch (\InvalidArgumentException $e) {
            $message = $e->getMessage();
        }
        $action->update(['status' => AssistantAction::FAILED, 'result' => mb_substr($message, 0, 490)]);

        return $message;
    }

    public function cancel(AssistantAction $action): void
    {
        AssistantAction::whereKey($action->id)->where('status', AssistantAction::PENDING)
            ->update(['status' => AssistantAction::CANCELLED, 'decided_at' => now()]);
    }

    private function product(AssistantAction $action): string
    {
        $data = $action->payload;
        $product = Product::where('shop_id', $action->shop_id)->find($data['product_id'] ?? 0);
        if (! $product) {
            throw new \InvalidArgumentException(__('المنتج مش موجود.'));
        }
        $changes = [];
        if (array_key_exists('price', $data)) {
            $changes['price'] = round(max(0, (float) $data['price']), 2);
        }
        if (array_key_exists('discount_price', $data)) {
            $changes['discount_price'] = (float) $data['discount_price'] > 0 ? round((float) $data['discount_price'], 2) : null;
        }
        if (array_key_exists('is_active', $data)) {
            $changes['is_active'] = (bool) $data['is_active'];
        }
        $price = $changes['price'] ?? (float) $product->price;
        $discount = array_key_exists('discount_price', $changes) ? $changes['discount_price'] : $product->discount_price;
        if ($discount !== null && (float) $discount > 0 && (float) $discount >= $price) {
            throw new \InvalidArgumentException(__('سعر الخصم لازم يكون أقل من السعر.'));
        }
        $product->update($changes);

        return __('تم: :summary', ['summary' => $action->summary]);
    }

    private function orderStatus(AssistantAction $action, User $user, Request $request): string
    {
        $order = FrontOrder::with('shop')->where('shop_id', $action->shop_id)->find($action->payload['order_id'] ?? 0);
        if (! $order || ! $order->shop) {
            throw new \InvalidArgumentException(__('الطلب مش موجود.'));
        }
        $restaurant = filled($order->order_type);
        if (! $user->isSuperAdmin() && ! $user->canAccessRouteName($restaurant ? 'restaurant.orders.status' : 'shop-orders.status')) {
            throw new \InvalidArgumentException(__('حسابك ما إله صلاحية يغيّر حالة الطلبات.'));
        }
        // Same request the dashboard sends, so customer notifications, drivers and timers behave the same.
        $sub = Request::create('/assistant/order-status', 'PATCH', array_filter([
            'status' => $action->payload['status'],
            'estimated_preparation_minutes' => $action->payload['estimated_preparation_minutes'] ?? null,
        ]));
        $sub->headers->set('Accept', 'application/json');
        $sub->setUserResolver(fn () => $user);
        if ($request->hasSession()) {
            $sub->setLaravelSession($request->session());
        }
        $restaurant
            ? app()->call([app(RestaurantController::class), 'status'], ['request' => $sub, 'order' => $order])
            : app()->call([app(ShopOrdersController::class), 'status'], ['request' => $sub, 'order' => $order]);

        return __('تم: :summary', ['summary' => $action->summary]);
    }

    private function accepting(AssistantAction $action): string
    {
        $open = (bool) ($action->payload['open'] ?? true);
        $action->shop->update(['is_accepting_orders' => $open]);

        return $open ? __('تم: المحل رجع يستقبل طلبات.') : __('تم: وقّفنا استقبال الطلبات مؤقتاً.');
    }

    private function coupon(AssistantAction $action, User $user): string
    {
        // Through the app's own coupon screen code, so the same rules and checks apply.
        $this->callApi($user, \App\Http\Controllers\Api\V1\CouponController::class, 'store', ['shop' => $action->shop], $action->payload, 'POST');

        return __('تم: :summary', ['summary' => $action->summary]);
    }

    private function reviewReply(AssistantAction $action, User $user): string
    {
        $review = \App\Models\ShopReview::where('shop_id', $action->shop_id)->find($action->payload['review_id'] ?? 0);
        if (! $review) {
            throw new \InvalidArgumentException(__('التقييم مش موجود.'));
        }
        $this->callApi($user, \App\Http\Controllers\Api\V1\ReviewController::class, 'reply', ['review' => $review], ['reply' => $action->payload['reply'] ?? ''], 'POST');

        return __('تم: انحفظ الرد على التقييم.');
    }

    private function socialPost(AssistantAction $action, User $user): string
    {
        $data = $action->payload;
        $files = [];
        if (! empty($data['photo'])) {
            $path = \Illuminate\Support\Facades\Storage::disk('local')->path($data['photo']);
            if (! is_file($path)) {
                throw new \InvalidArgumentException(__('الصورة مش موجودة، ابعتها للمساعد مرة ثانية.'));
            }
            $files['media'] = [new \Illuminate\Http\UploadedFile($path, basename($path), mime_content_type($path) ?: null, null, true)];
        }
        // Through the Social media screen's own code: the same account checks, picture rules and scheduling.
        $sub = Request::create('/assistant/social', 'POST', array_filter([
            'caption' => $data['caption'] ?? '', 'accounts' => $data['accounts'] ?? [], 'product_id' => $data['product_id'] ?? null,
            'add_link' => ! empty($data['add_link']) ? 1 : 0,
            'when' => empty($data['scheduled_at']) ? 'now' : 'schedule', 'scheduled_at' => $data['scheduled_at'] ?? null,
        ], fn ($v) => $v !== null), [], $files);
        $sub->headers->set('Accept', 'application/json');
        $sub->setUserResolver(fn () => $user);
        $response = app()->call([app(\App\Http\Controllers\Api\V1\SocialController::class), 'store'], ['request' => $sub, 'shop' => $action->shop]);
        if ($response->getStatusCode() >= 300) {
            throw new \InvalidArgumentException($response->getData(true)['message'] ?? __('ما انتشر المنشور.'));
        }

        return $response->getData(true)['message'] ?? __('تم: :summary', ['summary' => $action->summary]);
    }

    private function callApi(User $user, string $controller, string $method, array $params, array $data, string $verb): void
    {
        $sub = Request::create('/assistant/action', $verb, $data);
        $sub->headers->set('Accept', 'application/json');
        $sub->setUserResolver(fn () => $user);
        app()->call([app($controller), $method], ['request' => $sub] + $params);
    }
}
