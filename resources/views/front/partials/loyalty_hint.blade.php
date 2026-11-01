{{-- Loyalty points hint for a cart. Pass `total`: a CSS selector of the element showing the cart total. --}}
@php($loyaltyHint = app(\App\Services\LoyaltyPoints::class)->hint($shop ?? null))
@if($loyaltyHint)
<p class="loyalty-hint {{ $class ?? '' }}" data-loyalty-hint data-total="{{ $total }}" hidden
    data-amount="{{ $loyaltyHint['amount'] }}" data-points="{{ $loyaltyHint['points'] }}"
    @unless($loyaltyHint['signedIn']) data-login="{{ route('community.login', ['return' => request()->getRequestUri()]) }}" @endunless
    data-text="{{ $loyaltyHint['signedIn'] ? __('رح تكسب :points نقطة على هالطلب ⭐') : __('سجّل دخول واكسب :points نقطة على هالطلب') }}"
    role="status" aria-live="polite"></p>
@once
<style>.loyalty-hint{display:flex;align-items:center;gap:6px;margin:8px 0;padding:8px 12px;border:1px solid rgba(214,163,40,.45);border-radius:12px;background:rgba(255,209,102,.14);color:inherit;font-size:13px;font-weight:700;line-height:1.5;text-align:start}.loyalty-hint[hidden]{display:none}.loyalty-hint a{color:inherit;text-decoration:underline;text-underline-offset:3px}</style>
<script src="{{ asset('loyalty-hint.js') }}?v={{ filemtime(public_path('loyalty-hint.js')) }}" defer></script>
@endonce
@endif
