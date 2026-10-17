import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

const source = readFileSync(new URL('../../public/shop-signup-wheel.js', import.meta.url), 'utf8');
function page(storage, {shopId = 164, token = 'guest-a', welcomeId = '1', selected = null, available = true, offline = false, elegance = true, statuses = []} = {}) {
    const elements = new Map(), listeners = new Map();
    const element = () => ({dataset: {}, style: {setProperty() {}}, classList: {add() {}},
        addEventListener(name, callback) {this[name] = callback;}, replaceChildren() {}, append() {},
        showModal() {this.open = true;}, close() {this.open = false;}});
    const get = id => {if (!elements.has(id)) elements.set(id, element());return elements.get(id);};
    const window = {OZMAN_SIGNUP_WHEEL_COPY: {error: 'load error', session: 'session expired', server: 'server error'}, OzmanRestaurantCustomer: {token: () => token},
        ...(elegance ? {OZMAN_ELEGANCE_CHECKOUT: {shopId}} : {})};
    const meta = {content: 'old-token'}, requests = [];
    get('shopSignupWheel').dataset = {url: '/reward', csrfUrl: '/csrf-token'};
    const document = {getElementById: get, createElement: element, querySelector: selector => selector.includes('csrf-token') ? meta : null,
        addEventListener: (name, callback) => listeners.set(name, callback), dispatchEvent() {}};
    runInNewContext(source, {window, document, localStorage: {getItem: key => storage.get(key), setItem: (key, value) => storage.set(key, value)},
        Event, AbortController, setTimeout, clearTimeout, fetch: async (url, options) => {
            requests.push({url, options});
            if (offline) throw new Error('offline');
            if (url === '/csrf-token') return {ok: true, json: async () => ({token: 'fresh-token'})};
            const status = statuses.shift() || 200;
            if (status !== 200) return {ok: false, status};
            return {ok: true, json: async () => ({reward: available ? {title: 'Welcome', welcome_id: welcomeId, selected_index: selected,
                segments: [{label: 'Gift', color: '#ffffff', discount_type: 'gift'}, {label: 'Discount', color: '#000000', discount_type: 'percent', discount_value: 10}]} : null})};
        }});
    return {get, window, requests, visit: () => listeners.get('restaurant:registered')()};
}

test('closing an unspun welcome suppresses automatic reopening on return, while manual access remains', async () => {
    const storage = new Map(), first = page(storage);
    await first.visit(); assert.equal(first.get('shopSignupWheel').open, true);
    first.get('signupWheelClose').click();
    await first.visit(); assert.equal(first.get('shopSignupWheel').open, false);
    const returning = page(storage); await returning.visit();
    assert.notEqual(returning.get('shopSignupWheel').open, true);
    assert.equal(returning.get('signupWheelReopen').hidden, false);
    returning.get('signupWheelReopen').click(); assert.equal(returning.get('shopSignupWheel').open, true);
});

test('another shop or visitor has an independent first presentation', async () => {
    const storage = new Map(); await page(storage).visit();
    for (const options of [{shopId: 165}, {token: 'guest-b'}]) {
        const other = page(storage, options); await other.visit(); assert.equal(other.get('shopSignupWheel').open, true);
    }
});

test('an explicit server reset shows a new welcome to the same visitor, once', async () => {
    const storage = new Map(); await page(storage).visit();
    const reset = page(storage, {welcomeId: '2'}); await reset.visit();
    assert.equal(reset.get('shopSignupWheel').open, true);
    const returnAfterReset = page(storage, {welcomeId: '2'}); await returnAfterReset.visit();
    assert.notEqual(returnAfterReset.get('shopSignupWheel').open, true);
});

test('unavailable rewards and failed loads do not consume the first welcome', async () => {
    const storage = new Map();
    await page(storage, {available: false}).visit(); await page(storage, {offline: true}).visit();
    assert.equal(storage.size, 0);
    const recovered = page(storage); await recovered.visit(); assert.equal(recovered.get('shopSignupWheel').open, true);
});

test('spun rewards are restored without automatic opening or a new spin', async () => {
    const app = page(new Map(), {selected: 0}); await app.visit();
    assert.notEqual(app.get('shopSignupWheel').open, true);
    assert.equal(app.get('signupWheelSpin').hidden, true);
    assert.equal(app.window.OzmanSignupReward.gift().label, 'Gift');
});

test('checkout still prompts for an outstanding spin and restaurant flow is unchanged', async () => {
    const storage = new Map(); await page(storage).visit();
    const returning = page(storage); await returning.visit();
    assert.equal(await returning.window.OzmanSignupReward.beforeOrder(), false);
    assert.equal(returning.get('shopSignupWheel').open, true);
    const restaurant = page(storage, {elegance: false}); await restaurant.visit();
    assert.equal(restaurant.get('shopSignupWheel').open, true);
});

test('expired session refreshes CSRF and retries exactly once using the same visitor', async () => {
    const app = page(new Map(), {statuses: [419, 200]}); await app.visit();
    assert.equal(app.get('shopSignupWheel').open, true);
    assert.deepEqual(app.requests.map(r => r.url), ['/reward', '/csrf-token', '/reward']);
    assert.equal(app.requests[2].options.headers['X-CSRF-TOKEN'], 'fresh-token');
    assert.equal(app.requests[0].options.body, app.requests[2].options.body);
});

test('persistent session rejection and server failures show accurate errors without looping', async () => {
    for (const [statuses, message, calls] of [[[419, 419], 'session expired', 3], [[500], 'server error', 1]]) {
        const storage = new Map(), app = page(storage, {statuses}); await app.visit();
        assert.equal(app.get('signupWheelError').textContent, message);
        assert.equal(app.requests.length, calls);
        assert.equal(storage.size, 0);
    }
});
