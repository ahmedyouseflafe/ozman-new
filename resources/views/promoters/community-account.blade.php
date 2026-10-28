@extends('promoters.layout')
@section('title','حسابي • مجتمع أوزمان')
@section('content')
<section class="community-hero"><span class="eyebrow">مساحتك الشخصية</span><h1>أهلًا {{ $user->name }}</h1><p>حسابات بتتابعها، حكايات جديدة، وكل إشعاراتك هون.</p><div class="profile-actions"><a href="{{ route('promoters.index') }}" class="button">اكتشف المروّجين ←</a><a href="#updates" class="button outline">آخر التحديثات</a></div></section>
<div class="studio-columns">
<aside class="stack">
<section class="panel"><h2>ملفي الشخصي</h2><form method="post" action="{{ route('community.update') }}" class="stack">@csrf @method('PUT')
<label>الاسم<input name="name" value="{{ old('name',$user->name) }}" maxlength="100" required autocomplete="name"></label>
<label>رقم الجوال (اختياري)<input name="phone" type="tel" value="{{ old('phone',$user->phone) }}" maxlength="30" autocomplete="tel"></label>
<p class="handle" dir="ltr">{{ $user->email }}</p><button class="button outline">حفظ بياناتي</button></form><small>بيانات حسابك خاصة، ولا تُعرض للمروّجين على هذه الصفحة.</small></section>
<section class="panel" id="communityPush"><h2>خليك قريب 🔔</h2><p>فعّل إشعارات الجهاز ليصلك جديد الحسابات اللي بتتابعها، حتى لو الصفحة مسكّرة.</p>
<button class="button" id="communityPushEnable" type="button">تفعيل إشعارات المتابعة</button><button class="button outline" id="communityPushDisable" type="button" hidden>إيقافها على هذا الجهاز</button><p id="communityPushStatus" role="status" aria-live="polite">الإشعارات داخل حسابك موجودة حتى بدون تفعيل الجهاز.</p></section>
</aside>
<div class="stack"><section class="panel"><div class="section-heading"><h2>بتابعهم <span class="count">{{ $profiles->total() }}</span></h2></div>
<div class="community-following">@forelse($profiles as $profile)<article class="account-card"><div class="account-heading"><div class="avatar-ring">@include('promoters.avatar')</div><div><h3>{{ $profile->display_name }}</h3><a class="text-link" href="{{ $profile->publicUrl() }}">افتح الصفحة ←</a></div></div><form action="{{ route('promoters.follow.destroy',$profile) }}" method="post">@csrf @method('DELETE')<button class="text-link">إلغاء المتابعة</button></form></article>@empty<p>لسه ما تابعت حدا. اختار مروّج وابدأ المتابعة.</p>@endforelse</div>@include('promoters.pagination',['items'=>$profiles])</section>
<section class="panel" id="updates"><h2>آخر التحديثات</h2><div class="stack">@forelse($updates as $update)<article class="community-update {{ $update->read_at ? '' : 'is-unread' }}"><span aria-hidden="true">{{ $update->post->kind==='story'?'◉':'▷' }}</span><div><strong>{{ $update->post->profile->display_name }}</strong><p>{{ $update->post->kind==='story'?'نشر ستوري جديدة':'نشر محتوى جديد' }} · {{ $update->created_at->diffForHumans() }}</p></div><form method="post" action="{{ route('community.read',$update) }}">@csrf<button class="button small outline">شاهد</button></form></article>@empty<div class="quiet-empty">أول ما ينزل جديد من الحسابات اللي بتتابعها، بتلاقيه هون.</div>@endforelse</div>@include('promoters.pagination',['items'=>$updates])</section></div></div>
@endsection
@section('scripts')
<script>window.OZMAN_COMMUNITY_PUSH=@json(['enabled'=>$pushEnabled,'keyUrl'=>route('community.push.key'),'storeUrl'=>route('community.push.store'),'destroyUrl'=>route('community.push.destroy')]);</script>
<script src="{{ asset('web-push-client.js') }}?v={{ filemtime(public_path('web-push-client.js')) }}"></script>
<script>{!! file_get_contents(public_path('community-push.js')) !!}</script>
@endsection
