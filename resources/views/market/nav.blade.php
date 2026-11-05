{{-- Member area of the free market: one row of tabs. --}}
@auth
@if(auth()->user()->earnsPoints())
<div class="list-toolbar market-member-bar">
    <div class="segmented" role="navigation" aria-label="{{ __('المتجر الحر') }}">
        <a href="{{ route('market.mine') }}" @class(['is-current' => request()->routeIs('market.mine', 'market.create', 'market.edit')])>🏷 {{ __('منتجاتي') }}</a>
        <a href="{{ route('market.sales') }}" @class(['is-current' => request()->routeIs('market.sales')])>📥 {{ __('طلبات وصلتني') }}@if($pendingSales = \App\Models\MarketOrder::where('seller_id', auth()->id())->where('status', 'pending')->count()) <span class="pending-count">{{ $pendingSales }}</span>@endif</a>
        <a href="{{ route('market.purchases') }}" @class(['is-current' => request()->routeIs('market.purchases')])>📦 {{ __('طلباتي') }}</a>
    </div>
    @unless(request()->routeIs('market.create'))<a class="button small" href="{{ route('market.create') }}">{{ __('＋ بيع منتج') }}</a>@endunless
</div>
@endif
@endauth
