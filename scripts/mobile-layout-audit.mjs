import fs from 'node:fs/promises';

const pageUrl = process.argv[2] || 'http://127.0.0.1:8000';
const debugPort = process.argv[3] || '9222';
const viewport = { width: 412, height: 915, deviceScaleFactor: 1, mobile: true };

const targets = await fetch(`http://127.0.0.1:${debugPort}/json`);
const pages = (await targets.json()).filter((target) => target.type === 'page');
const target = pages[0];

if (!target?.webSocketDebuggerUrl) {
    throw new Error('No Chrome page target found.');
}

const socket = new WebSocket(target.webSocketDebuggerUrl);
const pending = new Map();
let commandId = 0;

await new Promise((resolve, reject) => {
    socket.addEventListener('open', resolve, { once: true });
    socket.addEventListener('error', reject, { once: true });
});

socket.addEventListener('message', (event) => {
    const message = JSON.parse(event.data);
    const request = pending.get(message.id);
    if (!request) return;
    pending.delete(message.id);
    if (message.error) request.reject(new Error(message.error.message));
    else request.resolve(message.result);
});

function send(method, params = {}) {
    const id = ++commandId;
    socket.send(JSON.stringify({ id, method, params }));
    return new Promise((resolve, reject) => pending.set(id, { resolve, reject }));
}

async function evaluate(expression) {
    const result = await send('Runtime.evaluate', {
        expression,
        awaitPromise: true,
        returnByValue: true,
    });
    return result.result.value;
}

async function screenshot(name) {
    const result = await send('Page.captureScreenshot', {
        format: 'png',
        captureBeyondViewport: false,
    });
    await fs.writeFile(name, Buffer.from(result.data, 'base64'));
}

await send('Page.enable');
await send('Runtime.enable');
await send('Emulation.setDeviceMetricsOverride', viewport);
await send('Page.addScriptToEvaluateOnNewDocument', {
    source: `
        localStorage.setItem('ozman_visitor_registration_done_v2', '1');
        localStorage.setItem('ozman_visitor_type', 'customer');
        localStorage.setItem('ozman_customer_profile', JSON.stringify({ name: 'Mobile Audit', phone: '0590000000' }));
    `,
});
await send('Page.navigate', { url: pageUrl });
await new Promise((resolve) => setTimeout(resolve, 2500));

const initial = await evaluate(`(() => {
    const selectors = [
        'header',
        '.carousel-3d-section',
        '#carouselProducts',
        '.radial-section',
        '#sideVCarousel',
        '#watchGridWrapper',
        '#purchaseWheelsCarousel',
        '.bottom-nav'
    ];
    const rect = (element) => {
        if (!element) return null;
        const value = element.getBoundingClientRect();
        return {
            left: Math.round(value.left),
            right: Math.round(value.right),
            top: Math.round(value.top),
            bottom: Math.round(value.bottom),
            width: Math.round(value.width),
            height: Math.round(value.height),
            display: getComputedStyle(element).display,
            overflowX: getComputedStyle(element).overflowX,
        };
    };
    return {
        viewport: { width: innerWidth, height: innerHeight },
        documentWidth: document.documentElement.scrollWidth,
        bodyWidth: document.body.scrollWidth,
        overflowElements: Array.from(document.querySelectorAll('body *'))
            .map((element) => {
                const value = element.getBoundingClientRect();
                return {
                    tag: element.tagName.toLowerCase(),
                    id: element.id,
                    className: typeof element.className === 'string' ? element.className : '',
                    left: Math.round(value.left),
                    right: Math.round(value.right),
                    width: Math.round(value.width),
                };
            })
            .filter((item) => item.width > 0 && (item.left < -2 || item.right > innerWidth + 2))
            .sort((a, b) => Math.max(Math.abs(b.left), b.right - innerWidth) - Math.max(Math.abs(a.left), a.right - innerWidth))
            .slice(0, 20),
        elements: Object.fromEntries(selectors.map((selector) => [selector, rect(document.querySelector(selector))])),
    };
})()`);

