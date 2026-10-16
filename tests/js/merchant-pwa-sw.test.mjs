import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

const source = readFileSync(new URL('../../public/merchant-pwa-sw.js', import.meta.url), 'utf8');
function worker(fetch) {
    const listeners = new Map();
    runInNewContext(source, {
        self: { location: { origin: 'https://ozman.test' }, addEventListener: (name, callback) => listeners.set(name, callback) },
        URL, Response, fetch,
    });
    return request => {
        let response;
        listeners.get('fetch')({ request, respondWith: value => { response = value; } });
        return response;
    };
}

test('navigation returns the live response, preserving redirects and authentication failures', async () => {
    for (const status of [200, 302, 401, 500]) {
        const expected = new Response('server response', { status });
        const request = { url: 'https://ozman.test/dashboard', method: 'GET', mode: 'navigate' };
        const run = worker(async actual => { assert.equal(actual, request); return expected; });
        assert.equal(await run(request), expected);
    }
});

test('network failure renders a retry page without caching private content', async () => {
    const run = worker(async () => { throw new TypeError('offline'); });
    const response = await run({ url: 'https://ozman.test/shop-app/elegance/launch', method: 'GET', mode: 'navigate' });
    assert.equal(response.status, 503);
    assert.equal(response.headers.get('Cache-Control'), 'no-store');
    assert.match(await response.text(), /إعادة المحاولة/);
});

test('styles, scripts, API requests, form submissions and other origins are not intercepted', () => {
    const run = worker(() => { throw new Error('must not fetch'); });
    for (const request of [
        { url: 'https://ozman.test/front-assets/shop-stories.css', method: 'GET', mode: 'no-cors' },
        { url: 'https://ozman.test/shop-pwa.js', method: 'GET', mode: 'no-cors' },
        { url: 'https://ozman.test/orders', method: 'GET', mode: 'cors' },
        { url: 'https://ozman.test/orders', method: 'POST', mode: 'navigate' },
        { url: 'https://other.test/', method: 'GET', mode: 'navigate' },
    ]) assert.equal(run(request), undefined);
});
