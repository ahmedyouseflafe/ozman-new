import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

const source = readFileSync(new URL('../../public/shop-pwa.js', import.meta.url), 'utf8');

function runtime({ standalone = false, displayMode = 'browser', installer = true, userAgent = 'Android Chrome/130', serviceWorker = true, clipboard = true } = {}) {
    const elements = [];
    const events = new Map();
    const timers = [];
    const copied = [];
    function element(tag) {
        const item = {
            tagName: tag, dataset: {}, hidden: false, textContent: '', children: [], listeners: {},
            setAttribute() {}, append(...children) { this.children.push(...children); },
            appendChild(child) { this.append(child); }, prepend(child) { this.children.unshift(child); },
            addEventListener(name, listener) { this.listeners[name] = listener; },
        };
        elements.push(item);
        return item;
    }
    const key = selector => selector.slice(1, -1).replace(/^data-/, '').replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
    const document = {
        head: element('head'), body: element('body'), createElement: element,
        querySelectorAll: selector => elements.filter(item => key(selector) in item.dataset),
        querySelector: selector => document.querySelectorAll(selector)[0] || null,
    };
    if (installer) {
        element('button').dataset.shopPwaInstall = '';
        element('button').dataset.shopPwaCopy = '';
        element('p').dataset.shopPwaStatus = '';
    }
    const labels = Object.fromEntries(['install', 'copyLink', 'installed', 'openChrome', 'openChromeHelp', 'iosInstall', 'iosInstructions', 'ready', 'cancelled', 'browserInstructions', 'copied', 'copyFailed', 'error', 'unsupported'].map(label => [label, label]));
    const config = {
        shopName: 'Elegance Perfumes', installUrl: 'https://ozman.test/shop-app/elegance-perfumes/install',
        shareUrl: 'https://ozman.test/shop-app/elegance-perfumes/install', serviceWorkerUrl: '/merchant-pwa-sw.js', labels,
    };
    const navigator = { userAgent };
    if (serviceWorker) navigator.serviceWorker = { register: async () => ({}) };
    if (clipboard) navigator.clipboard = { writeText: async value => copied.push(value) };
    const window = {
        OZMAN_SHOP_PWA: config, navigator,
        location: { href: 'https://ozman.test/cosmetics/elegance-perfumes', origin: 'https://ozman.test', pathname: '/cosmetics/elegance-perfumes' },
        matchMedia: query => ({ matches: query === `(display-mode: ${standalone ? 'standalone' : displayMode})` }),
        addEventListener: (name, listener) => events.set(name, listener),
        setTimeout: listener => timers.push(listener),
    };
    runInNewContext(source, { window, document, navigator, URL, console: { error() {} } });
    return {
        config, window, document, events, copied,
        button: () => document.querySelector('[data-shop-pwa-install]'),
        status: () => document.querySelector('[data-shop-pwa-status]'),
        settle: async () => { await Promise.resolve(); await Promise.resolve(); timers.splice(0).forEach(listener => listener()); },
    };
}

test('a store opened inside the installed Ozman app remains installable in Chrome', async () => {
    const app = runtime({ standalone: true });
    await app.settle();
    assert.equal(app.button().hidden, false);
    assert.equal(app.button().textContent, 'openChrome');
    assert.notEqual(app.status().textContent, 'installed');
    await app.button().listeners.click();
    assert.match(app.window.location.href, /^intent:\/\/ozman\.test\/shop-app\/elegance-perfumes\/install#/);
    assert.match(app.window.location.href, /package=com.android.chrome;/);
    assert.ok(app.window.location.href.includes(encodeURIComponent(app.config.installUrl)));
});

test('storefronts without installation controls get a visible install and copy entry', () => {
    const app = runtime({ installer: false });
    assert.equal(app.button().textContent, 'install');
    assert.equal(app.document.body.children[0].className, 'shop-pwa-bar');
    assert.equal(app.document.querySelectorAll('[data-shop-pwa-install]').length, 1);
});

test('fullscreen and minimal-ui installed apps also open the installer in Chrome', async () => {
    for (const displayMode of ['fullscreen', 'minimal-ui', 'window-controls-overlay']) {
        const app = runtime({ displayMode });
        await app.settle();
        assert.equal(app.button().textContent, 'openChrome');
        await app.button().listeners.click();
        assert.match(app.window.location.href, /^intent:\/\/ozman\.test\/shop-app\/elegance-perfumes\/install#/);
    }
});

test('the dedicated installer does not get a duplicate install bar', () => {
    const app = runtime();
    assert.equal(app.document.body.children.length, 0);
    assert.equal(app.document.querySelectorAll('[data-shop-pwa-install]').length, 1);
});

test('a supported browser consumes the install prompt and confirms only on appinstalled', async () => {
    const app = runtime();
    let prompted = 0;
    app.events.get('beforeinstallprompt')({ preventDefault() {}, prompt: async () => { prompted++; }, userChoice: Promise.resolve({ outcome: 'accepted' }) });
    await app.button().listeners.click();
    assert.equal(prompted, 1);
    assert.equal(app.button().hidden, false);
    app.events.get('appinstalled')();
    assert.equal(app.button().hidden, true);
    assert.equal(app.status().textContent, 'installed');
});

test('dismissal and prompt failures remain retryable', async () => {
    const app = runtime();
    app.events.get('beforeinstallprompt')({ preventDefault() {}, prompt: async () => {}, userChoice: Promise.resolve({ outcome: 'dismissed' }) });
    await app.button().listeners.click();
    assert.equal(app.status().textContent, 'cancelled');
    app.events.get('beforeinstallprompt')({ preventDefault() {}, prompt: async () => { throw new Error('blocked'); } });
    await app.button().listeners.click();
    assert.equal(app.status().textContent, 'browserInstructions');
    assert.equal(app.button().hidden, false);
});

test('embedded browsers retain external browser guidance without service worker support', async () => {
    const app = runtime({ userAgent: 'Android; wv) OzmanApp/1', serviceWorker: false });
    await app.settle();
    assert.equal(app.status().textContent, 'openChromeHelp');
    await app.button().listeners.click();
    assert.match(app.window.location.href, /^intent:/);
});

test('iPhone standalone mode explains Safari installation without declaring the shop installed', async () => {
    const app = runtime({ standalone: true, userAgent: 'iPhone Safari' });
    await app.settle();
    await app.button().listeners.click();
    assert.equal(app.status().textContent, 'iosInstructions');
    assert.equal(app.button().hidden, false);
});

test('copying uses the installer URL and exposes it if clipboard access is unavailable', async () => {
    const app = runtime();
    await app.document.querySelector('[data-shop-pwa-copy]').listeners.click();
    assert.deepEqual(app.copied, [app.config.installUrl]);
    const unsupported = runtime({ clipboard: false });
    await unsupported.document.querySelector('[data-shop-pwa-copy]').listeners.click();
    assert.ok(unsupported.status().textContent.includes(unsupported.config.installUrl));
});
