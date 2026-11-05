@extends('promoters.layout')
@section('title','حسابي • مجتمع أوزمان')
@section('content')
@php
    $pendingCreator = $user->isPromoter() && $user->promoterProfile?->isPending();
    $isCreator = $user->isPromoter() && $creator && ! $pendingCreator;
    $roleChip = $isCreator ? ['🎬', __('مروّج'), 'is-creator'] : ($pendingCreator ? ['⏳', __('مروّج قيد المراجعة'), 'is-pending'] : ['🛍', __('عميل'), 'is-customer']);
    $tabs = ['overview' => __('الرئيسية'), 'following' => __('بتابعهم'), 'points' => __('نقاطي'), 'settings' => __('الإعدادات')];
@endphp
{{-- Account hub: who you are, what you can do now, then tabs for the details. --}}
<section class="hub-identity">
    @include('promoters.customer-avatar', ['class' => 'is-large'])
    <div class="hub-identity-text">
        <h1>{{ $user->name }}</h1>
        <p>@if($user->username)<span class="handle" dir="ltr">{{ '@'.$user->username }}</span>@endif <span class="role-chip {{ $roleChip[2] }}">{{ $roleChip[0] }} {{ $roleChip[1] }}</span></p>
    </div>
    <a class="hub-points" href="{{ route('community.account', ['tab' => 'points']) }}" title="{{ __('رصيد النقاط') }}"><strong>⭐ {{ number_format($points['balance']) }}</strong><span>{{ __('نقطة') }}@if($points['pending']) · {{ __(':count معلّقة', ['count' => number_format($points['pending'])]) }}@endif</span></a>
</section>

@if($pendingCreator)<section class="panel pending-banner" role="status"><h2>{{ __('⏳ طلبك كمروّج قيد المراجعة') }}</h2><p>{{ __('الإدارة بتراجع طلبك، وأول ما ينقبل بيوصلك إيميل وبتفتح لوحتك وصفحتك') }} <bdi dir="ltr">/promoters/{{ $user->promoterProfile->slug }}</bdi>. {{ __('لهلأ حسابك شغّال كعميل: تابع المروّجين واجمع نقاط.') }}</p></section>@endif

<div class="hub-tabs segmented" role="tablist" aria-label="{{ __('أقسام حسابي') }}">
    @foreach($tabs as $key => $label)
        <a role="tab" href="{{ route('community.account', ['tab' => $key]) }}" data-hub-tab="{{ $key }}" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" @class(['is-active' => $tab === $key])>{{ $label }}@if($key === 'overview' && $unread) <span class="pending-count">{{ $unread }}</span>@endif</a>
    @endforeach
</div>

