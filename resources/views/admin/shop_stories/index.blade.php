<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>ستوريات المحل — Ozman</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body{margin:0;background:#061014;color:#fff;font-family:Tahoma,sans-serif;line-height:1.8}main{max-width:760px;margin:auto;padding:24px}a{color:#00e5ff}form,article{background:#10242a;padding:20px;border-radius:18px;margin:16px 0}label{display:block;margin:12px 0}input,select,textarea,button{box-sizing:border-box;width:100%;padding:12px;font:inherit;border-radius:10px;border:1px solid #42616a}button{background:#00e5ff;color:#001014;cursor:pointer;margin-top:12px}textarea{resize:vertical}article form{padding:0}.notice{color:#8fffc3}.errors{color:#ffadae}
    </style>
    @include('admin.shop_stories.styles')
</head><body><main>
    <div class="studio-top"><span class="studio-brand">OZMAN <small>STORIES</small></span><a class="back-link" href="{{ route('dashboard') }}">العودة للوحة التحكم ↗</a></div>
    <div class="studio-heading"><span class="eyebrow">شارك جديد محلك</span>
    <h1>ستوريات المحل</h1>
    <p>شارك صورة أو فيديو مع زوار Ozman. تختفي الستوري بعد 24 ساعة، ويمكنك حذفها قبل ذلك.</p>
    </div>
    @if(session('status'))<p class="notice" role="status">{{ session('status') }}</p>@endif
    @if($errors->any())<div class="errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <div class="composer-layout">
    <form id="storyComposer" class="composer-card" method="POST" enctype="multipart/form-data" action="{{ route('shop-stories.store') }}">
        @csrf
        <div class="card-heading"><h2>إنشاء ستوري جديدة</h2><span class="time-chip">◷ 24 ساعة</span></div>
        <label for="shop">المحل</label><select id="shop" name="shop_id" required>@foreach($shops as $shop)<option value="{{ $shop->id }}" @selected(old('shop_id') == $shop->id)>{{ $shop->name }}</option>@endforeach</select>
        <label for="media">صورة أو فيديو — حتى 20 ميغابايت</label>
        <div class="upload-zone">
            <span class="upload-symbol" aria-hidden="true">↑</span>
            <strong>اختر لحظة تستحق المشاركة</strong><span>اضغط لإضافة صورة أو فيديو من جهازك</span>
            <small>JPG · PNG · WEBP · MP4 · WEBM</small>
            <input id="media" name="media" type="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm" required aria-label="اختيار صورة أو فيديو">
        </div>
        <p id="selectedStoryFile" class="file-feedback" role="status">لم يتم اختيار ملف بعد</p>
        <label for="caption">نص الستوري (اختياري)</label><textarea id="caption" name="caption" maxlength="300">{{ old('caption') }}</textarea>
        <div class="caption-meta"><span>أضف عرضًا، خبرًا أو رسالة لزبائنك</span><span id="captionCount">0 / 300</span></div>
        <button class="publish-button" type="submit">نشر الستوري <span aria-hidden="true">↗</span></button>
        <p class="publish-hint">تظهر لزوار Ozman فور النشر</p>
    </form>
    <aside class="preview-card" aria-label="معاينة الستوري">
        <div class="preview-label"><span>معاينة مباشرة</span><small>هكذا تظهر لزبائنك</small></div>
        <div class="preview-phone">
            <div class="phone-progress"><span></span><span></span><span></span></div>
            <div class="phone-header"><span class="phone-avatar">O</span><div><strong id="previewShopName"></strong><small>الآن</small></div></div>
            <div id="storyPreviewMedia" class="preview-media"><div class="preview-placeholder"><span>✧</span><strong>قصة محلك تبدأ هنا</strong><small>اختر صورة أو فيديو للمعاينة</small></div></div>
            <p id="previewCaption" class="preview-caption"></p>
            <div class="phone-footer">زيارة المحل ↗</div>
        </div>
    </aside>
    </div>
    <section class="story-history">
    <h2>الستوريات المنشورة</h2>
    @forelse($stories as $story)
        <article class="history-card"><div class="history-media">
            @if($story->expires_at->isFuture())
                @if($story->type === 'image')<img src="{{ route('shop-stories.media', $story) }}" alt="{{ $story->caption ?: $story->shop->name }}" loading="lazy">
                @else<video src="{{ route('shop-stories.media', $story) }}" preload="none" controls playsinline></video>@endif
            @else<span>◷</span>@endif
        </div><div class="history-details"><span class="story-state {{ $story->expires_at->isFuture() ? 'is-live' : '' }}">{{ $story->expires_at->isFuture() ? '● ظاهرة الآن' : 'منتهية' }}</span><strong>{{ $story->shop->name }}</strong> — {{ $story->type === 'video' ? 'فيديو' : 'صورة' }}
            <p>{{ $story->caption }}</p>
            <p>{{ $story->expires_at->isFuture() ? 'ظاهرة حتى' : 'انتهت في' }} {{ $story->expires_at->format('Y-m-d H:i') }}</p>
            @if($story->expires_at->isFuture())<a href="{{ route('shop-stories.media', $story) }}" target="_blank" rel="noopener">معاينة</a>@endif
            <form method="POST" action="{{ route('shop-stories.destroy', $story) }}">@csrf @method('DELETE')<button type="submit">حذف الستوري</button></form>
        </div></article>
    @empty<div class="empty-stories"><span aria-hidden="true">◉</span><div><strong>مساحة تنتظر أول ستوري لك</strong><p>شارك جديد محلك، ودع زبائنك يشاهدونه هنا.</p></div></div>@endforelse
    {{ $stories->links() }}
    </section>
    @include('admin.shop_stories.preview_script')
</main></body></html>
