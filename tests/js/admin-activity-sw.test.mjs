import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import {readFileSync} from 'node:fs';
const source = readFileSync('public/admin-activity-sw.js', 'utf8');
function worker(windows = []) {
    const handlers = {}, notifications = [], opened = [];
    const self = {location: {origin: 'https://ozman.test'}, addEventListener: (name, callback) => handlers[name] = callback,
        skipWaiting: async () => {}, registration: {showNotification: async (...args) => notifications.push(args)},
        clients: {claim: async () => {}, matchAll: async () => windows, openWindow: async url => opened.push(url)}};
    vm.runInNewContext(source, {self, URL, Date, Response, fetch: async () => {throw new Error('offline');}});
    const dispatch = async (name, properties = {}) => {
        let task;handlers[name]({...properties, waitUntil: value => task = value, respondWith: value => task = value});return await task;
    };
    return {dispatch, notifications, opened};
}
test('background push displays every event with a unique tag and requests system sound', async () => {
    const w = worker();
    for (const id of [1,2]) await w.dispatch('push', {data: {json: () => ({title:'Shop', body:'Product updated', tag:'event-'+id, url:'/admin/analytics?order=7'})}});
    assert.equal(w.notifications.length, 2);
    assert.notEqual(w.notifications[0][1].tag, w.notifications[1][1].tag);
    assert.equal(w.notifications[0][1].silent, false);
    assert.equal(w.notifications[0][1].data.url, 'https://ozman.test/admin/analytics?order=7');
});
test('malformed push still displays a notification and links cannot leave admin analytics', async () => {
    const w = worker();
    await w.dispatch('push', {data: {json: () => {throw new Error('bad json');}}});
    assert.ok(w.notifications[0][0]);
    for (const url of ['https://evil.test/admin/analytics', '/cosmetics/shop', 'javascript:alert(1)']) {
        await w.dispatch('notificationclick', {notification: {close(){}, data:{url}}});
    }
    assert.deepEqual(w.opened, Array(3).fill('https://ozman.test/admin/analytics'));
});
test('notification click focuses admin window, never navigates a shop app', async () => {
    const actions = [];
    const w = worker([{url:'https://ozman.test/cosmetics/shop', navigate(){throw new Error('shop must stay untouched');}},
        {url:'https://ozman.test/admin/analytics?shop_id=4', navigate:async url => actions.push(url), focus:async () => actions.push('focus')}]);
    await w.dispatch('notificationclick', {notification:{close(){}, data:{url:'/admin/analytics?order=9'}}});
    assert.deepEqual(actions, ['https://ozman.test/admin/analytics?order=9', 'focus']);
    assert.equal(w.opened.length,0);
});
test('offline navigation has no cached administrative or customer data', async () => {
    const w = worker();const response = await w.dispatch('fetch', {request:{mode:'navigate'}});
    assert.equal(response.status, 503);assert.equal(response.headers.get('cache-control'),'no-store');
    assert.ok((await response.text()).includes('غير متصل'));
});
