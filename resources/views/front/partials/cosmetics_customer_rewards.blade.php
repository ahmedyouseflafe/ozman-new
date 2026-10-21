@if($shop->slug === 'elegance-perfumes')
@include('front.partials.shop_signup_wheel')
<div class="cosmetics-purchase-control">
    @include('front.partials.restaurant_purchase_wheel')
</div>
<form id="eleganceCheckoutForm" class="beauty-form" style="margin-bottom:12px">
    <label class="beauty-field" for="beautyCustomerName">الاسم
        <input id="beautyCustomerName" name="customer_name" autocomplete="name" maxlength="120" required>
    </label>
    <label class="beauty-field" for="beautyCustomerPhone">رقم التواصل
        <input id="beautyCustomerPhone" name="customer_phone" type="tel" autocomplete="tel" maxlength="30" required>
    </label>
    <label class="beauty-field" for="beautyCustomerWhatsapp">رقم واتساب
        <input id="beautyCustomerWhatsapp" name="customer_whatsapp" type="tel" maxlength="30" required>
    </label>
    <label class="beauty-field" for="beautyCustomerAddress">العنوان
        <textarea id="beautyCustomerAddress" name="customer_address" autocomplete="street-address" maxlength="500" rows="2" required></textarea>
    </label>
    <div class="elegance-location">
        <span>اللوكيشن (اختياري)</span>
        <button type="button" id="eleganceLocation" class="beauty-form-action beauty-scan-action">إضافة موقعي الحالي</button>
        <button type="button" id="eleganceLocationClear" class="beauty-form-action beauty-scan-action" hidden>إزالة الموقع</button>
        <small id="eleganceLocationStatus" role="status">يمكنك إرسال الطلب بدون إضافة اللوكيشن.</small>
    </div>
    <label id="eleganceSkipRewardLabel" hidden><input type="checkbox" id="eleganceSkipReward"> إكمال الطلب بدون هدية الترحيب</label>
    <p id="eleganceRewardSummary" role="status" style="color:#f7ce82;margin:0"></p>
    <p id="eleganceCheckoutStatus" role="status" aria-live="polite"></p>
    <button type="submit" hidden aria-hidden="true" tabindex="-1">إرسال الطلب</button>
</form>
<style>
.cosmetics-checkout-bar{position:fixed;right:12px;bottom:calc(12px + env(safe-area-inset-bottom));display:flex;align-items:center;gap:12px;z-index:45;max-width:calc(100% - 24px)}
.cosmetics-checkout-bar .beauty-cart{position:static;min-width:0;flex:1}.cosmetics-purchase-control{flex:none}
.beauty-reward-tools{bottom:100px!important}#beautyPurchaseWheelOpen{display:none}
#shopSignupWheel,#purchaseWheelDialog{border-color:#e8c276;background:radial-gradient(circle at top,#493049,#130d1b)}
#eleganceCheckoutForm [hidden]{display:none!important}#eleganceCheckoutForm textarea{box-sizing:border-box;width:100%;border:1px solid #51404f;border-radius:14px;background:#110b15;color:#fff;padding:12px;font:inherit;resize:vertical}
.elegance-location{display:grid;gap:8px;font-size:13px}.elegance-location small{color:#c8b4c5}#eleganceCheckoutStatus{color:#f7ce82;font-size:13px;line-height:1.7;margin:0}
@media(max-width:480px){.cosmetics-checkout-bar{left:12px}.cosmetics-checkout-bar .beauty-cart strong{font-size:12px}}
</style>
@php
$eleganceCheckoutConfig = [
    'shopId' => $shop->id,
    'orderUrl' => route('cosmetics.orders.store', $shop),
    'csrfUrl' => route('csrf.refresh'),
    'legacyKey' => 'ozman.restaurant.'.$shop->id.'.customer.v'.($shop->customer_registration_version ?? 1),
];
@endphp
<script>
window.OZMAN_ELEGANCE_CHECKOUT = @json($eleganceCheckoutConfig);
</script>
<script>{!! file_get_contents(base_path('public/elegance-order-whatsapp.js')) !!}</script>
<script>{!! file_get_contents(base_path('public/elegance-checkout.js')) !!}</script>
@endif
