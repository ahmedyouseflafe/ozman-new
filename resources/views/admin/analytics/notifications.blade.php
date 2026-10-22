@if(auth()->user()?->isSuperAdmin() && auth()->user()->is_active)
@once
<style>
.oz-activity-bell{position:fixed;bottom:max(24px,env(safe-area-inset-bottom));left:22px;z-index:9000;font:13px/1.7 Tahoma,Arial,sans-serif;direction:rtl;color:#17283e}.oz-activity-trigger{border:1px solid #dfe6f1;background:white;color:#245be7;border-radius:30px;box-shadow:0 5px 25px #17283e20;padding:12px 18px;cursor:pointer;font:inherit;display:flex;align-items:center;gap:9px;min-height:46px}.oz-activity-trigger b{background:#245be7;color:white;border-radius:20px;padding:0 7px;min-width:22px;text-align:center;font-size:11px}.oz-activity-panel{position:absolute;bottom:58px;left:0;background:white;color:#17283e;border:1px solid #dfe6f1;box-shadow:0 15px 65px #15253d33;border-radius:15px;padding:17px;width:min(390px,calc(100vw - 30px));max-height:calc(100dvh - 145px);overflow:auto;overscroll-behavior:contain}.oz-activity-panel[hidden]{display:none}.oz-activity-head{display:flex;align-items:center;justify-content:space-between;gap:8px}.oz-activity-panel button,.oz-activity-panel a{font:inherit;color:#245be7;background:none;border:0;cursor:pointer;text-decoration:none}.oz-activity-panel button:focus-visible,.oz-activity-trigger:focus-visible{outline:3px solid #92b6ff}.oz-activity-panel small{display:block;font-size:10px;color:#758398;margin:8px 0}.oz-activity-tools{display:flex;flex-wrap:wrap;gap:8px;border-bottom:1px solid #e7ecf2;padding-bottom:12px}.oz-activity-tools button{font-size:11px;padding:4px 0}.oz-activity-item{border-bottom:1px solid #edf1f6;padding:12px 0}.oz-activity-item.unread{border-right:3px solid #245be7;padding-right:9px}.oz-activity-item strong{font-size:12px;display:block;overflow-wrap:anywhere}.oz-activity-item p{color:#758398;font-size:11px;margin:4px 0}.oz-activity-item a,.oz-activity-item button{font-size:11px}.oz-activity-error{color:#a13428!important}.oz-activity-footer{display:block;padding-top:14px;font-size:12px}.oz-activity-toast{position:absolute;bottom:60px;left:0;background:#17376d;color:white;border-radius:12px;padding:14px;width:min(320px,calc(100vw - 35px));box-shadow:0 8px 30px #14283e33}.oz-activity-toast[hidden]{display:none}@media(max-width:650px){.oz-activity-bell{left:12px;bottom:max(14px,env(safe-area-inset-bottom))}.oz-activity-trigger{font-size:12px;padding:10px 14px}}
/* Keep the phone navigation accessible, including its safe-area padding. */
@media(max-width:900px){
    .oz-activity-bell{left:12px;bottom:calc(14px + env(safe-area-inset-bottom,0px));z-index:70}
    body:has(.admin-mobile-nav) .oz-activity-bell{bottom:calc(98px + env(safe-area-inset-bottom,0px))}
    .oz-activity-trigger{position:relative;box-sizing:border-box;width:48px;height:48px;min-height:48px;padding:0;justify-content:center}
    .oz-activity-trigger .oz-activity-label{display:none}
    .oz-activity-trigger b{position:absolute;top:-5px;right:-5px;box-sizing:border-box;min-width:21px;line-height:21px;padding:0 5px;border:2px solid white}
    .oz-activity-panel{box-sizing:border-box;max-height:calc(100dvh - 90px - env(safe-area-inset-bottom,0px) - env(safe-area-inset-top,0px))}
    body:has(.admin-mobile-nav) .oz-activity-panel{max-height:calc(100dvh - 174px - env(safe-area-inset-bottom,0px) - env(safe-area-inset-top,0px))}
    .oz-activity-toast{box-sizing:border-box;pointer-events:none}
}
</style>
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
