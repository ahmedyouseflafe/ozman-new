import assert from 'node:assert/strict';
import { writeFile } from 'node:fs/promises';

const baseUrl = process.argv[2] || 'http://127.0.0.1:8017';
const debugPort = process.argv[3] || '9222';
const slug = process.argv[4] || 'elegance-perfumes';
const targets = await (await fetch(`http://127.0.0.1:${debugPort}/json`)).json();
const target = targets.find(item => item.type === 'page');
const socket = new WebSocket(target.webSocketDebuggerUrl);
await new Promise((resolve, reject) => { socket.onopen = resolve; socket.onerror = reject; });
let sequence = 0;
const pending = new Map();
const exceptions = [];
socket.onmessage = event => {
    const message = JSON.parse(event.data);
    if (message.method === 'Runtime.exceptionThrown') exceptions.push(message.params.exceptionDetails.text);
    const call = pending.get(message.id);
    if (!call) return;
    pending.delete(message.id);
    message.error ? call.reject(message.error) : call.resolve(message.result);
};
const send = (method, params = {}) => new Promise((resolve, reject) => {
    const id = ++sequence; pending.set(id, { resolve, reject }); socket.send(JSON.stringify({ id, method, params }));
});
const evaluate = async expression => {
    const response = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
    if (response.exceptionDetails) throw new Error(JSON.stringify(response.exceptionDetails));
    return response.result.value;
};
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
async function navigate(path) {
    await send('Page.navigate', { url: baseUrl + path });
    for (let attempt = 0; attempt < 100; attempt++) {
        await delay(100);
        if (await evaluate(`location.pathname === ${JSON.stringify(path)} && document.readyState === 'complete' && !!window.OzmanShopPwa`)) return;
    }
    throw new Error(`Navigation did not finish: ${path}, current: ${await evaluate('location.href')}`);
}
try {
    await send('Page.enable'); await send('Runtime.enable'); await send('Network.enable');
    await send('Page.addScriptToEvaluateOnNewDocument', { source: `window.__pwaInstallPromptSeen=false; window.addEventListener('beforeinstallprompt',()=>{window.__pwaInstallPromptSeen=true;});` });
    await send('Emulation.setDeviceMetricsOverride', { width: 412, height: 915, deviceScaleFactor: 1, mobile: true });
    await send('Emulation.setUserAgentOverride', { userAgent: 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', acceptLanguage: 'ar' });
    await send('Network.setBlockedURLs', { urls: ['*fonts.googleapis.com*', '*fonts.gstatic.com*', '*cdn.jsdelivr.net*'] });
    await navigate(`/cosmetics/${slug}`);
    // This store requires customer registration; installation must also be
    // reachable from that initial dialog without registering a test customer.
    const installerLink = await evaluate(`document.querySelector('#bankaiWelcome [data-shop-app-installer]')?.getAttribute('href')`);
    assert.ok(installerLink?.endsWith(`/shop-app/${slug}/install`));
    await evaluate(`document.querySelector('#bankaiWelcome')?.close()`);
    const storefront = await evaluate(`({title:document.title, buttons:document.querySelectorAll('[data-shop-pwa-install]').length, width:document.documentElement.scrollWidth, viewport:innerWidth})`);
    assert.equal(storefront.buttons, 1);
    assert.ok(storefront.width <= storefront.viewport + 1, JSON.stringify(storefront));
    const shot = await send('Page.captureScreenshot', { format: 'png' });
    await writeFile('storage/app/shop-pwa-mobile-audit.png', Buffer.from(shot.data, 'base64'));

    await navigate(`/shop-app/${slug}/install`);
    const manifestResult = await send('Page.getAppManifest');
    assert.deepEqual(manifestResult.errors, []);
    const manifest = JSON.parse(manifestResult.data);
    assert.equal(manifest.scope, `/shop-app/${slug}/`);
    assert.ok(manifest.start_url.startsWith(manifest.scope));
    const icons = await evaluate(`Promise.all(${JSON.stringify(manifest.icons)}.map(async icon => {const response=await fetch(icon.src);const bitmap=await createImageBitmap(await response.blob());return {status:response.status,width:bitmap.width,height:bitmap.height}}))`);
    assert.deepEqual(icons.map(icon => [icon.status, icon.width, icon.height]), [[200, 192, 192], [200, 512, 512]]);
    const installability = await send('Page.getInstallabilityErrors');
    assert.deepEqual(installability.installabilityErrors, []);
    // DevTools manifest checks alone do not prove that the browser offers the
    // install prompt (installed apps and browser heuristics can suppress it).
    for (let attempt = 0; attempt < 30 && !await evaluate('window.__pwaInstallPromptSeen'); attempt++) await delay(100);
    const installPromptSeen = await evaluate('window.__pwaInstallPromptSeen');
    await navigate(`/shop-app/${slug}/launch`);
    assert.equal(await evaluate('!!document.querySelector(".cosmetics-shell")'), true);

    const standalone = await send('Page.addScriptToEvaluateOnNewDocument', { source: `const nativeMatchMedia=window.matchMedia.bind(window); window.matchMedia=query=>query==='(display-mode: standalone)'?{matches:true,media:query}:nativeMatchMedia(query);` });
    await navigate(`/cosmetics/${slug}`);
    const inOzman = await evaluate(`({hidden:document.querySelector('[data-shop-pwa-install]').hidden,label:document.querySelector('[data-shop-pwa-install]').textContent,status:document.querySelector('[data-shop-pwa-status]').textContent})`);
    assert.equal(inOzman.hidden, false);
    assert.ok(inOzman.label.includes('Chrome'));
    assert.ok(!inOzman.status.includes('تم تثبيت'));
    await send('Page.removeScriptToEvaluateOnNewDocument', { identifier: standalone.identifier });
    assert.deepEqual(exceptions, []);
    console.log(JSON.stringify({ storefront, scope: manifest.scope, icons, installability, installPromptSeen, inOzman, exceptions }, null, 2));
} finally {
    socket.close();
}
