// Local test: mock order/reward APIs and intercept only the final navigation.
// No messages, orders, image downloads, or native sharing are sent.
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

const url = process.argv[2] || 'http://127.0.0.1:8031/cosmetics/elegance-perfumes';
const targets = await (await fetch(process.env.CDP_URL || 'http://127.0.0.1:9227/json')).json();
const ws = new WebSocket(targets.find(target => target.type === 'page').webSocketDebuggerUrl);
await new Promise((resolve, reject) => { ws.onopen = resolve; ws.onerror = reject; });
let sequence = 0;
const pending = new Map(), errors = [];
ws.onmessage = ({ data }) => {
    const message = JSON.parse(data);
    if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.text);
    const request = pending.get(message.id);
    if (request) {
        pending.delete(message.id);
        message.error ? request.reject(message.error) : request.resolve(message.result);
    }
};
const send = (method, params = {}) => new Promise((resolve, reject) => {
    const id = ++sequence;
    pending.set(id, { resolve, reject }); ws.send(JSON.stringify({ id, method, params }));
});
const evaluate = async expression => {
    const result = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
    if (result.exceptionDetails) throw new Error(JSON.stringify(result.exceptionDetails));
    return result.result.value;
};
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
async function until(expression) {
    for (let i = 0; i < 100; i++) { if (await evaluate(expression)) return; await delay(100); }
    throw new Error(`Timed out: ${expression}`);
}
async function metrics(width, height) {
    await send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: width < 1000 });
    await delay(150);
}
async function tap(id) {
    const point = await evaluate(`(()=>{const e=document.getElementById('${id}');e.scrollIntoView({block:'center',behavior:'instant'});const r=e.getBoundingClientRect();return {x:r.x+r.width/2,y:r.y+r.height/2}})()`);
    await send('Input.dispatchMouseEvent',{type:'mousePressed',button:'left',clickCount:1,...point});
    await send('Input.dispatchMouseEvent',{type:'mouseReleased',button:'left',clickCount:1,...point});
    await delay(100);
}
try {
    assert.ok(['localhost','127.0.0.1'].includes(new URL(url).hostname));
    await send('Page.enable');await send('Runtime.enable');await metrics(390,844);
    await send('Page.navigate',{url});
    await until(`document.readyState==='complete' && !!window.OzmanOrderWhatsapp`);
    await delay(2000);
    await evaluate(`document.querySelector('#shopSignupWheel')?.close();
        [...document.querySelectorAll('button')].find(b=>['Later','لاحقًا','لاحقاً'].includes(b.textContent.trim()))?.click();
        window.__orders=0;window.__navigations=[];window.__reward=false;window.__lookups=0;
        window.__text='طلب 100 ₪\\nصورة المنتج: https://ozman.online/storage/products/perfume.jpg\\nللرجوع والتسوّق: https://ozman.online/cosmetics/elegance-perfumes';
        window.__data={shop_id:window.OZMAN_ELEGANCE_CHECKOUT.shopId,order_number:'COS-TEST',text:__text,
            whatsapp_url:'https://wa.me/972501234567?text='+encodeURIComponent(__text)};
        window.__realFetch=window.fetch;
        window.fetch=async(input,options={})=>{
            const path=new URL(String(input),location.href).pathname;
            if(options.method==='POST' && path.endsWith('/orders')){__orders++;return Response.json({order_number:__data.order_number,total:100,
                order_share_url:location.origin+'/__audit-share',order_share:__data,whatsapp_url:__data.whatsapp_url,
                purchase_wheel:__reward?{url:'/__audit-wheel',spin_url:'/__audit-spin'}:null});}
            if(path==='/__audit-share'){__lookups++;return Response.json(__data);}
            if(path==='/__audit-wheel')return Response.json({shop_id:__data.shop_id,whatsapp_url:__data.whatsapp_url,order_share_url:location.origin+'/__audit-share',
                wheel:{title:'Wheel',segments:[{label:'Gift',color:'#ff0000'},{label:'Gift 2',color:'#00ff00'}]},reward:null});
            if(path==='/__audit-spin'){__data.text+=' Purchase prize: Gift';__data.whatsapp_url='https://wa.me/972501234567?text='+encodeURIComponent(__data.text);
                return Response.json({shop_id:__data.shop_id,whatsapp_url:__data.whatsapp_url,order_share_url:location.origin+'/__audit-share',reward:{label:'Gift',discount_type:'gift',segment_index:0}});}
            return __realFetch(input,options);
        };
        Object.defineProperty(navigator,'share',{configurable:true,value:()=>{throw new Error('Must not open native sharing')}});
        window.OzmanSignupReward={beforeOrder:async()=>true,consumed(){},gift:()=>null,discount:()=>0};
        document.querySelector('[data-add-product]').click();document.querySelector('#openBeautyCart').click();
        for(const [id,value] of Object.entries({beautyCustomerName:'Test',beautyCustomerPhone:'0591234567',beautyCustomerWhatsapp:'0597654321',beautyCustomerAddress:'Street'}))document.getElementById(id).value=value;`);
    const source=await readFile('public/elegance-order-whatsapp.js','utf8');
    assert.ok(source.includes('window.location.assign(whatsappUrl)'));
    const intercepted=source.replace('window.location.assign(whatsappUrl)','window.__navigations.push(whatsappUrl)');
    await evaluate(intercepted);
    await tap('beautyWhatsappOrder');await until('__navigations.length===1');
    const destination=new URL(await evaluate('__navigations[0]'));
    assert.equal(destination.host,'wa.me');assert.equal(destination.pathname,'/972501234567');
    assert.ok(destination.searchParams.get('text').includes('https://ozman.online/storage/products/perfume.jpg'));
    assert.ok(destination.searchParams.get('text').includes('/cosmetics/elegance-perfumes'));
    assert.equal(await evaluate('__lookups'),0,'Fresh orders should navigate without another fetch');
    assert.equal(await evaluate(`!!document.querySelector('#eleganceOrderShareDialog,#eleganceSharePhotos')`),false);
    await tap('beautyWhatsappOrder');await until('__navigations.length===2');
    assert.equal(await evaluate('__orders'),1);
    // Reinitialize the helper as on a page reload: only the signed lookup is persisted.
    await evaluate(intercepted);await tap('beautyWhatsappOrder');await until('__navigations.length===3');
    assert.equal(await evaluate('__lookups'),1);assert.equal(await evaluate('__orders'),1);
    console.log('PASS original button opens shop number with image/store links; repeat and reload retry do not create another order');
    await evaluate(`document.querySelector('#closeBeautyCart').click();__reward=true;
        document.querySelector('[data-add-product]').click();document.querySelector('#openBeautyCart').click()`);
    await tap('beautyWhatsappOrder');
    await until(`document.querySelector('#purchaseWheelDialog')?.open && !document.querySelector('#purchaseWheelSpin').hidden`);
    await evaluate(`document.querySelector('#purchaseWheelSpin').click()`);await delay(3300);
    await tap('purchaseWheelWhatsapp');await until('__navigations.length===4');
    const wheelText=new URL(await evaluate('__navigations.at(-1)')).searchParams.get('text');
    assert.ok(wheelText.includes('Purchase prize: Gift'));assert.ok(wheelText.includes('/storage/products/perfume.jpg'));
    assert.equal(await evaluate('__orders'),2);
    console.log('PASS wheel send opens the same shop conversation with prize and image links');
    await evaluate(`document.querySelector('#purchaseWheelDialog').close();document.querySelector('#openBeautyCart').click();
        __data.whatsapp_url='https://untrusted.invalid/';`);
    await evaluate(intercepted);await tap('beautyWhatsappOrder');
    await until(`document.querySelector('#eleganceCheckoutStatus').textContent.includes('تعذّر')`);
    assert.equal(await evaluate('__navigations.length'),4);
    assert.deepEqual(errors,[]);
    console.log('PASS invalid redirect is rejected; no native share or JavaScript exceptions');
} finally { ws.close(); }
