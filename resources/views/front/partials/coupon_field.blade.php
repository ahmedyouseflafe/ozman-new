{{-- The discount-code row for a shop's website checkout. Mount it with window.OzmanCoupon.mount(sendButton). --}}
@php($couponConfig = [
    'checkUrl' => route('shop.coupon-check', $shop),
    'csrf' => csrf_token(),
    'csrfUrl' => route('csrf.refresh'),
    'text' => [
        'placeholder' => __('عندك كود خصم؟'), 'apply' => __('طبّق'), 'remove' => __('إزالة'),
        'failed' => __('الكود غير صحيح أو انتهى.'), 'network' => __('تعذّر فحص الكود. جرّب مرة ثانية.'),
    ],
])
<script>window.OzmanCouponConfig = @json($couponConfig);</script>
<script>{!! file_get_contents(public_path('coupon-field.js')) !!}</script>
