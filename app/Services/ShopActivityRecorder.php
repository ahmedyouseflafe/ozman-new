<?php

namespace App\Services;

use App\Models\FrontOrder;
use App\Models\Product;
use App\Models\Shop;
use App\Models\VisitorRegistration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShopActivityRecorder
{
    private ?bool $available = null;

    public function available(): bool
    {
        return $this->available ??= Schema::hasTable('shop_analytics_state');
    }

    public function record(?int $shopId, string $kind, string $title, array $data = [], ?Model $subject = null, ?string $key = null): void
    {
        if (! $this->available()) return;
        DB::table('shop_activity_events')->insertOrIgnore([
            'shop_id' => $shopId, 'actor_id' => auth()->id(), 'kind' => $kind,
            'title' => mb_substr($title, 0, 255), 'event_key' => $key,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(), 'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
    }

    public function saving(Model $model): void
    {
        if ($model instanceof FrontOrder && in_array($model->status, ['completed', 'delivered'], true)
            && ! $model->sale_confirmed_at && $this->available()) {
            $model->sale_confirmed_at = now();
        }
    }

    public function created(Model $model): void
    {
        if ($model instanceof FrontOrder) {
            $this->record($model->shop_id, 'order_created', 'طلب جديد '.$model->order_number,
                ['total' => $model->total, 'status' => $model->status], $model);
            $this->sale($model);
        } elseif ($model instanceof Product) {
            $this->record($model->shop_id, 'product_created', 'إضافة منتج: '.$model->name, [], $model);
        } elseif ($model instanceof VisitorRegistration) {
            $this->record($model->shop_id, 'customer_registered', 'تسجيل بيانات عميل جديد', ['type' => $model->type], $model);
        } elseif ($model instanceof Shop) {
            $this->record($model->id, 'shop_created', 'إضافة متجر: '.$model->name, [], $model);
        } else {
            $this->content($model, 'إضافة');
        }
    }

    public function updated(Model $model): void
    {
        if ($model instanceof FrontOrder && $model->wasChanged('status')) {
            $wasSale = in_array($model->getOriginal('status'), ['completed', 'delivered'], true);
            $isSale = in_array($model->status, ['completed', 'delivered'], true);
            $kind = $wasSale && ! $isSale ? 'sale_reversed' : ($model->status === 'cancelled' ? 'order_cancelled' : 'order_status');
            $this->record($model->shop_id, $kind, 'الطلب '.$model->order_number.': '.$model->statusLabel(),
                ['from' => $model->getOriginal('status'), 'to' => $model->status, 'total' => $model->total], $model);
            $this->sale($model);
        } elseif ($model instanceof FrontOrder && $model->wasChanged(['total', 'discount', 'payment_status'])) {
            $this->record($model->shop_id, 'order_updated', 'تعديل بيانات الطلب '.$model->order_number,
                ['total' => $model->total, 'discount' => $model->discount, 'payment_status' => $model->payment_status], $model);
        } elseif ($model instanceof Product && $model->wasChanged(['name', 'price', 'discount_price', 'quantity', 'is_active', 'category_id'])) {
            $changes = [];
            foreach (['name', 'price', 'discount_price', 'quantity', 'is_active', 'category_id'] as $field) {
                if ($model->wasChanged($field)) $changes[$field] = ['from' => $model->getOriginal($field), 'to' => $model->$field];
            }
            $this->record($model->shop_id, 'product_updated', 'تعديل منتج: '.$model->name, $changes, $model);
        } elseif ($model instanceof Shop && $model->wasChanged(['name', 'is_active', 'is_accepting_orders'])) {
            $this->record($model->id, 'shop_updated', 'تعديل متجر: '.$model->name,
                ['active' => $model->is_active, 'accepting_orders' => $model->is_accepting_orders], $model);
        } elseif (array_diff(array_keys($model->getChanges()), ['updated_at'])) {
            $this->content($model, 'تعديل');
        }
    }

    public function deleted(Model $model): void
    {
        if ($model instanceof Product) $this->record($model->shop_id, 'product_deleted', 'حذف منتج: '.$model->name, [], $model);
        else $this->content($model, 'حذف');
    }

    private function content(Model $model, string $action): void
    {
        $label = match (class_basename($model)) {
            'MainScreen' => 'شاشة', 'Advertisement' => 'إعلان', 'Category' => 'قسم', default => null,
        };
        if ($label) $this->record($model->shop_id, 'content_updated', $action.' '.$label.': '.($model->title ?? $model->name), [], $model);
    }

    private function sale(FrontOrder $order): void
    {
        if (in_array($order->status, ['completed', 'delivered'], true)) {
            $this->record($order->shop_id, 'sale_confirmed', 'بيع مؤكد • '.$order->order_number,
                ['total' => $order->total, 'items' => collect($order->items)->map(fn ($item) => [
                    'name' => $item['name'] ?? '', 'qty' => $item['qty'] ?? $item['quantity'] ?? 1,
                ])->all()], $order, 'sale:'.$order->id);
        }
    }
}
