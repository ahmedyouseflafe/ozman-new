<dialog id="profileComposer" class="profile-composer" aria-labelledby="composerTitle">
    <form id="profileComposerForm" method="post" enctype="multipart/form-data" action="{{ route('promoter.posts.store', $profile) }}">
        @csrf
        <input type="hidden" name="kind" value="story">
        <header><button type="button" id="composerClose" class="composer-close" aria-label="إغلاق">×</button><h2 id="composerTitle">إضافة ستوري</h2><button class="button small" type="submit" id="composerPublish" disabled>نشر</button></header>
        <div class="composer-body">
            <div class="composer-visual">
            <div class="composer-preview" id="composerPreview"><div class="composer-placeholder"><span aria-hidden="true">▧</span><p>اختار صورة أو فيديو</p><button class="button" type="button" id="composerChoose">اختيار من الجهاز</button></div></div>
            @include('promoters.camera')
            </div>
            <div class="composer-details stack">
                <div class="capture-options" aria-label="التصوير"><button class="button small outline" type="button" id="composerPhoto">تصوير صورة</button><button class="button small outline" type="button" id="composerVideo">تصوير فيديو</button></div>
                <p class="capture-hint">تصوير الفيديو حتى دقيقة واحدة. يمكنك معاينته قبل النشر.</p>
                <div class="native-capture"><label>أو كاميرا الجهاز للصورة<input id="nativePhoto" type="file" accept="image/*" capture="environment"></label><label>أو كاميرا الجهاز للفيديو<input id="nativeVideo" type="file" accept="video/*" capture="environment"></label></div>
                <label>الصورة أو الفيديو<input id="composerFile" name="media" type="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm" required><small>صورة أو فيديو حتى 20 ميجابايت.</small></label>
                <label>اكتب وصفًا<textarea name="caption" rows="3" maxlength="2000" placeholder="احكي عن اللحظة…"></textarea></label>
                <label>المحل (اختياري)<select name="shop_id"><option value="">بدون ربط بمحل</option>@foreach($shops as $shop)<option value="{{ $shop->id }}">{{ $shop->name }}</option>@endforeach</select></label>
                <p id="composerHint">الستوري بتظهر لمتابعي الصفحة لمدة 24 ساعة.</p>
                <progress id="composerProgress" max="100" value="0" hidden aria-label="تقدم الرفع"></progress>
                <p id="composerStatus" role="status" aria-live="polite"></p>
            </div>
        </div>
    </form>
</dialog>
@include('promoters.live-preparation')
