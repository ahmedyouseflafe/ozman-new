// Local test: mock order/reward APIs and native sharing, but generate real JPEG files.
// Does not send messages or create actual orders.
import assert from 'node:assert/strict';
import { writeFile } from 'node:fs/promises';

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
    await until(`document.readyState==='complete' && !!window.OzmanOrderPhotoShare`);
    await delay(2000);
    await evaluate(`document.querySelector('#shopSignupWheel')?.close();
        [...document.querySelectorAll('button')].find(b=>['Later','لاحقًا','لاحقاً'].includes(b.textContent.trim()))?.click();
        window.__orders=0;window.__shares=[];window.__mode='cancel';window.__reward=false;window.__delay=0;
        window.__data={shop_id:window.OZMAN_ELEGANCE_CHECKOUT.shopId,order_number:'COS-TEST',text:'Order 100 ₪ '+location.origin+'/cosmetics/elegance-perfumes',
            whatsapp_url:'https://wa.me/972501234567?text=test',items:[
                {product_id:1,name:'Perfume A',qty:2,image_url:'/images/virtual-tryon/demo-blue-watch.png'},
                {product_id:2,name:'Perfume B',qty:1,image_url:'/images/virtual-tryon/demo-ivory-navy-tee.png'}]};
        window.__realFetch=window.fetch;
        window.fetch=async(input,options={})=>{
            const path=new URL(String(input),location.href).pathname;
            if(options.method==='POST' && path.endsWith('/orders')){__orders++;await new Promise(r=>setTimeout(r,__delay));return Response.json({order_number:__data.order_number,total:100,
                order_share_url:location.origin+'/__audit-share',order_share:__data,whatsapp_url:__data.whatsapp_url,
                purchase_wheel:__reward?{url:'/__audit-wheel',spin_url:'/__audit-spin'}:null});}
            if(path==='/__audit-share')return Response.json(__data);
            if(path==='/__audit-wheel')return Response.json({shop_id:__data.shop_id,whatsapp_url:__data.whatsapp_url,order_share_url:location.origin+'/__audit-share',order_share:__data,
                wheel:{title:'Wheel',segments:[{label:'Gift',color:'#ff0000'},{label:'Gift 2',color:'#00ff00'}]},reward:null});
            if(path==='/__audit-spin'){__data.text+=' Purchase prize: Gift';return Response.json({shop_id:__data.shop_id,whatsapp_url:__data.whatsapp_url,
                order_share_url:location.origin+'/__audit-share',order_share:__data,reward:{label:'Gift',discount_type:'gift',segment_index:0}});}
            return __realFetch(input,options);
        };
        Object.defineProperty(navigator,'canShare',{configurable:true,value:({files})=>files.length>0});
        Object.defineProperty(navigator,'share',{configurable:true,value:async data=>{
            if(__mode==='blocked')throw new DOMException('Fresh tap required','NotAllowedError');
            const active=navigator.userActivation.isActive;
            const photos=await Promise.all(data.files.map(async file=>({type:file.type,size:file.size,header:Array.from(new Uint8Array(await file.arrayBuffer()).slice(0,3))})));
            __shares.push({text:data.text,photos,active});if(__mode==='cancel')throw new DOMException('Cancelled','AbortError');
        }});
        window.OzmanSignupReward={beforeOrder:async()=>true,consumed(){},gift:()=>null,discount:()=>0};
        document.querySelector('[data-add-product]').click();document.querySelector('#openBeautyCart').click();
        for(const [id,value] of Object.entries({beautyCustomerName:'Test',beautyCustomerPhone:'0591234567',beautyCustomerWhatsapp:'0597654321',beautyCustomerAddress:'Street'}))document.getElementById(id).value=value;`);
    await tap('beautyWhatsappOrder');
    await until(`__shares.length===1 && document.querySelector('#eleganceCheckoutStatus').textContent.includes('أُلغيت')`);
    const share=await evaluate('__shares[0]');
    assert.equal(share.active,true);assert.equal(share.photos.length,2);
    for(const file of share.photos){assert.equal(file.type,'image/jpeg');assert.ok(file.size>1000);assert.deepEqual(file.header,[255,216,255]);}
    assert.ok(share.text.includes('/cosmetics/elegance-perfumes'));
    assert.equal(await evaluate(`!!document.querySelector('#eleganceOrderShareDialog,#eleganceSharePhotos')`),false);
    await evaluate(`__mode='success'`);await tap('beautyWhatsappOrder');
    await until('__shares.length===2');assert.equal(await evaluate('__orders'),1);
    await evaluate(`document.querySelector('#closeBeautyCart').click();document.querySelector('#openBeautyCart').click()`);
    await tap('beautyWhatsappOrder');await until('__shares.length===3');assert.equal(await evaluate('__orders'),1);
    console.log('PASS original send button opens native sharing directly; real JPEGs, shop link, cancel/retry without duplicate order or extra screen');

    await evaluate(`__mode='blocked'`);await tap('beautyWhatsappOrder');
    await until(`document.querySelector('#eleganceCheckoutStatus').textContent.includes('مرة ثانية')`);
    await evaluate(`__mode='success'`);await tap('beautyWhatsappOrder');
    assert.equal(await evaluate('__orders'),1);
    assert.equal(await evaluate('__shares.at(-1).active'),true);
    console.log('PASS activation-blocked sharing retries from the same button');

    await evaluate(`(async()=>{__data.items[1].image_url='/__missing_image';await window.OzmanOrderPhotoShare.prepare(location.origin+'/__audit-share',__data).catch(()=>{});})()`);
    await tap('beautyWhatsappOrder');
    await until(`document.querySelector('#eleganceCheckoutStatus').textContent.includes('تعذّر')`);
    await evaluate(`__data.items[1].image_url='/images/virtual-tryon/demo-ivory-navy-tee.png'`);
    const before=await evaluate('__shares.length');await tap('beautyWhatsappOrder');await until(`__shares.length===${before+1}`);
    await evaluate(`Object.defineProperty(navigator,'canShare',{configurable:true,value:()=>false})`);
    await tap('beautyWhatsappOrder');
    await until(`document.querySelector('#eleganceCheckoutStatus').textContent.includes('لا يدعم')`);
    assert.equal(await evaluate('__orders'),1);
    console.log('PASS image failures retry the saved order; unsupported browsers report the limit inline');

    await evaluate(`Object.defineProperty(navigator,'canShare',{configurable:true,value:()=>true});document.querySelector('#closeBeautyCart').click();
        __reward=true;document.querySelector('[data-add-product]').click();document.querySelector('#openBeautyCart').click()`);
    await tap('beautyWhatsappOrder');
    await until(`document.querySelector('#purchaseWheelDialog')?.open && !document.querySelector('#purchaseWheelSpin').hidden`);
    await evaluate(`document.querySelector('#purchaseWheelSpin').click()`);await delay(3300);
    await until(`!document.querySelector('#purchaseWheelWhatsapp').disabled`);
    await tap('purchaseWheelWhatsapp');
    assert.ok((await evaluate('__shares.at(-1).text')).includes('Purchase prize: Gift'));
    assert.equal(await evaluate('__shares.at(-1).active'),true);
    assert.equal(await evaluate('__orders'),2);
    assert.equal(await evaluate(`!!document.querySelector('#eleganceOrderShareDialog')`),false);
    console.log('PASS wheel send button shares updated prize directly without another website screen');

    await evaluate(`(async()=>{document.querySelector('#purchaseWheelDialog').close();document.querySelector('#openBeautyCart').click();
        __data.items=Array.from({length:11},(_,i)=>({...__data.items[0],product_id:i+1}));await window.OzmanOrderPhotoShare.prepare(location.origin+'/__audit-share',__data);})()`);
    await tap('beautyWhatsappOrder');assert.equal(await evaluate('__shares.at(-1).photos.length'),10);
    await tap('beautyWhatsappOrder');assert.equal(await evaluate('__shares.at(-1).photos.length'),1);
    await evaluate(`(async()=>{__data.items=[{product_id:1,name:'No image',qty:1,image_url:null}];await window.OzmanOrderPhotoShare.prepare(location.origin+'/__audit-share',__data);})()`);
    await tap('beautyWhatsappOrder');
    await until(`document.querySelector('#eleganceCheckoutStatus').textContent.includes('ليس لها صور')`);
    assert.equal(await evaluate('__orders'),2);
    assert.deepEqual(errors,[]);
    console.log('PASS large order batches, missing images, no JavaScript exceptions');
    const shot=await send('Page.captureScreenshot',{format:'png'});
    await writeFile('storage/app/order-direct-share-mobile.png',Buffer.from(shot.data,'base64'));
} finally { ws.close(); }