{{-- الرئيسية --}}
<div class="hub-panel" data-hub-panel="overview" role="tabpanel" @if($tab !== 'overview') hidden @endif>
    @if($isCreator)
        <section class="hub-section">
            <div class="hub-section-head"><h2>{{ __('🎬 مساحتك كمروّج') }}</h2><a class="text-link" href="{{ $creator->publicUrl() }}">{{ __('شوف صفحتك ←') }}</a></div>
            <div class="hub-stats">
                <div><strong>{{ number_format($creator->followerTotal()) }}</strong><span>{{ __('متابع') }}</span></div>
                <div><strong>{{ number_format($creator->page_views) }}</strong><span>{{ __('زيارة') }}</span></div>
                <div><strong>{{ number_format($creator->gift_points) }}</strong><span>{{ __('🎁 نقاط هدايا') }}</span></div>
                <div><strong>{{ number_format($creator->posts_count) }}</strong><span>{{ __('منشور') }}</span></div>
            </div>
            <div class="hub-tiles">
                <a class="hub-tile is-primary" href="{{ route('promoter.manage', $creator) }}#storyComposer"><span aria-hidden="true">＋</span><b>{{ __('انشر حكاية') }}</b><small>{{ __('ستوري أو فيديو عن محل') }}</small></a>
                <a class="hub-tile" href="{{ route('promoter.manage', $creator) }}"><span aria-hidden="true">📊</span><b>{{ __('لوحتي') }}</b><small>{{ __('الوصول، الهدايا، المتابعين') }}</small></a>
                <a class="hub-tile" href="{{ $creator->publicUrl() }}"><span aria-hidden="true">👀</span><b>{{ __('صفحتي') }}</b><small dir="ltr">/promoters/{{ $creator->slug }}</small></a>
            </div>
        </section>
    @endif

    <section class="hub-section">
        <div class="hub-section-head"><h2>{{ __('🛍 المتجر الحر') }}</h2><a class="text-link" href="{{ route('market.index') }}">{{ __('تصفّح المتجر ←') }}</a></div>
        <div class="hub-tiles">
            <a class="hub-tile" href="{{ route('market.purchases') }}"><span aria-hidden="true">📦</span><b>{{ __('طلباتي') }}</b><small>{{ $market['purchases'] ? __(':count طلب مفتوح', ['count' => $market['purchases']]) : __('اللي طلبته من غيرك') }}</small></a>
            <a class="hub-tile" href="{{ route('market.sales') }}"><span aria-hidden="true">📥</span><b>{{ __('طلبات وصلتني') }}@if($market['sales']) <span class="pending-count">{{ $market['sales'] }}</span>@endif</b><small>{{ $market['sales'] ? __('بانتظار ردك') : __('طلبات على منتجاتك') }}</small></a>
            <a class="hub-tile" href="{{ $market['listings'] ? route('market.mine') : route('market.create') }}"><span aria-hidden="true">🏷</span><b>{{ $market['listings'] ? __('منتجاتي') : __('بيع منتج') }}</b><small>{{ $market['listings'] ? __(':count منتج معروض', ['count' => $market['listings']]) : __('صوّر وحط سعر') }}</small></a>
        </div>
    </section>

    @if($user->isCustomer())
        <section class="hub-section hub-become">
            <div><h2>{{ __('✨ بدك تصير مروّج؟') }}</h2><p>{{ __('احكي عن المحلات اللي بتحبها بستوريات وفيديوهات، واجمع متابعين وهدايا. الإدارة بتراجع الطلب.') }}</p></div>
            <details @if($errors->has('username') && old('become')) open @endif><summary class="button">{{ __('قدّم طلب') }}</summary>
                <form method="post" action="{{ route('community.become-promoter') }}" class="stack">@csrf<input type="hidden" name="become" value="1">
                    <label>{{ __('اسم المستخدم (بيصير رابط صفحتك)') }}<span class="username-field" dir="ltr"><span aria-hidden="true">@</span><input name="username" value="{{ old('username', $user->username) }}" maxlength="30" autocapitalize="none" spellcheck="false" required></span></label>
                    <label>{{ __('نبذة قصيرة (اختياري)') }}<textarea name="bio" rows="2" maxlength="600" placeholder="{{ __('شو بتحب تحكي عنه؟') }}">{{ old('bio') }}</textarea></label>
                    <button class="button" type="submit">{{ __('أرسل الطلب') }}</button>
                </form>
            </details>
        </section>
    @endif

    <section class="hub-section" id="updates">
        <div class="hub-section-head"><h2>{{ __('🔔 آخر التحديثات') }}</h2></div>
        <div class="stack">@forelse($updates as $update)<article class="community-update {{ $update->read_at ? '' : 'is-unread' }}"><span aria-hidden="true">{{ $update->post->kind==='story'?'◉':'▷' }}</span><div><strong>{{ $update->post->profile->display_name }}</strong><p>{{ $update->post->kind==='story'?'نشر ستوري جديدة':'نشر محتوى جديد' }} · {{ $update->created_at->diffForHumans() }}</p></div><form method="post" action="{{ route('community.read',$update) }}">@csrf<button class="button small outline">{{ __('شاهد') }}</button></form></article>@empty<div class="quiet-empty">{{ __('أول ما ينزل جديد من الحسابات اللي بتتابعها، بتلاقيه هون.') }} <a class="text-link" href="{{ route('promoters.index') }}">{{ __('اكتشف المروّجين') }}</a></div>@endforelse</div>
        @include('promoters.pagination',['items'=>$updates])
    </section>
