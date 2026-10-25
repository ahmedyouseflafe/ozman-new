<section class="creator-camera" data-camera @if($livePreview ?? false) data-preview-only @endif hidden aria-label="معاينة الكاميرا">
    <video data-camera-video autoplay muted playsinline></video>
    <div class="camera-controls">
        <button type="button" class="button small" data-camera-switch>تبديل الكاميرا</button>
        @unless($livePreview ?? false)
        <button type="button" class="button small" data-camera-snap>التقاط صورة</button>
        <button type="button" class="button small" data-camera-record>بدء التسجيل</button>
        @endunless
        <button type="button" class="button small outline" data-camera-stop>إغلاق الكاميرا</button>
    </div>
    <p data-camera-status role="status" aria-live="polite"></p>
</section>
