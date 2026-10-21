// Run against a local cosmetics storefront and a Chrome remote-debugging session.
// Does not submit orders, spin wheels, or change shop records.
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
async function checkViewport(id) {
    const result = await evaluate(`(() => {
        const modal = document.getElementById('${id}'), rect = modal.getBoundingClientRect();
        return {modal:modal.matches(':modal'), width:rect.width, right:rect.right, left:rect.left,
            height:rect.height, screen:innerWidth, viewport:visualViewport.height,
            covered:[2, innerWidth / 2, innerWidth - 2].every(x => modal.contains(document.elementFromPoint(x, 100))),
            locked:document.documentElement.classList.contains('beauty-overlay-open')};
    })()`);
    assert.equal(result.modal, true, `${id} must occupy the browser top layer`);
    assert.equal(result.covered, true, `${id} leaves the storefront exposed`);
    assert.equal(result.locked, true);
    assert.ok(Math.abs(result.width - result.screen) < 2 && Math.abs(result.left) < 2, JSON.stringify(result));
    assert.ok(Math.abs(result.height - result.viewport) < 2, JSON.stringify(result));
}
try {
    await send('Page.enable'); await send('Runtime.enable');
    await send('Page.navigate', { url });
    await until(`document.readyState === 'complete' && !!document.querySelector('[data-add-product]')`);
    await delay(2500);
    await evaluate(`document.querySelector('#shopSignupWheel')?.close();
        [...document.querySelectorAll('button')].find(b=>['Later','لاحقًا','لاحقاً'].includes(b.textContent.trim()))?.click();
        const add = document.querySelector('[data-add-product]');
        add.dataset.name = 'GIORGIO ARMANI ACQUA DI GIÒ Eau de Parfum عطر رجالي 100 ml ' + 'LongName'.repeat(20);
        add.click();`);
    for (const [width, height] of [[320,568],[390,844],[430,932],[768,1024],[844,390],[1440,900]]) {
        await metrics(width, height);
        await evaluate(`document.querySelector('.catalog-panel').scrollIntoView({behavior:'instant'});`);
        const scroll = await evaluate('scrollY');
        await evaluate(`document.querySelector('#openBeautyCart').focus();document.querySelector('#openBeautyCart').click();`);
        await checkViewport('beautyCartSheet');
        assert.equal(await evaluate(`(()=>{
            const card=document.querySelector('.cart-card');
            return card.scrollWidth <= card.clientWidth + 1 &&
                [...card.querySelectorAll('.beauty-field input,.beauty-field textarea')].every(e=>parseFloat(getComputedStyle(e).fontSize)>=16);
        })()`), true, 'Cart content must fit and inputs must avoid iOS focus zoom');
        await evaluate(`document.querySelector('#beautyCustomerName').focus();document.querySelector('.cart-card').scrollTop=10000;`);
        assert.equal(await evaluate(`(()=>{const r=document.querySelector('#closeBeautyCart').getBoundingClientRect();return r.top>=0 && r.bottom<=innerHeight})()`), true, 'Checkout actions must be reachable');
        if (width === 390) {
            await metrics(390, 430); await checkViewport('beautyCartSheet');
            await metrics(width, height);
            await evaluate(`document.querySelector('#beautyCustomerName').blur();document.querySelector('.cart-card').scrollTop=0`);
            const shot=await send('Page.captureScreenshot',{format:'png'});
            await writeFile('storage/app/cosmetics-cart-mobile.png',Buffer.from(shot.data,'base64'));
        }
        await evaluate(`document.querySelector('#closeBeautyCart').click()`);
        await delay(50);
        assert.ok(Math.abs(await evaluate('scrollY') - scroll) < 2, 'Cart close must restore the page position');
        assert.equal(await evaluate(`document.documentElement.classList.contains('beauty-overlay-open')`), false);
        await evaluate(`document.querySelector('[data-product-image]').click()`);
        await checkViewport('beautyProductImageModal');
        if (await evaluate(`!document.querySelector('#beautyImageNext').hidden`)) {
            const count = await evaluate(`document.querySelector('#beautyImageCount').textContent`);
            await evaluate(`document.querySelector('#beautyImageNext').click()`);
            assert.notEqual(await evaluate(`document.querySelector('#beautyImageCount').textContent`), count);
            await evaluate(`document.querySelector('#beautyImagePrev').click()`);
            assert.equal(await evaluate(`document.querySelector('#beautyImageCount').textContent`), count);
        }
        // Use a locally available image to measure rendering even with offline product uploads.
        await evaluate(`document.querySelector('#beautyProductImageViewer').src='/images/virtual-tryon/demo-blue-watch.png'`);
        await until(`document.querySelector('#beautyProductImageViewer').naturalWidth>0`);
        assert.equal(await evaluate(`['beautyProductImageViewer','beautyImageCount','beautyProductImageClose','beautyImageNext','beautyImagePrev'].every(id=>{
            const e=document.getElementById(id);if(e.hidden)return true;const r=e.getBoundingClientRect();
            return r.left>=0 && r.right<=innerWidth+1 && r.top>=0 && r.bottom<=innerHeight+1;
        })`), true, 'Image and controls must fit the visible screen');
        if (width === 390) {
            await metrics(844,390); await checkViewport('beautyProductImageModal');
            await metrics(width,height); await checkViewport('beautyProductImageModal');
            const shot=await send('Page.captureScreenshot',{format:'png'});
            await writeFile('storage/app/cosmetics-image-mobile.png',Buffer.from(shot.data,'base64'));
        }
        await send('Input.dispatchKeyEvent', {type:'keyDown',key:'Escape',code:'Escape',windowsVirtualKeyCode:27});
        await send('Input.dispatchKeyEvent', {type:'keyUp',key:'Escape',code:'Escape',windowsVirtualKeyCode:27});
        await delay(50);
        assert.equal(await evaluate(`document.querySelector('#beautyProductImageModal').open`), false);
        assert.ok(Math.abs(await evaluate('scrollY') - scroll) < 2, 'Image close must restore the page position');
        console.log(`PASS ${width}x${height}: cart, long name, fields, image, controls, scroll restoration`);
    }
    await evaluate(`document.querySelector('#openBeautyCart').click();document.querySelector('#beautyCartSheet').close();`);
    await until(`!document.documentElement.classList.contains('beauty-overlay-open')`);
    await evaluate(`document.querySelector('#openBeautyCart').click();document.querySelector('#closeBeautyCartTop').click();`);
    assert.equal(await evaluate(`document.querySelector('#beautyCartSheet').open`), false);
    assert.deepEqual(errors, []);
    console.log('PASS native close cleanup, top close button, no JavaScript exceptions');
} finally { ws.close(); }