</div>

{{-- بتابعهم --}}
<div class="hub-panel" data-hub-panel="following" role="tabpanel" @if($tab !== 'following') hidden @endif>
    <section class="hub-section"><div class="hub-section-head"><h2>{{ __('بتابعهم') }} <span class="count">{{ $profiles->total() }}</span></h2></div>
    <div class="community-following">@forelse($profiles as $profile)<article class="account-card"><div class="account-heading"><div class="avatar-ring">@include('promoters.avatar')</div><div><h3>{{ $profile->display_name }}</h3><a class="text-link" href="{{ $profile->publicUrl() }}">{{ __('افتح الصفحة ←') }}</a></div></div><form action="{{ route('promoters.follow.destroy',$profile) }}" method="post">@csrf @method('DELETE')<button class="text-link">{{ __('إلغاء المتابعة') }}</button></form></article>@empty<p class="quiet-empty">{{ __('لسه ما تابعت حدا.') }} <a class="text-link" href="{{ route('promoters.index') }}">{{ __('اختار مروّج وابدأ المتابعة') }}</a></p>@endforelse</div>
    @include('promoters.pagination',['items'=>$profiles])</section>
</div>

{{-- نقاطي --}}
<div class="hub-panel" data-hub-panel="points" role="tabpanel" @if($tab !== 'points') hidden @endif>
    <section class="hub-section loyalty-summary" aria-labelledby="loyaltyTitle"><h2 id="loyaltyTitle">{{ __('نقاطي') }} ⭐</h2>
        <div class="loyalty-balance"><div><strong>{{ number_format($points['balance']) }}</strong><span>{{ __('الرصيد المؤكد') }}</span></div><div class="is-pending"><strong>{{ number_format($points['pending']) }}</strong><span>{{ __('نقاط معلّقة') }}</span></div></div>
        <small>{{ __('بتكسب نقاط على طلباتك من المحلات المشاركة. المعلّقة بتتأكد لما المحل يسلّم الطلب، وبتنلغي إذا انلغى.') }}</small>
        @if(($points['shops'] ?? collect())->where('balance', '>', 0)->isNotEmpty())<div class="shop-balances"><h3>{{ __('نقاطك حسب المحل') }}</h3><ul>@foreach($points['shops']->where('balance', '>', 0) as $bucket)<li><span>{{ $bucket['shop_name'] ?? __('أوزمان') }}</span><b>⭐ {{ number_format($bucket['balance']) }}</b></li>@endforeach</ul><small>{{ __('كل النقاط بتنفع هدايا للمروّجين. والخصم بمحل معيّن بيكون من نقاط هالمحل بس.') }}</small></div>@endif
    </section>
    <section class="hub-section" id="points"><h2>{{ __('سجل النقاط') }}</h2><div class="stack">@forelse($pointEntries as $entry)<article class="points-entry is-{{ $entry->status }} {{ $entry->points < 0 ? 'is-negative' : '' }}"><div><strong>{{ $entry->typeLabel() }}@if($entry->shop) · {{ $entry->shop->name }}@endif</strong><p><time datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->format('Y-m-d') }}</time>@if($entry->order) · {{ __('طلب') }} <bdi dir="ltr">{{ $entry->order->order_number }}</bdi>@endif</p>@if($entry->note)<p>{{ $entry->note }}</p>@endif</div><div class="points-amount"><b dir="ltr">{{ $entry->points > 0 ? '+' : '' }}{{ number_format($entry->points) }}</b><span>{{ $entry->statusLabel() }}</span></div></article>@empty<div class="quiet-empty">{{ __('لسه ما في نقاط. اطلب من محل مشارك وأنت مسجّل دخول واكسب نقاط على كل طلب.') }}</div>@endforelse</div>@include('promoters.pagination',['items'=>$pointEntries])</section>
</div>

