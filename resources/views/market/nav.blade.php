{{-- Member links inside the free market. --}}
<div class="market-subnav" role="navigation" aria-label="{{ __('المتجر الحر') }}">
    <a href="{{ route('market.index') }}" @class(['is-current' => request()->routeIs('market.index')])>{{ __('🛍 كل المنتجات') }}</a>
    @auth
        @if(auth()->user()->earnsPoints())
            <a href="{{ route('market.mine') }}" @class(['is-current' => request()->routeIs('market.mine', 'market.create', 'market.edit')])>{{ __('منتجاتي') }}</a>
            <a href="{{ route('market.sales') }}" @class(['is-current' => request()->routeIs('market.sales')])>{{ __('طلبات وصلتني') }}@if($pendingSales = \App\Models\MarketOrder::where('seller_id', auth()->id())->where('status', 'pending')->count()) <span class="pending-count">{{ $pendingSales }}</span>@endif</a>
            <a href="{{ route('market.purchases') }}" @class(['is-current' => request()->routeIs('market.purchases')])>{{ __('طلباتي') }}</a>
        @endif
        @if(auth()->user()->isSuperAdmin())<a href="{{ route('market.admin') }}" @class(['is-current' => request()->routeIs('market.admin')])>{{ __('إدارة المتجر') }}</a>@endif
    @endauth
    <a class="button small" href="{{ auth()->check() ? route('market.create') : route('community.login', ['return' => '/free-market/new']) }}">{{ __('+ بيع منتج') }}</a>
</div>
