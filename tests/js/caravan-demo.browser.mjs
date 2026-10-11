// Run against a local real-estate page with Chrome's remote debugging enabled.
// CARAVAN_TEST_URL=http://127.0.0.1:8000/real-estate/example
// CDP_URL=http://127.0.0.1:9228 node tests/js/caravan-demo.browser.mjs
// AR sensor frames are simulated. This does not certify tracking on a physical phone.
import assert from 'node:assert/strict';
const cdp = process.env.CDP_URL || 'http://127.0.0.1:9228';
const url = process.env.CARAVAN_TEST_URL;
if (!url) throw new Error('Set CARAVAN_TEST_URL to your local real-estate company page.');
const target = (await (await fetch(`${cdp}/json`)).json()).find(page => page.type === 'page');
const ws = new WebSocket(target.webSocketDebuggerUrl);
await new Promise(resolve => { ws.onopen = resolve; });
let sequence = 0; const pending = new Map(); const exceptions = [];
const send = (method, params = {}) => new Promise((resolve, reject) => {
    const id = ++sequence; pending.set(id, { resolve, reject }); ws.send(JSON.stringify({ id, method, params }));
});
const evaluate = async expression => {
    const response = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
    if (response.exceptionDetails) throw new Error(response.exceptionDetails.exception?.description || response.exceptionDetails.text);
    return response.result.value;
};
const wait = ms => new Promise(resolve => setTimeout(resolve, ms));
async function until(expression) {
    for (let i = 0; i < 100; i++) { if (await evaluate(expression)) return; await wait(100); }
    throw new Error(`Timed out: ${expression}`);
}
const mockEngine = `
window.__ar = { modules: [], stops: 0, hits: [], scene: null };
window.XR8 = {
 XrController: { configure: config => __ar.config = config, pipelineModule: () => ({name:'tracking'}), updateCameraProjectionMatrix:()=>{}, hitTest:()=>__ar.hits },
 GlTextureRenderer: { pipelineModule:()=>({name:'video'}) },
 Threejs: { pipelineModule:()=>({name:'three'}), xrScene:()=>__ar.scene },
 XrConfig: { device:()=>({MOBILE:'mobile'}), camera:()=>({BACK:'back'}) },
 addCameraPipelineModules: modules => __ar.modules=modules,
 clearCameraPipelineModules:()=>{__ar.modules=[];},
 stop:()=>{__ar.stops++;},
 run:async()=>{
   if(window.__cameraFailure) { __ar.modules.at(-1).onCameraStatusChange({status:'failed'});return; }
   const camera=new THREE.PerspectiveCamera(60,1,.1,100);camera.position.set(0,1.6,0);camera.lookAt(0,0,-4);camera.updateMatrixWorld(true);
   __ar.scene={scene:new THREE.Scene(),camera,renderer:{}};
   __ar.modules.at(-1).onStart();
 }
};
window.dispatchEvent(new Event('xrloaded'));
`;
ws.onmessage = async ({ data }) => {
    const message = JSON.parse(data), promise = pending.get(message.id);
    if (promise) { pending.delete(message.id); message.error ? promise.reject(message.error) : promise.resolve(message.result); }
    if (message.method === 'Runtime.exceptionThrown') exceptions.push(message.params.exceptionDetails.text);
    if (message.method === 'Fetch.requestPaused') {
        await send('Fetch.fulfillRequest', { requestId: message.params.requestId, responseCode: 200,
            responseHeaders: [{ name: 'Content-Type', value: 'application/javascript' }, { name: 'Access-Control-Allow-Origin', value: '*' }],
            body: Buffer.from(mockEngine).toString('base64') });
    }
};
const checks = [];
const check = async (name, expression) => { assert.equal(await evaluate(expression), true, name); checks.push(name); };
const click = selector => evaluate(`document.querySelector(${JSON.stringify(selector)}).click()`);
const frame = normal => evaluate(`__ar.modules.at(-1).onUpdate({processCpuResult:{reality:{trackingStatus:'${normal ? 'NORMAL' : 'LIMITED'}'}}})`);
try {
    await send('Page.enable'); await send('Runtime.enable');
    await send('Page.addScriptToEvaluateOnNewDocument', { source: `Object.defineProperty(navigator,'languages',{get:()=>[]});` });
    await send('Page.navigate', { url });
    await until(`!!document.querySelector('[data-caravan-open]')`);
    await click('[data-caravan-open]');
    await until(`document.querySelector('dialog.caravan-dialog').dataset.mode==='preview'`);
    await check('default bathroom, optional kitchen', `document.querySelector('[data-part=bathroom]').getAttribute('aria-pressed')==='true' && document.querySelector('[data-part=kitchen]').getAttribute('aria-pressed')==='false'`);
    const before = await evaluate(`document.querySelector('.caravan-canvas-host canvas').toDataURL()`);
    await click('[data-part=kitchen]');
    assert.notEqual(await evaluate(`document.querySelector('.caravan-canvas-host canvas').toDataURL()`), before, 'kitchen changes the actual rendered model');
    checks.push('kitchen changes rendered model');
    for (const [width, height] of [[320,700],[390,844],[768,1024],[1024,768],[1366,900],[844,390]]) {
        await send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: width < 800 }); await wait(150);
        await check(`dialog fits ${width} × ${height}`, `(()=>{const d=document.querySelector('.caravan-dialog'),r=d.getBoundingClientRect(),s=document.querySelector('.caravan-stage').getBoundingClientRect();return r.left>=-1&&r.right<=innerWidth+1&&r.bottom<=innerHeight+1&&s.height>=175&&d.scrollWidth<=d.clientWidth+1;})()`);
        await check(`camera and options accessible ${width}`, `(()=>{let b=document.querySelector('[data-caravan-camera]');b.scrollIntoView({block:'nearest'});let r=b.getBoundingClientRect();return r.top>=0&&r.bottom<=innerHeight+1&&r.width>100;})()`);
    }
    await send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
    // Verify modelling units and sockets independently from DOM text.
    await check('parts stay joined when root moves/rotates', `(async()=>{
        const T=await import('https://cdn.jsdelivr.net/npm/three@0.160.1/build/three.module.js');
        const {buildCaravan,disposeTree}=await import('/front-assets/caravan-model.js');
        const m=buildCaravan(T);m.root.position.set(8,0,-6);m.root.rotation.y=Math.PI/2;m.root.updateMatrixWorld(true);
        const before=m.parts.bathroom.getWorldPosition(new T.Vector3());m.setOptions({bathroom:false,kitchen:true});m.setOptions({bathroom:true,kitchen:true});m.root.updateMatrixWorld(true);
        const after=m.parts.bathroom.getWorldPosition(new T.Vector3());const ok=before.distanceTo(after)<.00001&&m.parts.kitchen.parent===m.root&&m.dimensions.length===6&&m.dimensions.width===3;
        disposeTree(m.root);return ok;
    })()`);
    // Camera failure / placement / cleanup are exercised with controlled sensor frames.
    await send('Network.setUserAgentOverride', { userAgent: 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/130.0.0.0 Mobile Safari/537.36' });
    await send('Fetch.enable', { patterns: [{ urlPattern: '*@8thwall/engine-binary@1.0.0/dist/xr.js*' }] });
    await evaluate(`window.__cameraFailure=true`); await click('[data-caravan-camera]');
    await until(`window.__ar?.stops>0 && document.querySelector('.caravan-dialog').dataset.mode==='preview'`);
    checks.push('camera failure returns to functional 3D preview');
    await evaluate(`window.__cameraFailure=false`); await click('[data-caravan-camera]');
    await until(`document.querySelector('.caravan-dialog').dataset.mode==='ar'`);
    await frame(false);
    await check('cannot place before tracking is ready', `document.querySelector('[data-caravan-place]').disabled`);
    await evaluate(`__ar.hits=[{type:'DETECTED_SURFACE',position:{x:2,y:0,z:-8},rotation:{x:0,y:0,z:0,w:1},distance:8}]`);
    await frame(true); await click('[data-caravan-place]');
    await evaluate(`window.__placedRoot=__ar.scene.scene.getObjectByName('ozman-caravan-demo');true`);
    await check('placed at measured ground point in metres', `__placedRoot.visible&&__placedRoot.position.x===2&&__placedRoot.position.z===-8&&__ar.config.scale==='absolute'`);
    await click('[data-part=bathroom]'); await click('[data-part=kitchen]'); await click('[data-rotate="1"]');
    await check('live controls change placed model without moving it', `!__placedRoot.getObjectByName('bathroom').visible&&!__placedRoot.getObjectByName('kitchen').visible&&__placedRoot.position.x===2&&__placedRoot.position.z===-8&&__placedRoot.rotation.y>0`);
    await frame(false); await check('tracking loss disables repositioning', `document.querySelector('[data-caravan-place]').disabled`);
    await frame(true); await click('[data-caravan-place]');
    await check('reposition requires a fresh hit', `!__placedRoot.visible&&document.querySelector('[data-caravan-place]').disabled`);
    await evaluate(`__ar.hits=[]`); await frame(true);
    await check('estimated ground supported without native surface hits', `!document.querySelector('[data-caravan-place]').disabled`);
    await evaluate(`__ar.scene.camera.lookAt(0,10,-4);__ar.scene.camera.updateMatrixWorld(true)`); await frame(true);
    await check('cannot place in sky', `document.querySelector('[data-caravan-place]').disabled`);
    const stops = await evaluate('__ar.stops');
    await click('[data-caravan-close]');
    assert.ok(await evaluate('__ar.stops') > stops, 'closing stops the camera'); checks.push('closing stops the camera');
    await check('closing removes canvas and unlocks page', `!document.querySelector('.caravan-dialog').open&&!document.querySelector('.caravan-canvas-host canvas')&&document.body.style.overflow!=='hidden'`);
    await click('[data-caravan-open]'); await until(`document.querySelector('.caravan-dialog').dataset.mode==='preview'`);
    await check('reopening preserves configuration', `document.querySelector('[data-part=bathroom]').getAttribute('aria-pressed')==='false'&&document.querySelector('[data-part=kitchen]').getAttribute('aria-pressed')==='false'`);
    await click('[data-caravan-close]');
    assert.deepEqual(exceptions, [], 'no uncaught browser exceptions');
    console.log(JSON.stringify({ passed: checks.length, checks }, null, 2));
} finally {
    await send('Fetch.disable'); await send('Network.setUserAgentOverride', { userAgent: '' }); ws.close();
}
