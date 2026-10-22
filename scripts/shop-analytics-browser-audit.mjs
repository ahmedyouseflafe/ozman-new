// Local isolated fixture only. Never send orders or messages to production.
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
async function shot(name){const r=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});await writeFile('storage/app/'+name+'.png',Buffer.from(r.data,'base64'));}
try{
    await send('Page.enable');await send('Runtime.enable');
    await send('Emulation.setDeviceMetricsOverride',{width:1440,height:1050,deviceScaleFactor:1,mobile:false});
    await send('Page.navigate',{url:base+'/login'});await until("document.readyState==='complete'");
    if(await evaluate("!!document.querySelector('input[name=email]')")){
        await evaluate("document.querySelector('input[name=email]').value='analytics-preview@example.test';document.querySelector('input[name=password]').value='Local-preview-only-2026';document.querySelector('input[name=password]').form.requestSubmit()");
        await until("!location.pathname.includes('/login')");
    }
    await send('Page.navigate',{url:base+'/admin/analytics'});await until("document.querySelectorAll('.metric').length===8");
    assert.equal(await evaluate("document.getElementById('analyticsError').hidden"),true);
    assert.equal(await evaluate("getComputedStyle(document.querySelector('.panel')).backgroundColor"),'rgb(255, 255, 255)');
    assert.equal(await evaluate("document.querySelectorAll('#shopsBody tr').length"),4);
    await shot('analytics-desktop');
    for(const [width,height] of [[320,740],[390,844],[768,1024],[1440,1050]]){
        await send('Emulation.setDeviceMetricsOverride',{width,height,deviceScaleFactor:1,mobile:width<800});await delay(200);
        const layout=await evaluate("({page:document.documentElement.scrollWidth,screen:innerWidth})");assert.ok(layout.page<=layout.screen+1,JSON.stringify(layout));
        await evaluate("document.querySelector('#ordersBody button').click()");await until("document.querySelector('#orderDetailBody .order-detail-line')");
        assert.equal(await evaluate("document.getElementById('orderDetail').matches(':modal')"),true);
        assert.equal(await evaluate("(()=>{const r=document.getElementById('orderDetail').getBoundingClientRect();return r.left>=0&&r.right<=innerWidth+1&&r.bottom<=innerHeight+1})()"),true);
        if(width===390)await shot('analytics-mobile-order');
        await evaluate("document.getElementById('closeOrder').click();document.getElementById('ozActivityToggle').click()");
        assert.equal(await evaluate("(()=>{const r=document.getElementById('ozActivityPanel').getBoundingClientRect();return r.left>=0&&r.right<=innerWidth+1&&r.top>=0})()"),true);
        await evaluate("document.getElementById('ozActivityClose').click();window.scrollTo(0,0)");
        if(width===390)await shot('analytics-mobile');
    }
    await evaluate("document.querySelector('#shopsBody button').click()");await until("document.querySelectorAll('#shopsBody tr').length===1");
    assert.notEqual(await evaluate("document.getElementById('shopFilter').value"),'');
    await evaluate("document.getElementById('shopFilter').value='';document.getElementById('analyticsFilters').requestSubmit()");await until("document.querySelectorAll('#shopsBody tr').length===4");
    await evaluate("document.getElementById('activityKind').value='sale_confirmed';document.getElementById('activityKind').dispatchEvent(new Event('change'))");
    await until("[...document.querySelectorAll('.activity-entry h3')].every(n=>n.textContent.includes('بيع مؤكد')) && document.querySelectorAll('.activity-entry').length>0");
    await evaluate("document.getElementById('ozActivityToggle').click();document.getElementById('ozActivityRead').click()");await until("document.getElementById('ozActivityCount').textContent==='0'");
    assert.equal(errors.length,0,JSON.stringify(errors));
    console.log('PASS: real admin login, white dashboard, 8 metrics, shop filter, sale activity filter, per-admin notification read, order modal, 4 viewport widths, no overflow or JS errors.');
}finally{ws.close();}
