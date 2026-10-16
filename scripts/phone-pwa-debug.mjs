const action = process.argv[2] || 'inspect';
const targets = await (await fetch('http://127.0.0.1:9223/json')).json();
const page = action === 'launch' ? 'launch' : 'install';
const target = targets.find(item => item.type === 'page' && item.url === `https://ozman.online/shop-app/elegance-perfumes/${page}`);
if (!target) throw new Error('Open the Elegance installer in Chrome on the connected phone.');
const socket = new WebSocket(target.webSocketDebuggerUrl);
await new Promise((resolve, reject) => { socket.onopen = resolve; socket.onerror = reject; });
const pending = new Map();
let id = 0;
socket.onmessage = event => {
    const message = JSON.parse(event.data);
    const call = pending.get(message.id);
    if (!call) return;
    pending.delete(message.id);
    clearTimeout(call.timeout);
    message.error ? call.reject(message.error) : call.resolve(message.result);
};
const send = (method, params = {}) => new Promise((resolve, reject) => {
    const command = ++id;
    const timeout = setTimeout(() => { pending.delete(command); reject(new Error(`${method} timed out`)); }, 15000);
    pending.set(command, { resolve, reject, timeout });
    socket.send(JSON.stringify({ id: command, method, params }));
});
const evaluate = async expression => {
    const response = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true, userGesture: true });
    if (response.exceptionDetails) throw new Error(JSON.stringify(response.exceptionDetails));
    return response.result.value;
};
try {
    await send('Page.enable');
    await send('Runtime.enable');
    if (action === 'webapks') {
        await send('Page.navigate', { url: 'chrome://webapks/' });
        await new Promise(resolve => setTimeout(resolve, 2000));
        console.log(await evaluate('document.body.innerText'));
        await send('Page.navigate', { url: 'https://ozman.online/shop-app/elegance-perfumes/install' });
        process.exitCode = 0;
    } else {
    if (action === 'reload') {
        await send('Page.addScriptToEvaluateOnNewDocument', { source: `window.__phoneInstallEvent=false;window.addEventListener('beforeinstallprompt',e=>{window.__phoneInstallEvent=true;});` });
        await send('Page.reload', { ignoreCache: true });
        await new Promise(resolve => setTimeout(resolve, 8000));
    }
    if (action === 'click') {
        await evaluate(`document.querySelector('[data-shop-pwa-install]').click()`);
        await new Promise(resolve => setTimeout(resolve, 1500));
    }
    console.log(JSON.stringify({
        state: await evaluate(`(async()=>{let worker;try{worker={controller:navigator.serviceWorker.controller?.scriptURL,registrations:await navigator.serviceWorker.getRegistrations().then(items=>items.map(item=>({scope:item.scope,active:item.active?.scriptURL})))}}catch(error){worker={error:error.message}}return {url:location.href,title:document.title,text:document.body?.innerText.slice(0,1500),ua:navigator.userAgent,secure:isSecureContext,display:['browser','standalone','fullscreen','minimal-ui'].filter(mode=>matchMedia('(display-mode: '+mode+')').matches),promptSeen:window.__phoneInstallEvent??null,status:document.querySelector('[data-shop-pwa-status]')?.textContent,button:document.querySelector('[data-shop-pwa-install]')?.textContent,worker,manifest:document.querySelector('link[rel=manifest]')?.href}})()`),
        frames: await send('Page.getFrameTree'),
        installability: await send('Page.getInstallabilityErrors'),
        manifest: await send('Page.getAppManifest'),
    }, null, 2));
    }
} finally { socket.close(); }
