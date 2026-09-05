<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>ستوريات المحل — Ozman</title>
    <style>
        body{margin:0;background:#061014;color:#fff;font-family:Tahoma,sans-serif;line-height:1.8}main{max-width:760px;margin:auto;padding:24px}a{color:#00e5ff}form,article{background:#10242a;padding:20px;border-radius:18px;margin:16px 0}label{display:block;margin:12px 0}input,select,textarea,button{box-sizing:border-box;width:100%;padding:12px;font:inherit;border-radius:10px;border:1px solid #42616a}button{background:#00e5ff;color:#001014;cursor:pointer;margin-top:12px}textarea{resize:vertical}article form{padding:0}.notice{color:#8fffc3}.errors{color:#ffadae}
    </style>
</head><body><main>
    <a href="{{ route('dashboard') }}">العودة للوحة التحكم</a>
    <h1>ستوريات المحل</h1>
    <p>شارك صورة أو فيديو مع زوار Ozman. تختفي الستوري بعد 24 ساعة، ويمكنك حذفها قبل ذلك.</p>
    @if(session('status'))<p class="notice" role="status">{{ session('status') }}</p>@endif
    @if($errors->any())<div class="errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="POST" enctype="multipart/form-data" action="{{ route('shop-stories.store') }}">
        @csrf
        <label for="shop">المحل</label><select id="shop" name="shop_id" required>@foreach($shops as $shop)<option value="{{ $shop->id }}" @selected(old('shop_id') == $shop->id)>{{ $shop->name }}</option>@endforeach</select>
        <label for="media">صورة أو فيديو — حتى 20 ميغابايت</label>
        <input id="media" name="media" type="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm" required>
        <label for="caption">نص الستوري (اختياري)</label><textarea id="caption" name="caption" maxlength="300">{{ old('caption') }}</textarea>
        <button type="submit">نشر الستوري</button>
    </form>
    <h2>الستوريات المنشورة</h2>
    @forelse($stories as $story)
        <article><strong>{{ $story->shop->name }}</strong> — {{ $story->type === 'video' ? 'فيديو' : 'صورة' }}
            <p>{{ $story->caption }}</p>
            <p>{{ $story->expires_at->isFuture() ? 'ظاهرة حتى' : 'انتهت في' }} {{ $story->expires_at->format('Y-m-d H:i') }}</p>
            @if($story->expires_at->isFuture())<a href="{{ route('shop-stories.media', $story) }}" target="_blank" rel="noopener">معاينة</a>@endif
            <form method="POST" action="{{ route('shop-stories.destroy', $story) }}">@csrf @method('DELETE')<button type="submit">حذف الستوري</button></form>
        </article>
    @empty<p>لم تنشر أي ستوري بعد.</p>@endforelse
    {{ $stories->links() }}
</main></body></html>
