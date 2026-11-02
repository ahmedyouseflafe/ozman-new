{{-- Customers pay gifts with confirmed loyalty points; guests are sent to sign in first. --}}
<dialog id="promoterGiftDialog" class="profile-composer gift-dialog" aria-labelledby="giftDialogTitle"
    data-url="{{ route('promoters.gifts.store', $profile) }}"
    data-login="{{ route('community.login', ['return' => parse_url($profile->publicUrl(), PHP_URL_PATH)]) }}"
    data-signed-in="{{ $gifting['canGift'] ? '1' : '0' }}"
    data-i18n="{{ json_encode([
        'notEnough' => __('رصيدك ما بيكفي لهالهدية.'),
        'failed' => __('ما زبطت الهدية. حاول كمان مرة.'),
        'expired' => __('انتهت الجلسة. حدّث الصفحة وحاول مجددًا.'),
        'tooMany' => __('استنى شوي وحاول مجددًا.'),
        'sending' => __('جارٍ الإرسال…'),
    ], JSON_UNESCAPED_UNICODE) }}">
    <header><button type="button" class="composer-close" data-gift-close aria-label="{{ __('إغلاق') }}">×</button><h2 id="giftDialogTitle">{{ __('ادعم :name بهدية', ['name' => $profile->display_name]) }}</h2></header>
    <div class="gift-dialog-body">
        @if($gifting['canGift'])
            <p class="gift-balance">{{ __('رصيدك:') }} <strong id="giftBalance" data-balance="{{ $gifting['balance'] }}">⭐ {{ number_format($gifting['balance']) }}</strong> {{ __('نقطة') }}</p>
            <div class="gift-grid">
                @foreach($gifting['gifts'] as $gift)
                    <button type="button" class="gift-option" data-gift-id="{{ $gift->id }}" data-points="{{ $gift->points }}" @disabled($gift->points > $gifting['balance'])>
                        <span class="gift-icon" aria-hidden="true">{{ $gift->icon }}</span><span class="gift-name">{{ $gift->name }}</span><span class="gift-cost">⭐ {{ number_format($gift->points) }}</span>
                    </button>
                @endforeach
            </div>
            <small>{{ __('الهدايا بتنخصم من نقاطك المؤكدة، من نقاط أقدم محل أولاً. وبترفع مستوى صفحة المروّج.') }}</small>
        @else
            <p>{{ __('اجمع نقاط من مشترياتك بمحلات أوزمان، واستخدمها لتدعم المروّجين اللي بتحبهم.') }}</p>
            <div class="gift-grid is-preview">@foreach($gifting['gifts'] as $gift)<span class="gift-option"><span class="gift-icon" aria-hidden="true">{{ $gift->icon }}</span><span class="gift-name">{{ $gift->name }}</span><span class="gift-cost">⭐ {{ number_format($gift->points) }}</span></span>@endforeach</div>
            @guest<a class="button" href="{{ route('community.login', ['return' => parse_url($profile->publicUrl(), PHP_URL_PATH)]) }}">{{ __('سجّل دخول عشان تهدي') }}</a>@endguest
        @endif
        <p id="giftStatus" role="status" aria-live="polite"></p>
    </div>
</dialog>
