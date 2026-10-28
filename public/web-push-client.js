(() => {
    'use strict';
    if (window.OzmanWebPush) return;
    const registrations = new Map(), subscriptions = new Map();
    const failure = (code, cause) => Object.assign(new Error(code), { code, cause });
    function bounded(promise, code, milliseconds = 30000) {
        return new Promise((resolve, reject) => {
            const timer = setTimeout(() => reject(failure(code)), milliseconds);
            Promise.resolve(promise).then(value => { clearTimeout(timer);resolve(value); }, error => { clearTimeout(timer);reject(error); });
        });
    }
    async function active(registration) {
        // Wait for this registration, not serviceWorker.ready (which may resolve
        // to the separate /admin/ worker controlling the current page).
        if (registration.active?.state === 'activated') return registration;
        await new Promise((resolve, reject) => {
            const workers = new Set();
            const finish = error => {
                clearTimeout(timer);
                registration.removeEventListener('updatefound', check);
                workers.forEach(worker => worker.removeEventListener('statechange', check));
                error ? reject(error) : resolve();
            };
            const check = () => {
                if (registration.active?.state === 'activated') return finish();
                const worker = registration.installing || registration.waiting || registration.active;
                if (worker?.state === 'activated') return finish();
                if (worker?.state === 'redundant') return finish(failure('worker-failed'));
                if (worker && !workers.has(worker)) { workers.add(worker);worker.addEventListener('statechange', check); }
            };
            const timer = setTimeout(() => finish(failure('worker-timeout')), 20000);
            registration.addEventListener('updatefound', check);check();
        });
        return registration;
    }
    function register(url, scope = '/') {
        const id = new URL(scope, location.origin).href;
        if (!registrations.has(id)) {
            const promise = bounded(navigator.serviceWorker.register(url, { scope, updateViaCache: 'none' }), 'worker-timeout')
                .then(active).catch(error => {
                    registrations.delete(id); // A failed first attempt must not poison every retry.
                    throw failure(typeof error.code === 'string' ? error.code : 'worker-failed', error);
                });
            registrations.set(id, promise);
        }
        return registrations.get(id);
    }
    function decode(value) {
        try {
            const raw = atob(value.replace(/-/g, '+').replace(/_/g, '/') + '='.repeat((4 - value.length % 4) % 4));
            const key = Uint8Array.from(raw, c => c.charCodeAt(0));
            if (key.length !== 65 || key[0] !== 4) throw new Error('Invalid P-256 public key');
            return key;
        } catch (error) { throw failure('key-invalid', error); }
    }
    function subscription(registration, publicKey) {
        const id = registration.scope;
        if (subscriptions.has(id)) return subscriptions.get(id);
        const work = (async () => {
            await active(registration);
            const expected = decode(publicKey);
            let current = await registration.pushManager.getSubscription();
            if (current?.options?.applicationServerKey) {
                const actual = new Uint8Array(current.options.applicationServerKey);
                if (actual.length !== expected.length || actual.some((byte, i) => byte !== expected[i])) {
                    if (!await current.unsubscribe()) throw failure('subscription-reset');
                    current = null;
                }
            }
            return current || await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: expected });
        })();
        const promise = bounded(work, 'push-timeout').catch(error => {
            throw failure(typeof error.code === 'string' ? error.code : (error.name === 'NotAllowedError' ? 'permission-denied' : 'push-service'), error);
        });
        // Do not launch a second provider request while a timed-out browser
        // operation is still pending (PushManager has no abort API).
        work.then(() => subscriptions.delete(id), () => subscriptions.delete(id));
        subscriptions.set(id, promise);
        return promise;
    }
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    async function request(url, { method = 'GET', data, token = csrf(), retry = true } = {}) {
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 20000);
        let response;
        try {
            response = await fetch(url, { method, credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                ...(data === undefined ? {} : { body: JSON.stringify(data) }) });
            // Keep the timeout through the body download, not just headers.
            if (response.ok) {
                const body = await response.json().catch(error => { throw failure(error.name === 'AbortError' ? 'network' : 'invalid-response', error); });
                return body;
            }
        } catch (error) { throw typeof error.code === 'string' ? error : failure('network', error); }
        finally { clearTimeout(timer); }
        if (response.status === 419 && retry) {
            const fresh = await request('/csrf-token', { retry: false });
            if (!fresh.token) throw failure('http-419');
            document.querySelectorAll('meta[name="csrf-token"]').forEach(meta => { meta.content = fresh.token; });
            return request(url, { method, data, token: fresh.token, retry: false });
        }
        if (!response.ok) throw failure('http-' + response.status);
    }
    async function save(url, current, extra = {}) {
        const serialized = current.toJSON();
        serialized.contentEncoding = window.PushManager?.supportedContentEncodings?.[0] || 'aes128gcm';
        const body = await request(url, { method: 'POST', data: { ...extra, subscription: serialized } });
        if (body.registered !== true) throw failure('invalid-response');
        return current;
    }
    function message(error) {
        const messages = {
            'worker-failed': 'تعذّر تشغيل خدمة الإشعارات. حدّث الصفحة ثم حاول مجددًا.',
            'worker-timeout': 'تجهيز خدمة الإشعارات تأخر. تحقق من الاتصال واضغط تفعيل مجددًا.',
            'push-timeout': 'خدمة إشعارات المتصفح لم تستجب. تحقق من الاتصال بخدمات Google أو Apple ثم حاول مجددًا.',
            'push-service': 'المتصفح لم ينشئ اشتراك الإشعارات. جرّب متصفحًا محدّثًا وتحقق من اتصالك بخدمة الإشعارات.',
            'permission-denied': 'الإشعارات محظورة. اسمح بها من إعدادات الموقع بجانب عنوان الصفحة ثم حاول مجددًا.',
            'key-invalid': 'إعداد مفتاح الإشعارات في الموقع غير صالح. يلزم إصلاحه من الإدارة.',
            'subscription-reset': 'تعذّر تحديث اشتراك الجهاز القديم. حدّث الصفحة وحاول مجددًا.',
            'network': 'تعذّر الاتصال بالسيرفر لحفظ التفعيل. تحقق من الاتصال وحاول مجددًا.',
            'http-401': 'انتهى تسجيل الدخول. سجّل الدخول مجددًا لتفعيل إشعارات حسابك.',
            'http-403': 'الحساب الحالي لا يملك صلاحية تفعيل هذه الإشعارات.',
            'http-409': 'الجهاز مربوط بحساب آخر. سجّل الخروج من الحساب السابق أولًا.',
            'http-419': 'تعذّر تحديث الجلسة. اسمح بملفات تعريف الارتباط ثم حدّث الصفحة.',
            'http-422': 'السيرفر رفض بيانات الاشتراك. حدّث الصفحة وحاول مجددًا.',
            'http-429': 'محاولات كثيرة خلال وقت قصير. انتظر دقيقة ثم حاول مجددًا.',
            'http-500': 'تعذّر حفظ الاشتراك بسبب خطأ بالسيرفر. يلزم فحص سجل الأخطاء من الإدارة.',
            'http-503': 'خدمة الإشعارات غير جاهزة على السيرفر. يلزم فحص الإعدادات وتحديث قاعدة البيانات.',
            'invalid-response': 'السيرفر لم يؤكد حفظ الاشتراك. حدّث الصفحة وسجّل الدخول إذا طُلب.'
        };
        return (messages[error.code] || 'تعذّر تفعيل الإشعارات. حاول مجددًا.') + ' [' + (error.code || 'unknown') + ']';
    }
    window.OzmanWebPush = { register, active, subscription, request, save, message };
})();
