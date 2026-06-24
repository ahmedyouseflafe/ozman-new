<div class="dropdown-item">
    {{ __('الموزعون') }}
    <div class="sub-dropdown">
        @forelse($distributors as $distributor)
            <button type="button" class="sub-item agent-item" data-person-shop-id="{{ $distributor->shop_id }}" data-person-id="{{ $distributor->id }}" data-person-type="distributor">
                <div class="agent-main">
                    <div class="agent-logo-wrapper">
                        <img src="{{ $distributor->image ? asset($distributor->image) : $shopLogo }}" alt="{{ $distributor->name }}" class="agent-img-logo border-cyan">
                    </div>
                    <span class="agent-name">{{ $distributor->name }}</span>
                </div>
                <div class="agent-shop">{{ $distributor->phone ?? $distributor->whatsapp ?? $shopName }}</div>
            </button>
        @empty
            <div class="sub-item">{{ __('لا يوجد موزعون بعد') }}</div>
        @endforelse
    </div>
</div>

<div class="dropdown-item">
    {{ __('الوكلاء') }}
    <div class="sub-dropdown">
        @forelse($agents as $agent)
            <button type="button" class="sub-item agent-item" data-person-shop-id="{{ $agent->shop_id }}" data-person-id="{{ $agent->id }}" data-person-type="agent">
                <div class="agent-main">
                    <div class="agent-logo-wrapper">
                        <img src="{{ $agent->image ? asset($agent->image) : $shopLogo }}" alt="{{ $agent->name }}" class="agent-img-logo border-blue">
                    </div>
                    <span class="agent-name">{{ $agent->name }}</span>
                </div>
                <div class="agent-shop">{{ $agent->phone ?? $agent->whatsapp ?? $shopName }}</div>
            </button>
        @empty
            <div class="sub-item">{{ __('لا يوجد وكلاء بعد') }}</div>
        @endforelse
    </div>
</div>
