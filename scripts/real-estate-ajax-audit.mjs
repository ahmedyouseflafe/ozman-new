const targets = await (await fetch('http://127.0.0.1:9222/json')).json();
const target = targets.find(item => item.type === 'page');
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
const evaluate = async expression => {
    const response = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
    if (response.exceptionDetails) throw new Error(response.exceptionDetails.exception?.description || response.exceptionDetails.text);
    return response.result.value;
};
const wait = ms => new Promise(resolve => setTimeout(resolve, ms));

await send('Page.enable');
await send('Runtime.enable');
await send('Page.navigate', { url: 'http://127.0.0.1:8000/real-estate' });
await wait(1800);

const initial = await evaluate(`({
    navigationCount: performance.getEntriesByType('navigation').length,
    resultRegion: Boolean(document.querySelector('#market-results')),
    filterForm: Boolean(document.querySelector('.filters')),
    initialCards: document.querySelectorAll('.property-card').length
})`);

await evaluate(`(() => {
    const input = document.querySelector('[name="q"]');
    input.value = '__ajax_no_match__';
    input.dispatchEvent(new Event('input', { bubbles: true }));
})()`);
await wait(1200);
const searched = await evaluate(`({
    query: new URL(location.href).searchParams.get('q'),
    cards: document.querySelectorAll('.property-card').length,
    busy: document.querySelector('#market-results')?.getAttribute('aria-busy'),
    ajaxResources: performance.getEntriesByType('resource').filter(entry => entry.name.includes('__ajax_no_match__')).length,
    navigationCount: performance.getEntriesByType('navigation').length
})`);

await evaluate(`document.querySelector('.filters .secondary').click()`);
await wait(900);
const reset = await evaluate(`({
    query: new URL(location.href).searchParams.get('q'),
    inputValue: document.querySelector('[name="q"]').value,
    cards: document.querySelectorAll('.property-card').length,
    navigationCount: performance.getEntriesByType('navigation').length
})`);

await evaluate(`(() => {
    const radio = document.querySelector('[name="purpose"][value="sale"]');
    radio.checked = true;
    radio.dispatchEvent(new Event('change', { bubbles: true }));
})()`);
await wait(850);
const purpose = await evaluate(`({
    value: new URL(location.href).searchParams.get('purpose'),
    checked: document.querySelector('[name="purpose"][value="sale"]').checked,
    navigationCount: performance.getEntriesByType('navigation').length
})`);

await evaluate(`(() => {
    const input = document.querySelector('[name="min_price"]');
    input.value = '1000';
    input.dispatchEvent(new Event('change', { bubbles: true }));
})()`);
await wait(850);
const price = await evaluate(`({
    value: new URL(location.href).searchParams.get('min_price'),
    navigationCount: performance.getEntriesByType('navigation').length
})`);

await evaluate(`(() => {
    const input = document.querySelector('[name="parking"]');
    input.checked = true;
    input.dispatchEvent(new Event('change', { bubbles: true }));
})()`);
await wait(850);
const feature = await evaluate(`({
    value: new URL(location.href).searchParams.get('parking'),
    navigationCount: performance.getEntriesByType('navigation').length
})`);

await evaluate(`(() => {
    const select = document.querySelector('#market-sort');
    select.value = 'price_desc';
    select.dispatchEvent(new Event('change', { bubbles: true }));
})()`);
await wait(850);
const sort = await evaluate(`({
    value: new URL(location.href).searchParams.get('sort'),
    preservedPurpose: new URL(location.href).searchParams.get('purpose'),
    preservedPrice: new URL(location.href).searchParams.get('min_price'),
    preservedFeature: new URL(location.href).searchParams.get('parking'),
    navigationCount: performance.getEntriesByType('navigation').length
})`);

const report = { initial, searched, reset, purpose, price, feature, sort };
console.log(JSON.stringify(report));
socket.close();

const failed = !initial.resultRegion || !initial.filterForm
    || searched.query !== '__ajax_no_match__' || searched.cards !== 0 || searched.ajaxResources < 1
    || searched.navigationCount !== 1 || reset.query !== null || reset.inputValue !== ''
    || reset.navigationCount !== 1 || purpose.value !== 'sale' || !purpose.checked
    || purpose.navigationCount !== 1 || price.value !== '1000' || price.navigationCount !== 1
    || feature.value !== '1' || feature.navigationCount !== 1 || sort.value !== 'price_desc'
    || sort.preservedPurpose !== 'sale' || sort.preservedPrice !== '1000'
    || sort.preservedFeature !== '1' || sort.navigationCount !== 1;
process.exit(failed ? 1 : 0);
