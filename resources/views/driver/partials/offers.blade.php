@forelse($offers as $offer)
    @php($order = $offer->order)
    @if($order)
        <article class="offer-card" id="delivery-offer-{{ $offer->id }}">
            <div class="offer-top">
                <div>
                    <span class="offer-kicker"><i class="ti ti-bell-ringing"></i> طلب متاح للاستلام</span>
                    <strong>{{ $order->order_number }}</strong>
                </div>
                <span class="offer-preparation"><i class="ti ti-clock"></i> {{ $order->estimated_preparation_minutes }} د</span>
            </div>
            <div class="offer-meta">
                <span><i class="ti ti-user"></i> {{ $order->customer_name }}</span>
                <span><i class="ti ti-cash"></i> {{ number_format((float) $order->total, 2) }} ₪</span>
                @if($order->customer_address)<span><i class="ti ti-map-pin"></i> {{ $order->customer_address }}</span>@endif
            </div>
            <div class="offer-actions">
                <form method="post" action="{{ route('driver.delivery-offers.accept', $offer) }}">
                    @csrf @method('patch')
                    <button type="submit" class="offer-accept"><i class="ti ti-circle-check"></i> استلام الطلب</button>
                </form>
                <form method="post" action="{{ route('driver.delivery-offers.reject', $offer) }}">
                    @csrf @method('patch')
                    <button type="submit" class="offer-reject"><i class="ti ti-x"></i> رفض</button>
                </form>
            </div>
        </article>
    @endif
@empty
    <div class="offer-empty"><i class="ti ti-bell-off"></i> لا توجد طلبات متاحة للاستلام الآن.</div>
@endforelse
