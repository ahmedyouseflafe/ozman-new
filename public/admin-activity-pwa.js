(() => {
    'use strict';
    const $ = id => document.getElementById(id), config = window.OzmanActivityConfig;
    if (!$('adminPush') || !config) return;
    const standalone = () => matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
    const ios = /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    let registration, subscription, key, installPrompt, busy = false;
    const status = message => {$('adminPushStatus').textContent = message;};
    function installed() {
        // Standalone may be the parent Ozman app, not this administration PWA.
        // Keep installation help reachable instead of assuming this app is installed.
        $('adminInstall').hidden = false;
        $('adminInstall').textContent = standalone() ? 'خيارات تثبيت تطبيق الإدارة' : 'تثبيت تطبيق الإدارة';
        $('adminInstallState').textContent = standalone() ? 'مفتوح بوضع التطبيق' : 'تطبيق الإدارة';
    }
    installed();
    window.addEventListener('beforeinstallprompt', event => {event.preventDefault();installPrompt = event;});
    window.addEventListener('appinstalled', () => {installPrompt = null;$('adminInstall').textContent = 'تعليمات فتح تطبيق الإدارة';$('adminInstallState').textContent = 'تم التثبيت';});
    function installHelp() {
        $('adminInstallHelp').hidden = false;
        $('adminInstallHelp').textContent = ios
            ? 'على الآيفون: افتح هذه اللوحة في Safari، اضغط مشاركة (أو قائمة المزيد ثم مشاركة)، اختر «إضافة إلى الشاشة الرئيسية» ثم «إضافة». افتح الأيقونة الجديدة، سجّل الدخول إن طُلب، ثم فعّل الإشعارات. تتطلب الإشعارات iOS 16.4 أو أحدث.'
            : 'على أندرويد: افتح '+location.origin+'/admin/analytics في Chrome مباشرة، ثم افتح قائمة ⋮ واختر «إضافة إلى الشاشة الرئيسية» ثم «تثبيت» إذا ظهر. إذا ظهر «فتح التطبيق» فهو مثبّت بالفعل. إذا فتحت اللوحة من واتساب أو تطبيق أوزمان، افتح الرابط في Chrome أولًا.';
    }
    $('adminInstall').onclick = async () => {
        const prompt = installPrompt;
        if (prompt) {
            installPrompt = null;
            try {
                await prompt.prompt();
                const choice = await prompt.userChoice;
                if (choice.outcome === 'accepted') {
                    $('adminInstallHelp').hidden = false;
                    $('adminInstallHelp').textContent = 'وافقت على التثبيت. بعد اكتماله افتح أيقونة «إدارة أوزمان» من الشاشة الرئيسية، ثم فعّل الإشعارات.';
                    return;
                }
            } catch { /* A stale/unavailable prompt falls back to visible instructions. */ }
        }
        installHelp();
    };
    function controls(active) {
        $('adminPushEnable').hidden = active;
        $('adminPushTest').hidden = !active;
        $('adminPushDisable').hidden = !active;
    }
    async function api(path, method = 'POST', data) {
        try {
            return await window.OzmanWebPush.request(config.base+'/push/'+path, {method, data});
        } catch (error) { throw new Error(window.OzmanWebPush.message(error)); }
    }
    async function save() {
        const result = await api('subscription', 'POST', {...subscription.toJSON(), device_name: ios ? 'iPhone / iPad' : /Android/.test(navigator.userAgent) ? 'Android' : 'متصفح الإدارة'});
        if (result.registered !== true) throw new Error('السيرفر لم يؤكد حفظ الاشتراك. حاول مجددًا.');
        return result;
    }
    async function prepare() {
        if (ios && !standalone()) {
            status('ثبّت اللوحة على الشاشة الرئيسية وافتحها من الأيقونة لتفعيل إشعارات الآيفون.');
            $('adminInstall').click(); return;
        }
        if (!isSecureContext || !('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
            status('إشعارات الخلفية غير مدعومة هنا. افتح اللوحة في متصفح حديث أو من التطبيق المثبّت.');return;
        }
        registration = await window.OzmanWebPush.register('/admin/analytics/sw.js', '/admin/');
        key = (await api('key', 'GET')).key;
        subscription = await registration.pushManager.getSubscription();
        if (subscription?.options?.applicationServerKey) {
            const actual = btoa(String.fromCharCode(...new Uint8Array(subscription.options.applicationServerKey))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
            if (actual !== key.replace(/=+$/, '')) {
                controls(false);$('adminPushEnable').disabled = false;
                status('تغيّر إعداد الإشعارات. اضغط تفعيل لتحديث اشتراك هذا الجهاز.');return;
            }
        }
        if (subscription && Notification.permission === 'granted') {
            const device = await api('status', 'POST', {endpoint: subscription.endpoint});
            controls(device.registered);
            if (device.registered) {
                await save();
                status(device.failed ? 'الإشعارات مفعّلة، لكن بعض الإرسالات تعثّرت. جرّب إرسال إشعار تجريبي.'
                    : 'إشعارات كل المحلات مفعّلة على هذا الجهاز، حتى بعد إغلاق اللوحة.'+(device.pending ? ' توجد إشعارات بانتظار إعادة الإرسال.' : ''));
            } else status('اضغط تفعيل لربط إشعارات هذا الجهاز بحسابك.');
        } else {controls(false);status(Notification.permission === 'denied' ? 'الإشعارات محظورة. اسمح بها من إعدادات الموقع أو إعدادات إشعارات التطبيق، ثم حدّث الصفحة.' : 'اضغط تفعيل، ثم وافق على السماح بالإشعارات.');}
        $('adminPushEnable').disabled = false;
    }
    async function run(action) {
        if (busy) return;busy = true;
        for (const id of ['adminPushEnable','adminPushTest','adminPushDisable']) $(id).disabled = true;
        try {await action();} catch (error) {status((error.code ? window.OzmanWebPush.message(error) : error.message) || 'تعذّر إكمال العملية. حاول ثانية.');}
        finally {busy = false;for (const id of ['adminPushEnable','adminPushTest','adminPushDisable']) $(id).disabled = false;}
    }
    $('adminPushEnable').onclick = () => run(async () => {
        // Permission must be requested directly during the tap, before any network await (Safari).
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {status('لم يتم السماح بالإشعارات. يمكنك تفعيلها لاحقًا من إعدادات الجهاز.');return;}
        registration = await window.OzmanWebPush.register('/admin/analytics/sw.js', '/admin/');
        key = (await api('key', 'GET')).key;
        subscription = await window.OzmanWebPush.subscription(registration, key);
        await save();controls(true);status('تم التفعيل. اضغط «إرسال إشعار تجريبي» وتأكد من ظهوره على الجوال.');
    });
    $('adminPushTest').onclick = () => run(async () => {const result = await api('test', 'POST', {endpoint: subscription.endpoint});status(result.message);});
    $('adminPushDisable').onclick = () => run(async () => {
        await api('subscription', 'DELETE', {endpoint: subscription.endpoint});
        await subscription.unsubscribe();subscription = null;controls(false);status('تم إيقاف إشعارات هذا الجهاز. باقي أجهزتك تبقى مفعّلة.');
    });
    prepare().catch(error => {
        controls(false);
        $('adminPushEnable').disabled = false;
        status((error.code ? window.OzmanWebPush.message(error) : error.message) || 'تعذّر تجهيز الإشعارات. حدّث الصفحة وحاول مجددًا.');
    });
})();
