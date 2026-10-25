<dialog id="creatorLivePreparation" class="profile-composer live-preparation" aria-labelledby="livePreparationTitle">
    <header><button type="button" class="composer-close" id="livePreparationClose" aria-label="إغلاق">×</button><h2 id="livePreparationTitle">تجهيز البث المباشر</h2><span class="live-setup-badge">غير مباشر</span></header>
    <div class="live-preparation-content">
        <p><strong>هذه معاينة خاصة؛ لا يشاهدك المتابعون.</strong></p>
        <p>البث المباشر غير مفعّل حاليًا. سيصبح متاحًا بعد ربط خدمة البث من إدارة أوزمان.</p>
        <button type="button" class="button" id="livePreparationCamera">اختبار الكاميرا والمايك</button>
        @include('promoters.camera', ['livePreview' => true])
        <p id="livePreparationStatus" role="status"></p>
        <button type="button" class="button" disabled>بدء البث — بانتظار تفعيل الخدمة</button>
    </div>
</dialog>