{{-- الإعدادات --}}
<div class="hub-panel" data-hub-panel="settings" role="tabpanel" @if($tab !== 'settings') hidden @endif>
<div class="studio-columns">
    <div class="stack">
        @if($user->isCustomer())
        <section class="panel customer-avatar-panel"><h2>{{ __('صورتك') }}</h2>
            <form method="post" action="{{ route('community.avatar.update') }}" enctype="multipart/form-data" class="stack">@csrf @method('PUT')
            @include('promoters.avatar-picker', ['label' => __('أضف صورة')])
            <button class="button" type="submit" data-avatar-save hidden>{{ __('حفظ الصورة') }}</button></form>
            @if($user->avatar_path)<form method="post" action="{{ route('community.avatar.destroy') }}">@csrf @method('DELETE')<button class="text-link danger" type="submit">{{ __('حذف الصورة') }}</button></form>@endif
        </section>
        @endif
        <section class="panel"><h2>{{ __('ملفي الشخصي') }}</h2><form method="post" action="{{ route('community.update') }}" class="stack">@csrf @method('PUT')
            <label>{{ __('الاسم') }}<input name="name" value="{{ old('name',$user->name) }}" maxlength="100" required autocomplete="name"></label>
            @unless($user->isPromoter())<label>{{ __('اسم المستخدم') }}<span class="username-field" dir="ltr"><span aria-hidden="true">@</span><input name="username" value="{{ old('username',$user->username) }}" maxlength="30" autocomplete="username" autocapitalize="none" spellcheck="false" pattern="[a-zA-Z0-9._]{3,30}"></span></label>@endunless
            <label>{{ __('رقم الجوال (اختياري)') }}<input name="phone" type="tel" value="{{ old('phone',$user->phone) }}" maxlength="30" autocomplete="tel"></label>
            <p class="handle" dir="ltr">{{ $user->email }}</p><button class="button outline">{{ __('حفظ بياناتي') }}</button></form><small>{{ __('بيانات حسابك خاصة، والمروّجين بيشوفوا اسمك الأول بس.') }}</small></section>
    </div>
    <div class="stack">
        @include('promoters.password-form')
        <section class="panel" id="communityPush"><h2>{{ __('خليك قريب 🔔') }}</h2><p>{{ __('فعّل إشعارات الجهاز ليصلك جديد الحسابات اللي بتتابعها، حتى لو الصفحة مسكّرة.') }}</p>
            <button class="button" id="communityPushEnable" type="button">{{ __('تفعيل إشعارات المتابعة') }}</button><button class="button outline" id="communityPushDisable" type="button" hidden>{{ __('إيقافها على هذا الجهاز') }}</button><p id="communityPushStatus" role="status" aria-live="polite">{{ __('الإشعارات داخل حسابك موجودة حتى بدون تفعيل الجهاز.') }}</p></section>
    </div>
</div>
</div>
@endsection
@section('scripts')
<script>
// Tabs switch in place; the URL keeps the tab so refresh and back work.
document.querySelectorAll('[data-hub-tab]').forEach(link=>link.addEventListener('click',e=>{e.preventDefault();const key=link.dataset.hubTab;
document.querySelectorAll('[data-hub-tab]').forEach(l=>{const on=l===link;l.classList.toggle('is-active',on);l.setAttribute('aria-selected',on?'true':'false');});
document.querySelectorAll('[data-hub-panel]').forEach(p=>{p.hidden=p.dataset.hubPanel!==key;});
try{history.replaceState(null,'',link.href);}catch(_){}}));
</script>
<script>window.OZMAN_COMMUNITY_PUSH={{ \Illuminate\Support\Js::from(['enabled'=>$pushEnabled,'keyUrl'=>route('community.push.key'),'storeUrl'=>route('community.push.store'),'destroyUrl'=>route('community.push.destroy')]) }};</script>
<script src="{{ asset('web-push-client.js') }}?v={{ filemtime(public_path('web-push-client.js')) }}"></script>
<script>{!! file_get_contents(public_path('community-push.js')) !!}</script>
@endsection
