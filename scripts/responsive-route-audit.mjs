const baseUrl = process.argv[2] || 'http://127.0.0.1:8000';
const debugPort = process.argv[3] || '9222';
const email = process.argv[4] || 'admin@ozman.local';
const password = process.argv[5] || 'password';

const requestedRoute = process.argv[6] || '';
const allRoutes = [
    '/', '/customer-login', '/merchant-login', '/merchant-register', '/display',
    '/electronics/top-phone', '/electronics/top-phone/devices/ayfon-17',
    '/dashboard', '/dashboard/main', '/shops', '/shops/create', '/products', '/products/create',
    '/categories', '/categories/create', '/ads', '/ads/create', '/screens', '/screens/create',
    '/agents', '/agents/create', '/distributors', '/distributors/create', '/distributor-marketers',
    '/employees', '/employees/create', '/users', '/visitor-registrations', '/front-orders',
    '/raffle-cards', '/raffle-cards/inspector', '/reward-wheels/customer-signup',
    '/reward-wheels/purchase', '/settings',
];
const routes = requestedRoute ? [requestedRoute] : allRoutes;
const viewports = [
    { name: 'mobile-360', width: 360, height: 800, mobile: true },
    { name: 'mobile-412', width: 412, height: 915, mobile: true },
    { name: 'tablet-768', width: 768, height: 1024, mobile: true },
    { name: 'desktop-1366', width: 1366, height: 768, mobile: false },
];

const targets = await fetch(`http://localhost:${debugPort}/json`);
const target = (await targets.json()).find(item => item.type === 'page');
if (!target?.webSocketDebuggerUrl) throw new Error('No Chrome page target found.');
const socket = new WebSocket(target.webSocketDebuggerUrl.replace('127.0.0.1', 'localhost'));
const pending = new Map(); let id = 0;
await new Promise((resolve, reject) => { socket.onopen = resolve; socket.onerror = reject; });
socket.onmessage = event => { const message = JSON.parse(event.data), request = pending.get(message.id); if (!request) return; pending.delete(message.id); message.error ? request.reject(message.error) : request.resolve(message.result); };
const send = (method, params = {}) => new Promise((resolve, reject) => { const commandId = ++id; pending.set(commandId, { resolve, reject }); socket.send(JSON.stringify({ id: commandId, method, params })); });
const evaluate = async expression => (await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true })).result.value;
const wait = ms => new Promise(resolve => setTimeout(resolve, ms));
async function navigate(url) { await send('Page.navigate', { url }); await wait(320); }

await send('Page.enable'); await send('Runtime.enable');
await navigate(`${baseUrl}/login`);
await evaluate(`(() => { const email=document.querySelector('[name=email]'),password=document.querySelector('[name=password]'); if(!email||!password)return false; email.value=${JSON.stringify(email)}; password.value=${JSON.stringify(password)}; email.form.requestSubmit(); return true; })()`);
await wait(700);
const loggedIn = !String(await evaluate('location.pathname')).includes('/login');
if (!loggedIn) throw new Error(`Admin login failed for ${email}.`);

const results = [];
for (const viewport of viewports) {
    await send('Emulation.setDeviceMetricsOverride', { ...viewport, deviceScaleFactor: 1 });
    for (const route of routes) {
        await navigate(baseUrl + route);
        const result = await evaluate(`(() => {
            const vw=innerWidth, root=document.documentElement, body=document.body;
            const visible = element => { const style=getComputedStyle(element),rect=element.getBoundingClientRect(); return style.display!=='none'&&style.visibility!=='hidden'&&rect.width>0&&rect.height>0; };
            const allowed = element => element.closest('.table-wrap,.table-responsive,.orders-wrap,[class*="overflow"],.cats,.thumbs,.pagination,.admin-sidebar-nav,.sidebar');
            const overflow = [...document.querySelectorAll('body *')].filter(visible).map(element=>{const rect=element.getBoundingClientRect();return {element,rect}}).filter(({element,rect})=>!allowed(element)&&(rect.left < -3 || rect.right > vw + 3)).sort((a,b)=>Math.max(Math.abs(b.rect.left),b.rect.right-vw)-Math.max(Math.abs(a.rect.left),a.rect.right-vw)).slice(0,8).map(({element,rect})=>({tag:element.tagName.toLowerCase(),id:element.id,className:typeof element.className==='string'?element.className.slice(0,100):'',left:Math.round(rect.left),right:Math.round(rect.right),width:Math.round(rect.width)}));
            const tinyControls=[...document.querySelectorAll('a,button,input,select,textarea')].filter(visible).map(element=>({element,rect:element.getBoundingClientRect()})).filter(({rect})=>rect.width<32||rect.height<32).slice(0,8).map(({element,rect})=>({tag:element.tagName.toLowerCase(),className:typeof element.className==='string'?element.className.slice(0,80):'',width:Math.round(rect.width),height:Math.round(rect.height)}));
            const scrollContainers=[...document.querySelectorAll('body *')].filter(visible).map(element=>({element,clientWidth:element.clientWidth,scrollWidth:element.scrollWidth,rect:element.getBoundingClientRect()})).filter(item=>item.scrollWidth>item.clientWidth+3).sort((a,b)=>(b.scrollWidth-b.clientWidth)-(a.scrollWidth-a.clientWidth)).slice(0,8).map(({element,clientWidth,scrollWidth,rect})=>({tag:element.tagName.toLowerCase(),className:typeof element.className==='string'?element.className.slice(0,100):'',clientWidth,scrollWidth,left:Math.round(rect.left),right:Math.round(rect.right)}));
            return {path:location.pathname,statusTitle:document.title,documentWidth:Math.max(root.scrollWidth,body.scrollWidth),viewportWidth:vw,overflow,scrollContainers,tinyControls,hasSidebar:!!document.querySelector('#admin-dashboard-sidebar'),hasMobileNav:!!document.querySelector('.admin-mobile-nav')};
        })()`);
        results.push({ viewport: viewport.name, requested: route, ...result });
    }
}
const failures = results.filter(item => item.documentWidth > item.viewportWidth + 2 || (item.hasSidebar && item.viewport.startsWith('mobile') && !item.hasMobileNav));
console.log(JSON.stringify({ audited: results.length, failureCount: failures.length, failures }, null, 2));
socket.close();
if (failures.length) process.exitCode = 1;
