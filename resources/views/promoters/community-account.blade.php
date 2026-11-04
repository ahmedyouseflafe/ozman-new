@extends('promoters.layout')
@section('title','حسابي • مجتمع أوزمان')
@section('content')
<section class="community-hero"><span class="eyebrow">مساحتك الشخصية</span><div class="account-hero-identity">@if($user->isCustomer())@include('promoters.customer-avatar', ['class' => 'is-large'])@endif<div><h1>أهلًا {{ $user->name }}</h1>@if($user->username)<span class="handle" dir="ltr">{{ '@'.$user->username }}</span>@endif</div></div><p>حسابات بتتابعها، حكايات جديدة، وكل إشعاراتك هون.</p><div class="profile-actions"><a href="{{ route('promoters.index') }}" class="button">اكتشف المروّجين ←</a><a href="#updates" class="button outline">آخر التحديثات</a></div></section>
@if($user->isPromoter() && $user->promoterProfile?->isPending())<section class="panel pending-banner" role="status"><h2>{{ __('⏳ طلبك كمروّج قيد المراجعة') }}</h2><p>{{ __('الإدارة بتراجع طلبك، وأول ما ينقبل بيوصلك إيميل وبتفتح لوحتك وصفحتك') }} <bdi dir="ltr">/promoters/{{ $user->promoterProfile->slug }}</bdi>. {{ __('لهلأ حسابك شغّال كعميل: تابع المروّجين واجمع نقاط.') }}</p></section>@endif
<div class="studio-columns">
<aside class="stack">
<section class="panel loyalty-summary" aria-labelledby="loyaltyTitle"><h2 id="loyaltyTitle">{{ __('نقاطي') }} ⭐</h2>
<div class="loyalty-balance"><div><strong>{{ number_format($points['balance']) }}</strong><span>{{ __('الرصيد المؤكد') }}</span></div><div class="is-pending"><strong>{{ number_format($points['pending']) }}</strong><span>{{ __('نقاط معلّقة') }}</span></div></div>
<small>{{ __('النقاط المعلّقة بتتأكد لما المحل يسلّم الطلب، وبتنلغي إذا انلغى.') }}</small> <a class="text-link" href="#points">{{ __('سجل النقاط') }}</a>
@if(($points['shops'] ?? collect())->where('balance', '>', 0)->isNotEmpty())<div class="shop-balances"><h3>{{ __('نقاطك حسب المحل') }}</h3><ul>@foreach($points['shops']->where('balance', '>', 0) as $bucket)<li><span>{{ $bucket['shop_name'] ?? __('أوزمان') }}</span><b>⭐ {{ number_format($bucket['balance']) }}</b></li>@endforeach</ul><small>{{ __('كل النقاط بتنفع هدايا للمروّجين. والخصم بمحل معيّن بيكون من نقاط هالمحل بس.') }}</small></div>@endif
</section>
@if($user->isCustomer())
<section class="panel customer-avatar-panel"><h2>{{ __('صورتك') }}</h2>
<form method="post" action="{{ route('community.avatar.update') }}" enctype="multipart/form-data" class="stack">@csrf @method('PUT')
@include('promoters.avatar-picker', ['label' => __('أضف صورة')])
<button class="button" type="submit" data-avatar-save hidden>{{ __('حفظ الصورة') }}</button></form>
@if($user->avatar_path)<form method="post" action="{{ route('community.avatar.destroy') }}">@csrf @method('DELETE')<button class="text-link danger" type="submit">{{ __('حذف الصورة') }}</button></form>@endif
</section>
@endif
<section class="panel"><h2>ملفي الشخصي</h2><form method="post" action="{{ route('community.update') }}" class="stack">@csrf @method('PUT')
<label>الاسم<input name="name" value="{{ old('name',$user->name) }}" maxlength="100" required autocomplete="name"></label>
@unless($user->isPromoter())<label>{{ __('اسم المستخدم') }}<span class="username-field" dir="ltr"><span aria-hidden="true">@</span><input name="username" value="{{ old('username',$user->username) }}" maxlength="30" autocomplete="username" autocapitalize="none" spellcheck="false" pattern="[a-zA-Z0-9._]{3,30}"></span></label>@endunless
<label>رقم الجوال (اختياري)<input name="phone" type="tel" value="{{ old('phone',$user->phone) }}" maxlength="30" autocomplete="tel"></label>
<p class="handle" dir="ltr">{{ $user->email }}</p><button class="button outline">حفظ بياناتي</button></form><small>بيانات حسابك خاصة، ولا تُعرض للمروّجين على هذه الصفحة.</small></section>
@include('promoters.password-form')
<section class="panel" id="communityPush"><h2>خليك قريب 🔔</h2><p>فعّل إشعارات الجهاز ليصلك جديد الحسابات اللي بتتابعها، حتى لو الصفحة مسكّرة.</p>
<button class="button" id="communityPushEnable" type="button">تفعيل إشعارات المتابعة</button><button class="button outline" id="communityPushDisable" type="button" hidden>إيقافها على هذا الجهاز</button><p id="communityPushStatus" role="status" aria-live="polite">الإشعارات داخل حسابك موجودة حتى بدون تفعيل الجهاز.</p></section>
</aside>
<div class="stack"><section class="panel"><div class="section-heading"><h2>بتابعهم <span class="count">{{ $profiles->total() }}</span></h2></div>
<div class="community-following">@forelse($profiles as $profile)<article class="account-card"><div class="account-heading"><div class="avatar-ring">@include('promoters.avatar')</div><div><h3>{{ $profile->display_name }}</h3><a class="text-link" href="{{ $profile->publicUrl() }}">افتح الصفحة ←</a></div></div><form action="{{ route('promoters.follow.destroy',$profile) }}" method="post">@csrf @method('DELETE')<button class="text-link">إلغاء المتابعة</button></form></article>@empty<p>لسه ما تابعت حدا. اختار مروّج وابدأ المتابعة.</p>@endforelse</div>@include('promoters.pagination',['items'=>$profiles])</section>
<section class="panel" id="updates"><h2>آخر التحديثات</h2><div class="stack">@forelse($updates as $update)<article class="community-update {{ $update->read_at ? '' : 'is-unread' }}"><span aria-hidden="true">{{ $update->post->kind==='story'?'◉':'▷' }}</span><div><strong>{{ $update->post->profile->display_name }}</strong><p>{{ $update->post->kind==='story'?'نشر ستوري جديدة':'نشر محتوى جديد' }} · {{ $update->created_at->diffForHumans() }}</p></div><form method="post" action="{{ route('community.read',$update) }}">@csrf<button class="button small outline">شاهد</button></form></article>@empty<div class="quiet-empty">أول ما ينزل جديد من الحسابات اللي بتتابعها، بتلاقيه هون.</div>@endforelse</div>@include('promoters.pagination',['items'=>$updates])</section>
<section class="panel" id="points"><h2>{{ __('سجل النقاط') }}</h2><div class="stack">@forelse($pointEntries as $entry)<article class="points-entry is-{{ $entry->status }} {{ $entry->points < 0 ? 'is-negative' : '' }}"><div><strong>{{ $entry->typeLabel() }}@if($entry->shop) · {{ $entry->shop->name }}@endif</strong><p><time datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->format('Y-m-d') }}</time>@if($entry->order) · {{ __('طلب') }} <bdi dir="ltr">{{ $entry->order->order_number }}</bdi>@endif</p>@if($entry->note)<p>{{ $entry->note }}</p>@endif</div><div class="points-amount"><b dir="ltr">{{ $entry->points > 0 ? '+' : '' }}{{ number_format($entry->points) }}</b><span>{{ $entry->statusLabel() }}</span></div></article>@empty<div class="quiet-empty">{{ __('لسه ما في نقاط. اطلب من محل مشارك وأنت مسجّل دخول واكسب نقاط على كل طلب.') }}</div>@endforelse</div>@include('promoters.pagination',['items'=>$pointEntries])</section></div></div>
@endsection
@section('scripts')
<script>window.OZMAN_COMMUNITY_PUSH={{ \Illuminate\Support\Js::from(['enabled'=>$pushEnabled,'keyUrl'=>route('community.push.key'),'storeUrl'=>route('community.push.store'),'destroyUrl'=>route('community.push.destroy')]) }};</script>
<script src="{{ asset('web-push-client.js') }}?v={{ filemtime(public_path('web-push-client.js')) }}"></script>
<script>{!! file_get_contents(public_path('community-push.js')) !!}</script>
@endsection
