@if($shop->slug === 'elegance-perfumes')
@include('front.partials.shop_signup_wheel')
<div class="cosmetics-purchase-control">
    @include('front.partials.restaurant_purchase_wheel')
</div>
<form id="eleganceCheckoutForm" class="beauty-form" style="margin-bottom:12px">
    <label class="beauty-field" for="beautyCustomerName">{{ __('الاسم') }}
        <input id="beautyCustomerName" name="customer_name" autocomplete="name" maxlength="120" required>
    </label>
    <label class="beauty-field" for="beautyCustomerPhone">{{ __('رقم التواصل') }}
        <input id="beautyCustomerPhone" name="customer_phone" type="tel" autocomplete="tel" maxlength="30" required>
    </label>
    <label class="beauty-field" for="beautyCustomerWhatsapp">{{ __('رقم واتساب') }}
        <input id="beautyCustomerWhatsapp" name="customer_whatsapp" type="tel" maxlength="30" required>
    </label>
    <label class="beauty-field" for="beautyCustomerAddress">{{ __('العنوان') }}
        <textarea id="beautyCustomerAddress" name="customer_address" autocomplete="street-address" maxlength="500" rows="2" required></textarea>
    </label>
    <div class="elegance-location">
        <span>{{ __('اللوكيشن (اختياري)') }}</span>
        <button type="button" id="eleganceLocation" class="beauty-form-action beauty-scan-action">{{ __('إضافة موقعي الحالي') }}</button>
        <button type="button" id="eleganceLocationClear" class="beauty-form-action beauty-scan-action" hidden>{{ __('إزالة الموقع') }}</button>
        <small id="eleganceLocationStatus" role="status">{{ __('يمكنك إرسال الطلب بدون إضافة اللوكيشن.') }}</small>
    </div>
    <label id="eleganceSkipRewardLabel" hidden><input type="checkbox" id="eleganceSkipReward"> {{ __('إكمال الطلب بدون هدية الترحيب') }}</label>
    <p id="eleganceRewardSummary" role="status" style="color:#f7ce82;margin:0"></p>
    <p id="eleganceCheckoutStatus" role="status" aria-live="polite"></p>
    <button type="submit" hidden aria-hidden="true" tabindex="-1">{{ __('إرسال الطلب') }}</button>
</form>
@viewStyle('front.partials.cosmetics_customer_rewards')
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
