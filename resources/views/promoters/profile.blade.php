@extends('promoters.layout')
@section('title', $profile->display_name.' | مروّجين أوزمان')
@section('description', $profile->bio ?: 'ستوريات وفيديوهات '.$profile->display_name.' لاكتشاف محلات أوزمان.')
@section('content')
<style>{!! file_get_contents(public_path('promoter-profile.css')) !!}</style>
@include('promoters.profile-content')
<dialog class="social-viewer" id="socialViewer" aria-label="عارض المنشورات والستوريات"><header><span>{{ $profile->display_name }}</span><button type="button" id="viewerClose" aria-label="إغلاق">×</button></header><div class="story-progress" id="storyProgress" hidden><span></span></div><div class="viewer-media" id="viewerMedia"></div><div class="viewer-nav"><button type="button" id="viewerPrevious" aria-label="السابق">→</button><span id="viewerCounter"></span><button type="button" id="viewerPause" hidden>إيقاف مؤقت</button><button type="button" id="viewerNext" aria-label="التالي">←</button></div><footer><p id="viewerCaption"></p><div class="viewer-actions"><a id="viewerShop" class="button" hidden>زور المحل ←</a>@if(! $canManage && $gifting['gifts']->isNotEmpty())<button type="button" class="button gift-open" id="viewerGift" data-gift-open>{{ __('🎁 أهدي على هالمنشور') }}</button>@endif</div><p id="viewerStatus" role="status"></p></footer></dialog>
@endsection
@section('scripts')
@php
    $serialize = fn ($post) => ['src'=>route('promoters.media',$post),'type'=>$post->type,'poster'=>$post->poster ? route('promoters.media',['post'=>$post,'part'=>'poster']) : null,'caption'=>$post->caption,'shop'=>$post->shop?->is_active ? $post->shop->name : null,'url'=>$post->shopVisitUrl(),'id'=>$post->id];
    $pageData = ['title'=>$profile->display_name, 'url'=>$profile->publicUrl(), 'videos'=>$videos->map($serialize)->values(), 'stories'=>$stories->map($serialize)->values(), 'posts'=>collect($posts->items())->map($serialize)->values()];
@endphp
<script>window.PromoterPage=@json($pageData);</script>
<script>{!! file_get_contents(public_path('promoter-social.js')) !!}</script>
<script>{!! file_get_contents(public_path('promoter-follow.js')) !!}</script>
<script>{!! file_get_contents(public_path('promoter-gifts.js')) !!}</script>
<script>{!! file_get_contents(public_path('promoter-camera.js')) !!}</script><script>{!! file_get_contents(public_path('promoter-composer.js')) !!}</script>
@endsection
