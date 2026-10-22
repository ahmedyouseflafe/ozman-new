// Requires the guarded local analytics fixture. No WhatsApp navigation occurs.
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {execFileSync} from 'node:child_process';
const base='http://127.0.0.1:8032';
const targets=await(await fetch('http://127.0.0.1:9227/json')).json(),ws=new WebSocket(targets.find(t=>t.type==='page').webSocketDebuggerUrl);
await new Promise((resolve,reject)=>{ws.onopen=resolve;ws.onerror=reject;});
let seq=0;const pending=new Map(),errors=[];
ws.onmessage=({data})=>{const m=JSON.parse(data);if(m.method==='Runtime.exceptionThrown')errors.push(m.params.exceptionDetails);const p=pending.get(m.id);if(p){pending.delete(m.id);m.error?p.reject(m.error):p.resolve(m.result);}};
const send=(method,params={})=>new Promise((resolve,reject)=>{const id=++seq;pending.set(id,{resolve,reject});ws.send(JSON.stringify({id,method,params}));});
const evaluate=async expression=>{const r=await send('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true});if(r.exceptionDetails)throw new Error(JSON.stringify(r.exceptionDetails));return r.result.value;};
const delay=ms=>new Promise(r=>setTimeout(r,ms));
async function until(expr){for(let i=0;i<200;i++){if(await evaluate(expr))return;await delay(150);}throw new Error('Timed out '+expr);}
try {
    await send('Page.enable');await send('Runtime.enable');
    const helper=(await readFile('public/catalog-checkout.js','utf8')).replace('window.location.assign(url.href);','window.capturedCheckoutUrl=url.href;');
    for(const [path,add,name,phone] of [['/sweets/checkout-sweets','[data-add]','customerName','customerPhone'],['/fashion/checkout-clothing','.direct-add','buyerName','buyerPhone'],['/home-furniture/checkout-furniture_appliances','[data-add]','customerName','customerPhone']]){
        await send('Page.navigate',{url:base+path});await until(`!!window.OzmanSaveCatalogOrder && !!document.querySelector(${JSON.stringify(add)})`);
        await evaluate(helper);
        await evaluate(`document.querySelector(${JSON.stringify(add)}).click();document.getElementById(${JSON.stringify(name)}).value='Local buyer';document.getElementById(${JSON.stringify(phone)}).value='0591234567';document.getElementById('sendOrder').click()`);
        await until('!!window.capturedCheckoutUrl');
        const url=await evaluate('window.capturedCheckoutUrl');assert.ok(url.startsWith('https://wa.me/972501234567?'));assert.ok(decodeURIComponent(url).includes('80.00'));assert.ok(decodeURIComponent(url).includes('SHOP-'));
        await evaluate("window.capturedCheckoutUrl='';document.getElementById('sendOrder').click()");await until('!!window.capturedCheckoutUrl');assert.equal(await evaluate('window.capturedCheckoutUrl'),url,'retry must reuse order');
    }
    await send('Page.navigate',{url:base+'/admin/analytics'});await until("document.querySelectorAll('.metric').length===8 && document.getElementById('ozActivityItems').children.length>0");
    await evaluate("window.receivedShopEvents=0;document.addEventListener('ozman:shop-event',()=>window.receivedShopEvents++)");
    const env={...process.env,APP_CONFIG_CACHE:'C:/Users/Ahmad/Documents/project/Ozman/bootstrap/cache/analytics-browser-config.php',DB_CONNECTION:'sqlite',DB_DATABASE:'C:/Users/Ahmad/Documents/project/Ozman/storage/app/analytics-browser.sqlite',DB_URL:'',APP_ENV:'testing',SESSION_DRIVER:'file',CACHE_STORE:'array'};
    const args=['-d','extension_dir=C:/xampp/php/ext'];for(const ext of ['mbstring','pdo_sqlite','gd','openssl','fileinfo'])args.push('-d','extension='+ext);args.push('storage/app/analytics-sale-fixture.php');
    const number=execFileSync('php',args,{env,encoding:'utf8'}).trim();
    await until('window.receivedShopEvents>0');
    assert.ok(await evaluate(`document.getElementById('ozActivityItems').textContent.includes(${JSON.stringify(number)})`));
    assert.equal(errors.length,0,JSON.stringify(errors));
    console.log('PASS: three real storefront checkouts stored locally, authoritative price, no WhatsApp send, idempotent retry, and live sale notification from an actual database status change.');
} finally {ws.close();}
