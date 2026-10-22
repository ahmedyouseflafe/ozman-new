// Local fixture only. Push subscriptions and transport are mocked; no real notification is sent.
import assert from 'node:assert/strict';
import {writeFile} from 'node:fs/promises';
const base='http://127.0.0.1:8032';
const targets=await(await fetch('http://127.0.0.1:9227/json')).json();
const ws=new WebSocket(targets.find(t=>t.type==='page').webSocketDebuggerUrl);
await new Promise((resolve,reject)=>{ws.onopen=resolve;ws.onerror=reject;});
let sequence=0;const pending=new Map(),errors=[];
ws.onmessage=({data})=>{const m=JSON.parse(data);if(m.method==='Runtime.exceptionThrown')errors.push(m.params.exceptionDetails);const p=pending.get(m.id);if(p){pending.delete(m.id);m.error?p.reject(m.error):p.resolve(m.result);}};
const send=(method,params={})=>new Promise((resolve,reject)=>{const id=++sequence;pending.set(id,{resolve,reject});ws.send(JSON.stringify({id,method,params}));});
const evaluate=async expression=>{const r=await send('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true});if(r.exceptionDetails)throw new Error(JSON.stringify(r.exceptionDetails));return r.result.value;};
const delay=ms=>new Promise(r=>setTimeout(r,ms));
async function until(expr){for(let i=0;i<150;i++){if(await evaluate(expr))return;await delay(150);}throw new Error('Timed out: '+expr);}
async function click(id){await evaluate(`document.getElementById('${id}').scrollIntoView({block:'center'})`);const r=await evaluate(`(()=>{const r=document.getElementById('${id}').getBoundingClientRect();return {x:r.x+r.width/2,y:r.y+r.height/2}})()`);await send('Input.dispatchMouseEvent',{type:'mousePressed',button:'left',clickCount:1,...r});await send('Input.dispatchMouseEvent',{type:'mouseReleased',button:'left',clickCount:1,...r});}
let injected;
try {
    await send('Page.enable');await send('Runtime.enable');
    await send('Page.navigate',{url:base+'/login'});await until("document.readyState==='complete'");
    if(await evaluate("!!document.querySelector('input[name=email]')")){
        await evaluate("document.querySelector('input[name=email]').value='analytics-preview@example.test';document.querySelector('input[name=password]').value='Local-preview-only-2026';document.querySelector('input[name=password]').form.requestSubmit()");
        await until("!location.pathname.includes('/login')");
    }
    injected=(await send('Page.addScriptToEvaluateOnNewDocument',{source:`
        window.pushAudit={calls:[],registered:false,sub:null,gesture:false};
        Object.defineProperty(Notification,'permission',{get:()=> 'granted'});
        Notification.requestPermission=async()=>{pushAudit.gesture=navigator.userActivation.isActive;return 'granted';};
        PushManager.prototype.getSubscription=async()=>pushAudit.sub;
        PushManager.prototype.subscribe=async()=>pushAudit.sub={endpoint:'https://fcm.googleapis.com/fcm/send/local-preview',options:{},
            toJSON(){return {endpoint:this.endpoint,keys:{p256dh:'mock',auth:'mock'}}},unsubscribe:async()=>{pushAudit.sub=null;return true;}};
        const original=window.fetch;window.fetch=async(input,options={})=>{
            const url=String(input);if(url.includes('/push/')&&!url.endsWith('/key')){
                pushAudit.calls.push({url,method:options.method,body:options.body});
                if(url.endsWith('/subscription'))pushAudit.registered=options.method!=='DELETE';
                return new Response(JSON.stringify(url.endsWith('/test')?{accepted:true,message:'Accepted test fixture'}:{registered:pushAudit.registered}),{headers:{'Content-Type':'application/json'}});
            }return original(input,options);
        };` })).identifier;
    await send('Page.navigate',{url:base+'/admin/analytics'});
    await until("document.getElementById('adminPushEnable') && !document.getElementById('adminPushEnable').disabled");
    assert.equal(await evaluate("(async () => (await navigator.serviceWorker.getRegistration('/admin/analytics')).scope)()"),base+'/admin/');
    const manifest=await evaluate("fetch('/admin/analytics/manifest.webmanifest').then(r=>r.json())");
    assert.equal(manifest.id,'/admin/analytics');assert.equal(manifest.scope,'/admin/');
    for(const [width,height] of [[320,740],[390,844],[768,1024],[1440,1000]]){
        await send('Emulation.setDeviceMetricsOverride',{width,height,deviceScaleFactor:1,mobile:width<800});await delay(150);
        assert.ok(await evaluate("document.documentElement.scrollWidth<=innerWidth+1"),'overflow at '+width);
        await evaluate("document.getElementById('adminPush').scrollIntoView({block:'center'})");
        if(width===390||width===1440){const shot=await send('Page.captureScreenshot',{format:'png'});await writeFile('storage/app/admin-push-'+width+'.png',Buffer.from(shot.data,'base64'));}
    }
    await click('adminPushEnable');await until('pushAudit.registered && !document.getElementById("adminPushTest").hidden');
    assert.equal(await evaluate('pushAudit.gesture'),true,'permission requested in original tap');
    await click('adminPushTest');await until('pushAudit.calls.some(c=>c.url.endsWith("/test"))');
    await click('adminPushDisable');await until('!pushAudit.registered && document.getElementById("adminPushTest").hidden');
    assert.equal(await evaluate('pushAudit.sub'),null);
    // iPhone install guidance is shown before permission requests in a normal Safari tab.
    await send('Page.removeScriptToEvaluateOnNewDocument',{identifier:injected});injected=null;
    await send('Emulation.setUserAgentOverride',{userAgent:'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Version/18.0 Mobile/15E148 Safari/604.1'});
    await send('Page.navigate',{url:base+'/admin/analytics'});await until("!!document.getElementById('adminInstallHelp') && !document.getElementById('adminInstallHelp').hidden");
    assert.ok(await evaluate("document.getElementById('adminInstallHelp').textContent.includes('إضافة إلى الشاشة الرئيسية')"));
    assert.equal(await evaluate("document.getElementById('adminPushEnable').disabled"),true);
    assert.equal(errors.length,0,JSON.stringify(errors));
    console.log('PASS: isolated real admin service worker + manifest; 4 widths; enable/test/disable UI; permission in tap; iPhone install guidance; no external push sends.');
} finally {
    if(injected)await send('Page.removeScriptToEvaluateOnNewDocument',{identifier:injected});
    await send('Emulation.setUserAgentOverride',{userAgent:'Mozilla/5.0 Chrome/130.0.0.0 Safari/537.36'});
    ws.close();
}
