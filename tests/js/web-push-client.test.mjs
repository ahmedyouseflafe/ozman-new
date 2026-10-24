import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

const source = readFileSync(new URL('../../public/web-push-client.js', import.meta.url), 'utf8');
const key = Buffer.concat([Buffer.from([4]), Buffer.alloc(64, 7)]).toString('base64url');
const current = () => ({ toJSON: () => ({ endpoint: 'https://push.example/sub', keys: { auth: 'token', p256dh: key } }) });
function environment(overrides = {}) {
    const metas = [{ content: 'expired' }, { content: 'expired' }];
    const window = { PushManager: { supportedContentEncodings: ['aes128gcm'] } };
    const context = { window, navigator: {}, location: { origin: 'https://ozman.test' },
        document: { querySelector: () => metas[0], querySelectorAll: () => metas },
        URL, Uint8Array, atob, AbortController, setTimeout, clearTimeout, ...overrides };
    runInNewContext(source, context);
    return { api: window.OzmanWebPush, metas };
}
function registration(state = 'activated') {
    const worker = new EventTarget();worker.state = state;
    const result = new EventTarget();result.scope = 'https://ozman.test/';
    result.active = state === 'activated' ? worker : null;
    result.installing = result.active ? null : worker;
    result.pushManager = { getSubscription: async () => null, subscribe: async () => current() };
    result.activate = () => { worker.state = 'activated';result.active = worker;worker.dispatchEvent(new Event('statechange')); };
    return result;
}

test('first installation waits for the selected worker to activate before subscribing', async () => {
    const reg = registration('installing');let called = false;
    reg.pushManager.subscribe = async () => { assert.equal(reg.active.state, 'activated');called = true;return current(); };
    const { api } = environment({ navigator: { serviceWorker: { register: async () => reg } } });
    const promise = api.register('/merchant-pwa-sw.js').then(r => api.subscription(r, key));
    await new Promise(resolve => setTimeout(resolve, 10));assert.equal(called, false);
    reg.activate();assert.ok(await promise);assert.equal(called, true);
});

test('failed registration can be retried without reloading the page', async () => {
    let calls = 0;const reg = registration();
    const { api } = environment({ navigator: { serviceWorker: { register: async () => { if (++calls === 1) throw Error('network');return reg; } } } });
    await assert.rejects(api.register('/sw.js'), { code: 'worker-failed' });
    assert.equal(await api.register('/sw.js'), reg);assert.equal(calls, 2);
});

test('merchant and offer activation share one browser subscription operation', async () => {
    const reg = registration();let calls = 0;
    reg.pushManager.subscribe = async () => { calls++;await new Promise(resolve => setTimeout(resolve, 10));return current(); };
    const { api } = environment();
    const [a,b] = await Promise.all([api.subscription(reg,key),api.subscription(reg,key)]);
    assert.equal(a,b);assert.equal(calls,1);
});

test('a rotated server key replaces the stale subscription, matching keys are preserved', async () => {
    for (const matches of [true,false]) {
        let removed = 0,created = 0;
        const reg = registration();const old = { ...current(), options: { applicationServerKey: Uint8Array.from(Buffer.from(matches ? key : Buffer.alloc(65).toString('base64url'), 'base64url')).buffer }, unsubscribe: async () => { removed++;return true; } };
        reg.pushManager.getSubscription = async () => old;
        reg.pushManager.subscribe = async () => { created++;return current(); };
        await environment().api.subscription(reg,key);
        assert.equal(removed,matches ? 0 : 1);assert.equal(created,matches ? 0 : 1);
    }
});

test('expired CSRF is refreshed once, all meta tokens updated, same payload retried', async () => {
    const calls=[];
    const { api,metas } = environment({ fetch: async (url,options) => {
        calls.push({url,options});
        if (calls.length===1) return {ok:false,status:419};
        if(url==='/csrf-token') return {ok:true,json:async()=>({token:'fresh'})};
        return {ok:true,json:async()=>({registered:true})};
    } });
    await api.save('/subscribe',current(),{source_shop_id:1});
    assert.equal(calls.length,3);assert.equal(calls[2].options.headers['X-CSRF-TOKEN'],'fresh');
    assert.equal(calls[0].options.body,calls[2].options.body);
    assert.ok(metas.every(meta=>meta.content==='fresh'));
});

test('persistent session errors do not enter an infinite retry loop', async () => {
    let calls=0;
    const {api}=environment({fetch:async url=>{calls++;return url==='/csrf-token'?{ok:true,json:async()=>({token:'fresh'})}:{ok:false,status:419};}});
    await assert.rejects(api.save('/subscribe',current()),{code:'http-419'});assert.equal(calls,3);
});

test('HTML login pages and unconfirmed JSON never count as successful activation', async () => {
    for (const json of [async()=>{throw Error('HTML');},async()=>({})]) {
        const {api}=environment({fetch:async()=>({ok:true,json})});
        await assert.rejects(api.save('/subscribe',current()),{code:'invalid-response'});
    }
});

test('server, permission and network failures remain distinguishable and retryable', async () => {
    for(const status of [401,403,422,429,500,503]) {
        const {api}=environment({fetch:async()=>({ok:false,status})});
        await assert.rejects(api.save('/subscribe',current()),{code:`http-${status}`});
    }
    const reg=registration();let calls=0;
    reg.pushManager.subscribe=async()=>{if(++calls===1)throw Object.assign(Error('denied'),{name:'NotAllowedError'});return current();};
    const {api}=environment();
    await assert.rejects(api.subscription(reg,key),{code:'permission-denied'});
    assert.ok(await api.subscription(reg,key));
    await assert.rejects(environment({fetch:async()=>{throw Error('offline');}}).api.save('/subscribe',current()),{code:'network'});
});

test('invalid VAPID keys are rejected before contacting the push provider', async () => {
    const reg=registration();reg.pushManager.subscribe=()=>assert.fail('must not subscribe');
    await assert.rejects(environment().api.subscription(reg,'AAAA'),{code:'key-invalid'});
});

test('DOMException numeric codes do not hide push provider failures', async () => {
    const reg=registration();
    reg.pushManager.subscribe=async()=>{throw Object.assign(Error('push service unavailable'),{name:'AbortError',code:20});};
    await assert.rejects(environment().api.subscription(reg,key),{code:'push-service'});
});

test('admin and storefront registrations remain separate', async () => {
    const scopes=[];
    const {api}=environment({navigator:{serviceWorker:{register:async(url,options)=>{
        scopes.push(options.scope);const reg=registration();reg.scope='https://ozman.test'+options.scope;return reg;
    }}}});
    const [shop,admin]=await Promise.all([api.register('/merchant-pwa-sw.js'),api.register('/admin/analytics/sw.js','/admin/')]);
    assert.notEqual(shop,admin);assert.deepEqual(scopes,['/','/admin/']);
});