await screenshot('mobile-audit-home.png');

const testedDepartment = await evaluate(`(() => {
    const department = Object.keys(activeProductsDb).find((key) => activeProductsDb[key]?.length);
    if (department) {
        const source = activeProductsDb[department][0];
        activeProductsDb[department] = Array.from({ length: 12 }, (_, index) => ({
            ...source,
            name: (source.name || 'منتج') + ' اختبار جوال ' + (index + 1),
        }));
        renderProductsScatter(department);
    }
    return department || null;
})()`);
await new Promise((resolve) => setTimeout(resolve, 900));
const renderedProductCount = await evaluate(`document.querySelectorAll('#watchGridTrack .watch-item').length`);

const productLayout = await evaluate(`(() => {
    const wrapper = document.querySelector('#watchGridWrapper')?.getBoundingClientRect();
    const products = Array.from(document.querySelectorAll('#watchGridTrack .watch-item')).map((element) => {
        const rects = [element, ...element.querySelectorAll('*')].map((node) => node.getBoundingClientRect());
        const rect = {
            left: Math.min(...rects.map((value) => value.left)),
            right: Math.max(...rects.map((value) => value.right)),
            top: Math.min(...rects.map((value) => value.top)),
            bottom: Math.max(...rects.map((value) => value.bottom)),
        };
        rect.width = rect.right - rect.left;
        rect.height = rect.bottom - rect.top;
        return {
            left: Math.round(rect.left),
            right: Math.round(rect.right),
            top: Math.round(rect.top),
            bottom: Math.round(rect.bottom),
            width: Math.round(rect.width),
            height: Math.round(rect.height),
        };
    });
    return {
        wrapper: wrapper ? {
            left: Math.round(wrapper.left),
            right: Math.round(wrapper.right),
            top: Math.round(wrapper.top),
            bottom: Math.round(wrapper.bottom),
        } : null,
        products,
    };
})()`);

const testedCategories = await evaluate(`(() => {
    const center = centersData[activeCenterIndex];
    const source = center?.departments?.[0];
    if (!center || !source) return false;
    center.departments = Array.from({ length: 12 }, (_, index) => ({
        ...source,
        title: (source.title || 'فئة') + ' اختبار جوال ' + (index + 1),
    }));
    renderDepartmentsForCenter(activeCenterIndex);
    document.querySelector('#watchGridWrapper').scrollTop = 0;
    return true;
})()`);
await new Promise((resolve) => setTimeout(resolve, 500));
const categoryLayout = await evaluate(`(() => Array.from(document.querySelectorAll('#watchGridTrack .watch-item')).map((element) => {
    const rects = [element, ...element.querySelectorAll('*')].map((node) => node.getBoundingClientRect());
    const left = Math.min(...rects.map((value) => value.left));
    const right = Math.max(...rects.map((value) => value.right));
    const top = Math.min(...rects.map((value) => value.top));
    const bottom = Math.max(...rects.map((value) => value.bottom));
    return { left: Math.round(left), right: Math.round(right), top: Math.round(top), bottom: Math.round(bottom) };
}))()`);
await evaluate(`document.querySelector('.radial-section')?.scrollIntoView({ block: 'start' })`);
await new Promise((resolve) => setTimeout(resolve, 300));
await screenshot('mobile-audit-categories.png');

await evaluate(`(() => {
    if (${JSON.stringify(testedDepartment)}) renderProductsScatter(${JSON.stringify(testedDepartment)});
    document.querySelector('#watchGridWrapper').scrollTop = 0;
})()`);
await new Promise((resolve) => setTimeout(resolve, 500));

