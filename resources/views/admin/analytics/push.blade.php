<section class="panel admin-push" id="adminPush" aria-labelledby="adminPushTitle">
    <div class="section-head"><div><h2 id="adminPushTitle">إدارة أوزمان على جوالك</h2><p>ثبّت اللوحة وفعّل إشعارات نشاط كل المحلات، حتى بعد إغلاقها.</p></div><span class="pill" id="adminInstallState">تطبيق الإدارة</span></div>
    <div class="push-actions">
        <button type="button" class="button primary" id="adminInstall">تثبيت تطبيق الإدارة</button>
        <button type="button" class="button primary" id="adminPushEnable" disabled>تفعيل إشعارات الجوال</button>
        <button type="button" class="button secondary" id="adminPushTest" hidden>إرسال إشعار تجريبي</button>
        <button type="button" class="button secondary" id="adminPushDisable" hidden>إيقاف إشعارات هذا الجهاز</button>
    </div>
    <p class="note">للتثبيت اضغط الزر أعلاه؛ نافذة التثبيت قد لا تظهر تلقائيًا عند فتح اللوحة.</p>
    <p id="adminPushStatus" role="status" aria-live="polite">جارٍ التحقق من إشعارات هذا الجهاز…</p>
    <p id="adminInstallHelp" hidden></p>
    <p class="note">يبقى دخولك محفوظًا على هذا الجهاز. تسجيل الخروج يوقف إشعاراته؛ إغلاق التطبيق لا يوقفها. صوت الإشعار حسب إعدادات الجوال والصامت والتركيز.</p>
    <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="button secondary">تسجيل الخروج</button></form>
</section>
@viewStyle('admin.analytics.push')
