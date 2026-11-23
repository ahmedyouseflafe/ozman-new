@if(auth()->user()?->isSuperAdmin() && auth()->user()->is_active)
@once
@viewStyle('admin.analytics.notifications')
<div class="oz-activity-bell" id="ozActivityBell">
    <button type="button" class="oz-activity-trigger" aria-label="إشعارات المحلات" title="إشعارات المحلات" aria-expanded="false" aria-controls="ozActivityPanel" id="ozActivityToggle"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"/><path d="M10 21h4"/></svg> <span class="oz-activity-label">إشعارات المحلات</span> <b id="ozActivityCount">0</b></button>
    <section class="oz-activity-panel" id="ozActivityPanel" hidden aria-label="إشعارات المحلات">
        <div class="oz-activity-head"><strong>إشعارات المحلات</strong><button type="button" id="ozActivityClose" aria-label="إغلاق الإشعارات">×</button></div>
        <small>سجل لكل الأنشطة المتتبعة. لإشعارات الجوال خارج اللوحة، فعّل جهازك من مركز المتابعة.</small>
        <div class="oz-activity-tools"><button type="button" id="ozActivityRead">تعيين الكل كمقروء</button><button type="button" id="ozActivitySound" aria-pressed="false">تفعيل الصوت</button></div>
        <small id="ozActivityStatus" role="status"></small>
        <div id="ozActivityItems"></div>
        <a class="oz-activity-footer" href="{{ route('admin.analytics.index') }}#activity">فتح مركز المتابعة وسجل النشاط ←</a>
    </section>
    <div class="oz-activity-toast" id="ozActivityToast" role="status" hidden></div>
</div>
<script>
window.OzmanActivityConfig = @json(['base' => url('/admin/analytics'), 'csrf' => csrf_token(), 'user' => auth()->id()]);
</script>
<script>{!! file_get_contents(public_path('admin-shop-notifications.js')) !!}</script>
@endonce
@endif
