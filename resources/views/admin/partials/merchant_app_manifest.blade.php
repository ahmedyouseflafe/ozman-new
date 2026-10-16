{{-- Preserve manifest discovery after the installed app redirects to its dashboard. --}}
@if($shop->is_active && auth()->user()?->isShopOwner() && auth()->user()->is_active && auth()->user()->shops()->whereKey($shop->id)->exists())
    <link rel="manifest" href="{{ route('merchant-app.manifest', $shop) }}">
@endif