const storeScroller = await evaluate(`(async () => {
    const container = document.querySelector('#sideVCarousel');
    if (!container) return null;
    const before = container.scrollTop;
    const clientHeight = container.clientHeight;
    const scrollHeight = container.scrollHeight;
    const maxScrollTop = scrollHeight - clientHeight;
    container.scrollTop = before >= maxScrollTop - 2
        ? Math.max(0, before - 80)
        : Math.min(maxScrollTop, before + 80);
    container.dispatchEvent(new Event('scroll'));
    await new Promise((resolve) => setTimeout(resolve, 100));
    return {
        before: Math.round(before),
        after: Math.round(container.scrollTop),
        clientHeight,
        scrollHeight,
        overflowY: getComputedStyle(container).overflowY,
        touchAction: getComputedStyle(container).touchAction,
    };
})()`);

await evaluate(`(() => {
    const section = document.querySelector('.radial-section');
    section?.scrollIntoView({ block: 'start' });
})()`);
await new Promise((resolve) => setTimeout(resolve, 500));
await screenshot('mobile-audit-radial.png');

const failures = [];
if (initial.documentWidth > initial.viewport.width + 1) {
    failures.push(`horizontal overflow: document=${initial.documentWidth}, viewport=${initial.viewport.width}`);
}

for (const [selector, rect] of Object.entries(initial.elements)) {
    if (!rect || rect.display === 'none') continue;
    if (rect.left < -1 || rect.right > initial.viewport.width + 1) {
        failures.push(`${selector} exceeds viewport: ${JSON.stringify(rect)}`);
    }
}

if (productLayout.wrapper) {
    if (testedDepartment && renderedProductCount === 0) {
        failures.push(`department "${testedDepartment}" rendered no products during interaction audit`);
    }

    for (const [index, product] of productLayout.products.entries()) {
        if (
            product.left < productLayout.wrapper.left - 1
            || product.right > productLayout.wrapper.right + 1
        ) {
            failures.push(`product ${index} exceeds center column: ${JSON.stringify(product)}`);
        }
    }

    for (let first = 0; first < productLayout.products.length; first += 1) {
        for (let second = first + 1; second < productLayout.products.length; second += 1) {
            const a = productLayout.products[first];
            const b = productLayout.products[second];
            const overlapX = Math.min(a.right, b.right) - Math.max(a.left, b.left);
            const overlapY = Math.min(a.bottom, b.bottom) - Math.max(a.top, b.top);
            if (overlapX > 2 && overlapY > 2) {
                failures.push(`products ${first} and ${second} overlap by ${overlapX}x${overlapY}`);
            }
        }
    }
}

if (testedCategories) {
    for (let first = 0; first < categoryLayout.length; first += 1) {
        const category = categoryLayout[first];
        if (category.left < productLayout.wrapper.left - 1 || category.right > productLayout.wrapper.right + 1) {
            failures.push(`category ${first} exceeds center column: ${JSON.stringify(category)}`);
        }
        for (let second = first + 1; second < categoryLayout.length; second += 1) {
            const other = categoryLayout[second];
            const overlapX = Math.min(category.right, other.right) - Math.max(category.left, other.left);
            const overlapY = Math.min(category.bottom, other.bottom) - Math.max(category.top, other.top);
            if (overlapX > 2 && overlapY > 2) failures.push(`categories ${first} and ${second} overlap by ${overlapX}x${overlapY}`);
        }
    }
}

if (storeScroller) {
    if (storeScroller.scrollHeight <= storeScroller.clientHeight) {
        failures.push(`store scroller has no vertical range: ${JSON.stringify(storeScroller)}`);
    }
    if (storeScroller.after === storeScroller.before) {
        failures.push(`store scroller did not move: ${JSON.stringify(storeScroller)}`);
    }
    if (storeScroller.overflowY !== 'auto' && storeScroller.overflowY !== 'scroll') {
        failures.push(`store scroller overflow is disabled: ${JSON.stringify(storeScroller)}`);
    }
}

console.log(JSON.stringify({ ...initial, productLayout, categoryLayout, storeScroller, failures }, null, 2));
socket.close();

if (failures.length) process.exitCode = 1;
