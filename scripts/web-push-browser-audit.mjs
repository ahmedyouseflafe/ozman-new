// Run with Chrome --headless=new --remote-debugging-port=9227.
// Local fixture only: real service worker/DOM/network; simulated push provider.
import { createServer } from 'node:http';
import { readFileSync } from 'node:fs';
import assert from 'node:assert/strict';

const source = name => readFileSync(new URL('../public/'+name, import.meta.url),'utf8');
const key=Buffer.concat([Buffer.from([4]),Buffer.alloc(64,7)]).toString('base64url');
let saved=0, refreshed=0;
const server=createServer(async(req,res)=>{
    if(req.url==='/slow-body'){res.writeHead(200,{'Content-Type':'application/json'});res.flushHeaders();res.write('{');return;}
    if(req.url==='/csrf-token'){refreshed++;res.setHeader('Content-Type','application/json');res.end(JSON.stringify({token:'fresh'}));return;}
    if(req.url==='/subscribe'){
        let raw='';for await(const part of req)raw+=part;
        if(req.headers['x-csrf-token']!=='fresh'){res.writeHead(419);res.end('{}');return;}
        const data=JSON.parse(raw);assert.ok(data.subscription.keys);saved++;
        res.setHeader('Content-Type','application/json');res.end('{"registered":true}');return;
    }
    if(['/merchant-pwa-sw.js','/web-push-client.js','/offer-notifications.js','/restaurant-notification-reward.js'].includes(req.url)){
        res.setHeader('Content-Type','application/javascript');res.end(source(req.url.slice(1)));return;
    }
    res.setHeader('Content-Type','text/html');res.end(`<!doctype html><html lang="ar" dir="rtl"><head><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="expired"></head><body><h1>المحل</h1>
    <script>
    window.OZMAN_OFFER_NOTIFICATIONS=${JSON.stringify({showPrompt:true,title:'فعّل إشعارات العروض',body:'عروض كل المحلات',allowLabel:'تفعيل الإشعارات',iosLabel:'يمكنك التخطي',iosBodyLabel:'عروض اختيارية',skipLabel:'تخطي ومتابعة التصفح',deniedLabel:'اسمح بالإشعارات من المتصفح',requestingLabel:'جارٍ التفعيل',serviceWorkerUrl:'/merchant-pwa-sw.js',subscribeUrl:'/subscribe',vapidPublicKey:key,sourceShopId:1})};
    window.OZMAN_NOTIFICATION_REWARD={shopId:1,storageKey:'audit.customer.v1'};
    window.rewardActivations=0;
    document.addEventListener('restaurant:registered',()=>window.rewardActivations++);
    window.providerCalls=0;
    if(location.search.includes('hung')) {
        window.permissionCalls=0;
        Notification.requestPermission=()=>{window.permissionCalls++;return new Promise(resolve=>{window.resolvePermission=resolve;});};
        const originalTimer=window.setTimeout.bind(window);
        window.setTimeout=(fn,ms,...args)=>originalTimer(fn,ms===20000?100:ms,...args);
    }
    PushManager.prototype.getSubscription=async()=>null;
    PushManager.prototype.subscribe=async()=>{window.providerCalls++;return {toJSON:()=>({endpoint:'https://push.example.test/browser',keys:{auth:'test',p256dh:'test'}})};};
    </script><button id="notificationGiftEnable">Enable gift</button><script src="/restaurant-notification-reward.js"></script><script src="/web-push-client.js"></script><script src="/offer-notifications.js"></script></body></html>`);
});
await new Promise(resolve=>server.listen(8134,'127.0.0.1',resolve));
const origin='http://127.0.0.1:8134';
const targets=await(await fetch('http://127.0.0.1:9227/json')).json();
const ws=new WebSocket(targets.find(t=>t.type==='page').webSocketDebuggerUrl);
await new Promise((resolve,reject)=>{ws.onopen=resolve;ws.onerror=reject;});
let id=0;const pending=new Map();
ws.onmessage=({data})=>{const m=JSON.parse(data),p=pending.get(m.id);if(p){pending.delete(m.id);m.error?p.reject(m.error):p.resolve(m.result);}};
const send=(method,params={})=>new Promise((resolve,reject)=>{const key=++id;pending.set(key,{resolve,reject});ws.send(JSON.stringify({id:key,method,params}));});
const evaluate=async expression=>{const r=await send('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true});if(r.exceptionDetails)throw Error(r.exceptionDetails.exception?.description||r.exceptionDetails.text);return r.result.value;};
const wait=ms=>new Promise(r=>setTimeout(r,ms));
async function until(expression){for(let i=0;i<100;i++){if(await evaluate(expression))return;await wait(100);}throw Error('Timed out: '+expression);}
try{
    await send('Page.enable');
    await send('Page.navigate',{url:origin+'/?setup'});
    await until('!!window.OzmanOfferNotifications');
    await evaluate('sessionStorage.clear()');
    for(const width of [320,390,1280]){
        await send('Browser.setPermission',{permission:{name:'notifications'},setting:'prompt',origin});
        await send('Emulation.setDeviceMetricsOverride',{width,height:800,deviceScaleFactor:1,mobile:width<500});
        await send('Page.navigate',{url:origin+'/?width='+width});
        await until('!!document.querySelector("[data-offer-push-prompt]")?.open');
        assert.equal(await evaluate('window.OzmanRestaurantCustomer.token()'), '');
        assert.equal(await evaluate('window.rewardActivations'), 0);
        assert.equal(await evaluate(`(()=>{const r=document.querySelector('.offer-push-card').getBoundingClientRect();return r.left>=0&&r.right<=innerWidth&&document.querySelector('[data-offer-push-skip]').hidden;})()`),true);
        await send('Browser.setPermission',{permission:{name:'notifications'},setting:'granted',origin});
        await evaluate(`document.querySelector('[data-offer-push-enable]').click()`);
        await until(`!document.querySelector('[data-offer-push-prompt]')`);
        await until('window.rewardActivations > 0');
        assert.match(await evaluate('window.OzmanRestaurantCustomer.token()'), /^[a-f0-9]{64}$/);
        assert.equal(await evaluate('document.getElementById("notificationGiftEnable").hidden'), true);
        assert.equal(await evaluate(`document.body.style.overflow`),'');
        assert.equal(await evaluate(`(async()=> (await window.OzmanWebPush.register('/merchant-pwa-sw.js')).active.state)()`),'activated');
        assert.equal(await evaluate('window.providerCalls'),1);
        console.log('PASS real worker, CSRF recovery, saved subscription, dialog closes at '+width+'px');
    }
    assert.equal(saved,3);assert.equal(refreshed,3);
    assert.equal(await evaluate(`(async()=>{const old=setTimeout;window.setTimeout=(fn,ms,...args)=>old(fn,ms===20000?100:ms,...args);try{await OzmanWebPush.request('/slow-body');return 'unexpected';}catch(e){return e.code;}finally{window.setTimeout=old;}})()`),'network');
    await evaluate('OzmanOfferNotifications.prompt()');
    assert.equal(await evaluate('!!document.querySelector("[data-offer-push-prompt]")'),false);
    console.log('PASS slow response body timeout and already-enabled button');
    await send('Browser.setPermission',{permission:{name:'notifications'},setting:'denied',origin});
    await send('Page.navigate',{url:origin+'/?denied'});
    await until('!!document.querySelector("[data-offer-push-prompt]")?.open');
    assert.equal(await evaluate(`document.querySelector('[data-offer-push-skip]').hidden`),false);
    await evaluate(`document.querySelector('[data-offer-push-enable]').click()`);
    assert.equal(await evaluate('window.providerCalls'),0);
    await send('Browser.setPermission',{permission:{name:'notifications'},setting:'granted',origin});
    await evaluate(`window.dispatchEvent(new Event('focus'));document.querySelector('[data-offer-push-enable]').click()`);
    await until(`!document.querySelector('[data-offer-push-prompt]')`);
    assert.equal(saved,4);console.log('PASS blocked permission recovery after browser settings change');
    await send('Browser.setPermission',{permission:{name:'notifications'},setting:'prompt',origin});
    await send('Page.navigate',{url:origin+'/?hung'});
    await until('!!document.querySelector("[data-offer-push-prompt]")?.open');
    await evaluate(`document.querySelector('[data-offer-push-enable]').click()`);
    await until(`!document.querySelector('[data-offer-push-enable]').disabled`);
    assert.equal(await evaluate(`document.querySelector('[data-offer-push-status]').dataset.state`),'error');
    await evaluate(`document.querySelector('[data-offer-push-enable]').click()`);
    assert.equal(await evaluate('window.permissionCalls'),1);
    await evaluate(`document.querySelector('[data-offer-push-skip]').click();window.resolvePermission('granted')`);
    assert.equal(await evaluate('window.providerCalls'),0);
    assert.equal(await evaluate('document.body.style.overflow'),'');
    assert.equal(await evaluate('window.OzmanRestaurantCustomer.token()'), '');
    assert.equal(await evaluate('window.rewardActivations'), 0);
    await send('Page.navigate',{url:origin+'/?another-shop'});
    await until('!!window.OzmanOfferNotifications');
    await wait(100);
    assert.equal(await evaluate(`!!document.querySelector('[data-offer-push-prompt]')`),false);
    await evaluate(`sessionStorage.clear()`);
    console.log('PASS hanging permission timeout, single prompt, skip across shops, no false activation');
    await send('Emulation.setUserAgentOverride',{userAgent:'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1',platform:'iPhone'});
    await send('Browser.setPermission',{permission:{name:'notifications'},setting:'prompt',origin});
    await send('Page.navigate',{url:origin+'/?ios'});
    await until('!!document.querySelector("[data-offer-push-skip]")');
    assert.equal(await evaluate(`document.querySelector('[data-offer-push-enable]').hidden`),true);
    await evaluate(`document.querySelector('[data-offer-push-skip]').click()`);
    await until(`!document.querySelector('[data-offer-push-prompt]')`);
    assert.equal(saved,4);console.log('PASS iOS skip without installation or subscription');
}finally{await send('Emulation.setUserAgentOverride',{userAgent:''});ws.close();server.closeAllConnections();await new Promise(resolve=>server.close(resolve));}
