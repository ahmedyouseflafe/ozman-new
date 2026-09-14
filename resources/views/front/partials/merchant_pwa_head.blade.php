@php
    $merchantPwaShop = $pwaShop ?? ($shop ?? null);
    $merchantPwaUser = auth()->user();
    $merchantPwaEnabled = $merchantPwaShop instanceof \App\Models\Shop
        && $merchantPwaUser?->isShopOwner()
        && $merchantPwaUser->shops()->whereKey($merchantPwaShop->id)->exists();
@endphp

@if($merchantPwaEnabled)
    @php
        $merchantPwaVersion = $merchantPwaShop->updated_at?->timestamp ?? 1;
        $merchantPwaPublicKey = $vapidPublicKey ?? app(\App\Services\WebPushService::class)->publicKey();
        $merchantPwaConfig = [
            'shopId' => $merchantPwaShop->id,
            'shopName' => $merchantPwaShop->name,
            'serviceWorkerUrl' => asset('merchant-pwa-sw.js'),
            'subscribeUrl' => route('merchant-app.push.store'),
            'unsubscribeUrl' => route('merchant-app.push.destroy'),
            'vapidPublicKey' => $merchantPwaPublicKey,
        ];
    @endphp
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#071820">
    <meta name="application-name" content="{{ $merchantPwaShop->name }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $merchantPwaShop->name }}">
    <link rel="manifest" href="{{ route('merchant-app.manifest', $merchantPwaShop) }}">
    <link rel="apple-touch-icon" href="{{ route('merchant-app.icon', ['shop' => $merchantPwaShop, 'size' => 192, 'v' => $merchantPwaVersion]) }}">
    <script>
        window.OZMAN_MERCHANT_PWA = @json($merchantPwaConfig);
    </script>
    <script defer src="{{ asset('merchant-pwa.js') }}?v={{ filemtime(public_path('merchant-pwa.js')) }}"></script>
@endif
