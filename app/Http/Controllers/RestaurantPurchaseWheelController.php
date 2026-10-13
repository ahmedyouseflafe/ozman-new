<?php
namespace App\Http\Controllers;

use App\Models\{FrontOrder, RewardWheel};
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class RestaurantPurchaseWheelController extends Controller
{
    public static function eligible(FrontOrder $order): ?RewardWheel
    {
        return RewardWheel::where('shop_id', $order->shop_id)->where('wheel_type', RewardWheel::TYPE_PURCHASE_AMOUNT)
            ->where('is_active', true)->where('min_order_total', '<=', $order->total)
            ->where(fn($q) => $q->whereNull('max_order_total')->orWhere('max_order_total', '>=', $order->total))
            ->whereHas('segments', fn($q) => $q->where('is_active', true), '>=', 2)
            ->with(['segments' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderByDesc('min_order_total')->orderBy('id')->first();
    }

    public function show(FrontOrder $order): JsonResponse
    {
        abort_unless(($order->order_channel === 'restaurant' || ($order->order_channel === 'whatsapp' && $order->shop?->catalog_type === 'cosmetics' && $order->shop?->slug === 'elegance-perfumes')) && $order->status !== 'cancelled', 404);
        $wheel = self::eligible($order);
        return response()->json(['wheel' => $wheel ? ['title' => $wheel->title, 'segments' => $wheel->segments->map(fn($s) => ['label'=>$s->label,'color'=>$s->color])] : null,
            'reward' => $order->purchase_reward ? json_decode($order->purchase_reward, true) : null])->header('Cache-Control', 'private, no-store');
    }

    public function spin(FrontOrder $order): JsonResponse
    {
        return DB::transaction(function () use ($order) {
            $order = FrontOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless(($order->order_channel === 'restaurant' || ($order->order_channel === 'whatsapp' && $order->shop?->catalog_type === 'cosmetics' && $order->shop?->slug === 'elegance-perfumes')) && $order->status !== 'cancelled', 422);
            if ($order->purchase_reward) return response()->json(['reward'=>json_decode($order->purchase_reward,true)]);
            $eligible = self::eligible($order);
            abort_unless($eligible, 422, 'لا توجد عجلة مناسبة لمبلغ الطلب.');
            $wheel = RewardWheel::whereKey($eligible->id)->lockForUpdate()->firstOrFail();
            abort_unless($wheel->is_active && (int)$wheel->shop_id === (int)$order->shop_id
                && $wheel->min_order_total <= $order->total
                && ($wheel->max_order_total === null || $wheel->max_order_total >= $order->total), 422);
            $segments = $wheel->segments()->where('is_active',true)->orderBy('sort_order')->get();
            abort_if($segments->count()<2,422);
            $cycle = collect($wheel->spin_cycle ?? [])->filter(fn($id)=>$segments->contains('id',$id))->values()->all();
            if (!$cycle) foreach ($segments as $segment) {
                for ($i=0;$i<max(0,min((int)($wheel->win_quota_total ?: 200),(int)($segment->win_quota ?? 1)));$i++) $cycle[]=$segment->id;
            }
            abort_if(!$cycle,422,'العجلة غير جاهزة.');
            $index=random_int(0,count($cycle)-1);$segment=$segments->firstWhere('id',$cycle[$index]);array_splice($cycle,$index,1);
            $wheel->update(['spin_cycle'=>$cycle]);
            $reward=['label'=>$segment->label,'discount_type'=>$segment->discount_type,'discount_value'=>$segment->discount_value,
                'gift_image'=>$segment->gift_image ? asset($segment->gift_image) : null,'segment_index'=>$segments->search(fn($s)=>$s->id===$segment->id),'wheel_id'=>$wheel->id];
            $order->forceFill(['purchase_reward'=>json_encode($reward,JSON_UNESCAPED_UNICODE)])->save();
            return response()->json(['reward'=>$reward]);
        });
    }
}
