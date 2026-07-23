import fs from 'node:fs/promises';

const [pageUrl = 'http://127.0.0.1:8000/distributor-stores/1', widthArg = '1440', heightArg = '900', output = 'shop-grid-audit.png'] = process.argv.slice(2);
const width = Number(widthArg);
const height = Number(heightArg);
const targets = await (await fetch('http://127.0.0.1:9222/json')).json();
const target = targets.find((item) => item.type === 'page');
if (!target?.webSocketDebuggerUrl) throw new Error('No Chrome page target found.');

const socket = new WebSocket(target.webSocketDebuggerUrl);
const pending = new Map();
let commandId = 0;
await new Promise((resolve, reject) => {
    socket.addEventListener('open', resolve, { once: true });
    socket.addEventListener('error', reject, { once: true });
});
socket.addEventListener('message', ({ data }) => {
    const message = JSON.parse(data);
    const request = pending.get(message.id);
    if (!request) return;
    pending.delete(message.id);
    message.error ? request.reject(new Error(message.error.message)) : request.resolve(message.result);
});
const send = (method, params = {}) => {
    const id = ++commandId;
    socket.send(JSON.stringify({ id, method, params }));
    return new Promise((resolve, reject) => pending.set(id, { resolve, reject }));
};
const evaluate = async (expression) => {
    const response = await send('Runtime.evaluate', {
        expression,
        awaitPromise: true,
        returnByValue: true,
    });
    if (response.exceptionDetails) {
        throw new Error(response.exceptionDetails.exception?.description || response.exceptionDetails.text);
    }
    return response.result.value;
};

await send('Page.enable');
await send('Runtime.enable');
await send('Emulation.setDeviceMetricsOverride', {
    width,
    height,
    deviceScaleFactor: 1,
    mobile: width <= 480,
});
await send('Page.addScriptToEvaluateOnNewDocument', {
    source: `
        localStorage.setItem('ozman_visitor_registration_done_v2', '1');
        localStorage.setItem('ozman_visitor_type', 'customer');
        localStorage.setItem('ozman_customer_profile', JSON.stringify({ name: 'Layout Audit', phone: '0590000000' }));
    `,
});
await send('Page.navigate', { url: pageUrl });
await new Promise((resolve) => setTimeout(resolve, 2600));
await evaluate(`(() => {
    if (document.querySelector('#watchGridTrack.is-shop-grid .watch-item')) return;
    const source = centersData?.[0] || {};
    const shops = Array.from({ length: 8 }, (_, index) => ({
        title: ['ماركت المركز', 'سوبر ماركت هيثم', 'سوبر ماركت أبناء فتحي', 'كباب ستيشن', 'Simit Sarayi', 'متجر المدينة', 'ماركت النور', 'سوبر ماركت البلد'][index],
        img: centersData?.[index % Math.max(centersData.length, 1)]?.img || source.img || source.logo || '',
        kind: 'shop',
        shop_id: source.id || index + 1,
    }));
    renderDepartmentsForCenter(0, { departments: shops, products_db: {} });
})()`);
await new Promise((resolve) => setTimeout(resolve, 500));
await evaluate(`document.querySelector('.radial-section')?.scrollIntoView({ block: 'start' })`);
await new Promise((resolve) => setTimeout(resolve, 400));

const layout = await evaluate(`(() => {
    const track = document.querySelector('#watchGridTrack');
    const cards = Array.from(document.querySelectorAll('#watchGridTrack.is-shop-grid .watch-item')).map((card) => {
        const image = card.getBoundingClientRect();
        const label = card.querySelector('.dept-title')?.getBoundingClientRect();
        return {
            image: { left: image.left, right: image.right, top: image.top, bottom: image.bottom },
            label: label ? { left: label.left, right: label.right, top: label.top, bottom: label.bottom } : null,
        };
    });
    const bounds = cards.map(({ image, label }) => ({
        left: Math.min(image.left, label?.left ?? image.left),
        right: Math.max(image.right, label?.right ?? image.right),
        top: Math.min(image.top, label?.top ?? image.top),
        bottom: Math.max(image.bottom, label?.bottom ?? image.bottom),
    }));
    const overlaps = [];
    for (let a = 0; a < bounds.length; a += 1) {
        for (let b = a + 1; b < bounds.length; b += 1) {
            const x = Math.min(bounds[a].right, bounds[b].right) - Math.max(bounds[a].left, bounds[b].left);
            const y = Math.min(bounds[a].bottom, bounds[b].bottom) - Math.max(bounds[a].top, bounds[b].top);
            if (x > 1 && y > 1) overlaps.push({ a, b, x: Math.round(x), y: Math.round(y) });
        }
    }
    const rows = [...new Set(cards.map(({ image }) => Math.round(image.top)))];
    const columns = [...new Set(cards.map(({ image }) => Math.round(image.left)))];
    return {
        viewport: { width: innerWidth, height: innerHeight },
        documentWidth: document.documentElement.scrollWidth,
        shopGrid: Boolean(track?.classList.contains('is-shop-grid')),
        cardCount: cards.length,
        rows: rows.length,
        columns: columns.length,
        overlaps,
    };
})()`);
const screenshot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
await fs.writeFile(output, Buffer.from(screenshot.data, 'base64'));
console.log(JSON.stringify(layout));
socket.close();

const failed = !layout.shopGrid
    || !layout.cardCount
    || layout.overlaps.length
    || layout.documentWidth > layout.viewport.width + 1;
process.exit(failed ? 1 : 0);
