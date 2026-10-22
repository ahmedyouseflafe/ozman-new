<section class="panel admin-push" id="adminPush" aria-labelledby="adminPushTitle">
    <div class="section-head"><div><h2 id="adminPushTitle">إدارة أوزمان على جوالك</h2><p>ثبّت اللوحة وفعّل إشعارات نشاط كل المحلات، حتى بعد إغلاقها.</p></div><span class="pill" id="adminInstallState">تطبيق الإدارة</span></div>
    <div class="push-actions">
        <button type="button" class="button secondary" id="adminInstall">تثبيت تطبيق الإدارة</button>
        <button type="button" class="button primary" id="adminPushEnable" disabled>تفعيل إشعارات الجوال</button>
        <button type="button" class="button secondary" id="adminPushTest" hidden>إرسال إشعار تجريبي</button>
        <button type="button" class="button secondary" id="adminPushDisable" hidden>إيقاف إشعارات هذا الجهاز</button>
    </div>
    <p id="adminPushStatus" role="status" aria-live="polite">جارٍ التحقق من إشعارات هذا الجهاز…</p>
    <p id="adminInstallHelp" hidden></p>
    <p class="note">يبقى دخولك محفوظًا على هذا الجهاز. تسجيل الخروج يوقف إشعاراته؛ إغلاق التطبيق لا يوقفها. صوت الإشعار حسب إعدادات الجوال والصامت والتركيز.</p>
    <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="button secondary">تسجيل الخروج</button></form>
</section>
<style>.admin-push .push-actions{display:flex;flex-wrap:wrap;gap:9px}.admin-push [hidden]{display:none!important}.admin-push p{line-height:1.8}.admin-push #adminPushStatus{font-size:13px;color:#38557a}.admin-push #adminInstallHelp{background:#eff6ff;padding:12px;border-radius:10px;font-size:13px}.admin-push button:disabled{opacity:.6;cursor:wait}@media(max-width:650px){.admin-push .push-actions>*{flex:1 1 100%}.admin-push .section-head{align-items:start}}</style>
