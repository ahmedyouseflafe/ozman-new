@php($purchaseReward = json_decode($order->purchase_reward ?? 'null', true))
@if($purchaseReward)
    <div style="padding:10px;border:1px solid #51e4b0;border-radius:10px;color:#51e4b0;margin:8px 0">
        🎁 جائزة عجلة الشراء: <strong>{{ $purchaseReward['label'] }}</strong>
        @if(($purchaseReward['discount_type'] ?? '') !== 'gift')
            <small>— تُراجع مع المطعم؛ غير مخصومة من إجمالي الطلب المعروض.</small>
        @endif
    </div>
@endif
