// Run against the local car marketplace with Chrome's remote debugging enabled.
// CAR_EXPLORER_TEST_URL=http://127.0.0.1:8031/cars
// CDP_URL=http://127.0.0.1:9228 node tests/js/car-explorer.browser.mjs
// AR sensor frames are simulated. This does not certify tracking on a physical phone.
import assert from 'node:assert/strict';
const cdp = process.env.CDP_URL || 'http://127.0.0.1:9228';
const url = process.env.CAR_EXPLORER_TEST_URL;
if (!url) throw new Error('Set CAR_EXPLORER_TEST_URL to your local /cars page.');
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
    await send('Page.navigate', {url});
    await until(`document.readyState==='complete' && !!document.querySelector('[data-car-explorer]')`);
    await click('[data-caravan-open]');
    await until(`document.querySelector('.car-explorer-dialog').dataset.mode==='preview'`);
    await check('model starts closed', `[...document.querySelectorAll('[data-part]')].every(b=>b.getAttribute('aria-pressed')==='false')`);
    const before=await evaluate(`document.querySelector('.car-explorer-canvas canvas').toDataURL()`);
    await click('[data-part=driverDoor]'); await wait(1000);
    assert.notEqual(await evaluate(`document.querySelector('.car-explorer-canvas canvas').toDataURL()`),before);
    checks.push('opening a door changes the rendered geometry');
    await check('all hinges open outward and return to their exact closed positions', `(async()=>{
        const T=await import('https://cdn.jsdelivr.net/npm/three@0.160.1/build/three.module.js');
        const {buildDemoCar}=await import('/front-assets/car-explorer-model.js');
        const {disposeTree}=await import('/front-assets/caravan-model.js');
        const m=buildDemoCar(T);
        m.setOptions({driverDoor:true,passengerDoor:true,hood:true,trunk:true,glovebox:true});m.update(1);
        m.root.updateMatrixWorld(true);
        const point=(key,xyz)=>m.parts[key].localToWorld(new T.Vector3(...xyz));
        const outward=point('driverDoor',[0,0,-1]).x < -1.5 && point('passengerDoor',[0,0,-1]).x>1.5
            && point('hood',[0,0,1]).y>1.5 && point('trunk',[0,0,-.8]).y>1.5 && point('glovebox',[0,.2,0]).z<.4;
        m.setOptions({});m.update(1);
        const closed=Object.values(m.parts).every(p=>p.rotation[p.userData.axis]===0 && p.parent===m.root);
        disposeTree(m.root);return outward&&closed;
    })()`);
    for(const view of ['inside','dashboard','engine','luggage','outside']){
        await click(`[data-car-view=${view}]`); await wait(180);
        await check(`view ${view} is selectable`, `document.querySelector('[data-car-view=${view}]').getAttribute('aria-pressed')==='true'`);
    }
    await check('inspection views open relevant compartments', `['hood','trunk','glovebox'].every(key=>document.querySelector('[data-part='+key+']').getAttribute('aria-pressed')==='true')`);
    await click('[data-car-reset]'); await wait(1000);
    await check('reset closes parts and restores outside view', `[...document.querySelectorAll('[data-part]')].every(b=>b.getAttribute('aria-pressed')==='false')&&document.querySelector('[data-car-view=outside]').getAttribute('aria-pressed')==='true'`);
    for(const [width,height] of [[320,700],[390,844],[768,1024],[1024,768],[1440,1000],[844,390]]){
        await send('Emulation.setDeviceMetricsOverride',{width,height,deviceScaleFactor:1,mobile:width<800});await wait(150);
        await check(`dialog fits ${width} x ${height}`, `(()=>{const d=document.querySelector('.car-explorer-dialog'),r=d.getBoundingClientRect(),s=document.querySelector('.car-explorer-stage').getBoundingClientRect();return r.left>=-1&&r.right<=innerWidth+1&&r.bottom<=innerHeight+1&&s.height>=175&&d.scrollWidth<=d.clientWidth+1;})()`);
        await check(`camera button reachable ${width}`, `(()=>{const b=document.querySelector('[data-caravan-camera]');b.scrollIntoView({block:'nearest'});const r=b.getBoundingClientRect();return r.top>=0&&r.bottom<=innerHeight+1&&r.width>100})()`);
    }
    // Mobile permission/placement/cleanup simulation: no physical-camera claim.
    await send('Network.setUserAgentOverride',{userAgent:'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/130.0.0.0 Mobile Safari/537.36'});
    await send('Fetch.enable',{patterns:[{urlPattern:'*@8thwall/engine-binary@1.0.0/dist/xr.js*'}]});
    await evaluate('window.__cameraFailure=true');await click('[data-caravan-camera]');
    await until(`window.__ar?.stops>0 && document.querySelector('.car-explorer-dialog').dataset.mode==='preview'`);
    checks.push('camera failure returns to functioning preview');
    await evaluate('window.__cameraFailure=false');await click('[data-caravan-camera]');
    await until(`document.querySelector('.car-explorer-dialog').dataset.mode==='ar'`);
    await frame(false);await check('cannot place without tracking',`document.querySelector('[data-caravan-place]').disabled`);
    await evaluate(`__ar.hits=[{type:'DETECTED_SURFACE',position:{x:2,y:0,z:-8},rotation:{x:0,y:0,z:0,w:1},distance:8}]`);
    await frame(true);await click('[data-caravan-place]');
    await evaluate(`window.__carRoot=__ar.scene.scene.getObjectByName('Ozman demonstration coupe');true`);
    await check('AR places the car at the hit point at 30 percent',`__carRoot.visible&&__carRoot.position.x===2&&__carRoot.position.z===-8&&__carRoot.scale.x===.3`);
    for(const key of ['driverDoor','passengerDoor','hood','trunk','glovebox'])await click(`[data-part=${key}]`);
    await wait(1100);
    await check('parts animate on the placed car without shifting it',`['driverDoor','passengerDoor','hood','trunk','glovebox'].every(key=>{const p=__carRoot.getObjectByName(key);return Math.abs(p.rotation[p.userData.axis]-p.userData.openAngle)<.01})&&__carRoot.position.x===2&&__carRoot.position.z===-8`);
    await click('[data-caravan-full-size]');
    await check('full size retains placement',`__carRoot.scale.x===1&&__carRoot.position.x===2&&__carRoot.position.z===-8`);
    await frame(false);await check('tracking loss disables relocation',`document.querySelector('[data-caravan-place]').disabled`);
    await frame(true);await click('[data-caravan-place]');
    await check('relocation waits for a new hit',`!__carRoot.visible&&document.querySelector('[data-caravan-place]').disabled`);
    const stops=await evaluate('__ar.stops');
    await click('[data-caravan-close]');
    assert.ok(await evaluate('__ar.stops')>stops);
    await check('close releases canvas and scroll lock',`!document.querySelector('.car-explorer-dialog').open&&!document.querySelector('.car-explorer-canvas canvas')&&document.body.style.overflow!=='hidden'`);
    await click('[data-caravan-open]');await until(`document.querySelector('.car-explorer-dialog').dataset.mode==='preview'`);
    await check('reopening preserves open parts',`[...document.querySelectorAll('[data-part]')].every(b=>b.getAttribute('aria-pressed')==='true')`);
    await click('[data-caravan-close]');
    await click('[data-details]');
    await check('listing dialog still works',`document.getElementById('detailsDialog').open`);
    await click('#detailsDialog [data-close]');
    assert.deepEqual(exceptions,[],'no uncaught browser exceptions');
    console.log(JSON.stringify({passed:checks.length,checks},null,2));
} finally {
    await send('Fetch.disable');await send('Network.setUserAgentOverride',{userAgent:''});ws.close();
}
