import { buildCaravan, disposeTree } from './caravan-model.js';

// Pinned CDN dependencies are fetched only after the visitor opens the demo.
const THREE_URL = 'https://cdn.jsdelivr.net/npm/three@0.160.1/build/three.module.js';
const XR_URL = 'https://cdn.jsdelivr.net/npm/@8thwall/engine-binary@1.0.0/dist/xr.js';
let threePromise, xrPromise;
const loadThree = () => threePromise ||= import(THREE_URL).catch(error => { threePromise = null; throw error; });
function loadXr() {
    if (xrPromise) return xrPromise;
    xrPromise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        const finish = error => {
            clearTimeout(timer); window.removeEventListener('xrloaded', ready);
            if (error) { script.remove(); xrPromise = null; reject(error); } else resolve(window.XR8);
        };
        const ready = () => finish();
        const timer = setTimeout(() => finish(new Error('XR load timeout')), 45000);
        window.addEventListener('xrloaded', ready, { once: true });
        script.src = XR_URL; script.async = true; script.crossOrigin = 'anonymous';
        script.dataset.preloadChunks = 'slam';
        script.onerror = () => finish(new Error('XR download failed'));
        document.head.append(script);
    });
    return xrPromise;
}

export class CaravanDemo {
    constructor(element) {
        this.element = element;
        this.copy = JSON.parse(element.querySelector('[data-caravan-copy]').textContent);
        this.dialog = element.querySelector('dialog');
        this.host = element.querySelector('[data-caravan-canvas]');
        this.status = element.querySelector('[data-caravan-status]');
        this.cameraButton = element.querySelector('[data-caravan-camera]');
        this.placeButton = element.querySelector('[data-caravan-place]');
        this.previewButton = element.querySelector('[data-caravan-preview]');
        this.retryButton = element.querySelector('[data-caravan-retry]');
        this.options = { bathroom: true, kitchen: false };
        this.mode = 'closed'; this.token = 0; this.rotation = 0;
        this.arScale = .15;
        this.scaleControls = element.querySelector('[data-caravan-scale-controls]');
        element.querySelector('[data-caravan-scale]').addEventListener('input', event => this.setArScale(Number(event.target.value) / 100));
        element.querySelector('[data-caravan-full-size]').addEventListener('click', () => this.setArScale(1));
        this.view = { theta: .65, phi: 1.14, radius: 16 };
        element.querySelector('[data-caravan-open]').addEventListener('click', () => this.open());
        element.querySelector('[data-caravan-close]').addEventListener('click', () => this.close());
        this.dialog.addEventListener('cancel', event => { event.preventDefault(); this.close(); });
        this.dialog.addEventListener('close', () => { if (!this.dialog.open && this.mode !== 'closed') this.close(); });
        this.cameraButton.addEventListener('click', () => this.startAr());
        this.previewButton.addEventListener('click', () => this.startPreview());
        this.retryButton.addEventListener('click', () => this.startPreview());
        this.placeButton.addEventListener('click', () => this.place());
        element.querySelectorAll('[data-part]').forEach(button => button.addEventListener('click', () => {
            const part = button.dataset.part; this.options[part] = !this.options[part]; this.syncOptions();
        }));
        element.querySelectorAll('[data-rotate]').forEach(button => button.addEventListener('click', () => {
            this.rotation += Number(button.dataset.rotate) * Math.PI / 12;
            if (this.model) this.model.root.rotation.y = this.rotation;
            this.render();
        }));
        element.querySelectorAll('[data-zoom]').forEach(button => button.addEventListener('click', () => {
            this.view.radius = Math.max(7, Math.min(30, this.view.radius + Number(button.dataset.zoom) * 1.5)); this.render();
        }));
        let pointer;
        this.host.addEventListener('pointerdown', event => {
            if (this.mode !== 'preview') return;
            pointer = { id: event.pointerId, x: event.clientX, y: event.clientY };
            this.host.setPointerCapture(event.pointerId);
        });
        this.host.addEventListener('pointermove', event => {
            if (!pointer || pointer.id !== event.pointerId || this.mode !== 'preview') return;
            this.view.theta -= (event.clientX - pointer.x) * .008;
            this.view.phi = Math.max(.35, Math.min(1.5, this.view.phi - (event.clientY - pointer.y) * .006));
            pointer.x = event.clientX; pointer.y = event.clientY; this.render();
        });
        for (const name of ['pointerup', 'pointercancel', 'lostpointercapture']) this.host.addEventListener(name, () => { pointer = null; });
        this.host.addEventListener('wheel', event => {
            if (this.mode !== 'preview') return;
            event.preventDefault(); this.view.radius = Math.max(7, Math.min(30, this.view.radius + event.deltaY * .01)); this.render();
        }, { passive: false });
        this.resizeObserver = new ResizeObserver(() => this.resize());
        this.resizeObserver.observe(this.host);
        // Release the actual camera on navigation, app backgrounding, and closing.
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && ['ar', 'ar-loading'].includes(this.mode)) this.startPreview();
        });
        window.addEventListener('pagehide', () => this.close());
        this.syncOptions();
    }
    say(key) { const text = this.copy[key] || key; if (this.status.textContent !== text) this.status.textContent = text; }
    setMode(mode) {
        this.mode = mode; this.dialog.dataset.mode = mode;
        const ar = mode === 'ar' || mode === 'ar-loading';
        this.cameraButton.hidden = ar; this.cameraButton.disabled = mode !== 'preview';
        this.previewButton.hidden = !ar; this.placeButton.hidden = mode !== 'ar';
        this.retryButton.hidden = mode !== 'error';
        this.scaleControls.hidden = mode !== 'ar';
        this.updateScaleLabel();
        this.element.querySelectorAll('[data-rotate],[data-zoom]').forEach(button => button.disabled = !['preview', 'ar'].includes(mode));
    }
    setArScale(value) {
        this.arScale = Math.max(.05, Math.min(1, value));
        if (this.mode === 'ar' && this.model) this.model.root.scale.setScalar(this.arScale);
        this.updateScaleLabel();
    }
    updateScaleLabel() {
        this.element.querySelector('[data-caravan-scale]').value = Math.round(this.arScale * 100);
        this.element.querySelector('[data-caravan-scale-label]').textContent =
            `${this.copy[this.arScale === 1 ? 'fullScale' : 'miniScale']} · ${Math.round(this.arScale * 100)}%`;
    }
    syncOptions() {
        this.model?.setOptions(this.options);
        this.element.querySelectorAll('[data-part]').forEach(button => {
            const active = this.options[button.dataset.part];
            button.setAttribute('aria-pressed', String(active));
            button.querySelector('[data-part-label]').textContent = this.copy[active ? 'included' : 'add'];
            button.querySelector('b').textContent = active ? '✓' : '+';
        });
        const summary = ['bathroom', 'kitchen'].map(part => `${this.copy[this.options[part] ? 'yes' : 'no']} ${this.copy[part]}`).join(' · ');
        this.element.querySelector('[data-caravan-summary]').textContent = summary;
        const link = this.element.querySelector('[data-caravan-contact]');
        if (link) {
            const url = new URL(this.element.dataset.contact);
            url.searchParams.set('text', `${this.copy.message}\n${summary}\n${this.copy.note}`);
            link.href = url.href;
        }
        this.render();
    }
    open() {
        if (this.dialog.open) return;
        this.previousOverflow = document.body.style.overflow;
        this.previousFocus = document.activeElement;
        this.dialog.showModal(); document.body.style.overflow = 'hidden';
        this.startPreview();
    }
    close() {
        ++this.token; this.teardown(); this.setMode('closed');
        if (this.dialog.open) this.dialog.close();
        if (this.previousOverflow !== undefined) document.body.style.overflow = this.previousOverflow;
        this.previousFocus?.focus({ preventScroll: true });
    }
    teardown() {
        clearTimeout(this.arTimer);
        if (this.xrRunning) {
            this.xrRunning = false;
            window.XR8.stop(); window.XR8.clearCameraPipelineModules();
        }
        if (this.scene) disposeTree(this.scene);
        if (this.renderer && this.mode !== 'ar' && this.mode !== 'ar-loading') {
            this.renderer.dispose(); this.renderer.forceContextLoss();
        }
        this.host.replaceChildren();
        this.scene = this.renderer = this.camera = this.model = this.ring = this.hit = null;
        this.placed = false;
    }
    addLights(scene) {
        const T = this.THREE;
        scene.add(new T.HemisphereLight(0xfff5df, 0x6c877e, 2.2));
        const light = new T.DirectionalLight(0xffefd2, 3.4); light.position.set(-3, 9, 5);
        light.castShadow = true;
        Object.assign(light.shadow.camera, { left: -10, right: 10, top: 9, bottom: -9, near: .1, far: 35 });
        light.shadow.mapSize.set(1024, 1024); light.shadow.bias = -.001;
        scene.add(light);
    }
    async startPreview(message = null) {
        const token = ++this.token;
        this.teardown(); this.setMode('loading'); this.say('loading');
        try {
            const T = await loadThree();
            if (token !== this.token || !this.dialog.open) return;
            this.THREE = T;
            this.scene = new T.Scene(); this.scene.background = new T.Color(0xdde1d2);
            this.camera = new T.PerspectiveCamera(42, 1, .1, 150);
            this.renderer = new T.WebGLRenderer({ antialias: true, preserveDrawingBuffer: true });
            this.renderer.setPixelRatio(Math.min(devicePixelRatio || 1, 1.75));
            this.renderer.shadowMap.enabled = true; this.renderer.shadowMap.type = T.PCFSoftShadowMap;
            this.renderer.toneMapping = T.ACESFilmicToneMapping; this.renderer.toneMappingExposure = 1;
            this.host.append(this.renderer.domElement); this.addLights(this.scene);
            this.model = buildCaravan(T, this.options); this.model.root.rotation.y = this.rotation;
            this.scene.add(this.model.root);
            const floor = new T.Mesh(new T.PlaneGeometry(200, 200), new T.MeshStandardMaterial({ color: 0xdde1d2, roughness: 1 }));
            floor.rotation.x = -Math.PI / 2; floor.position.y = -.01; floor.receiveShadow = true; this.scene.add(floor);
            const grid = new T.GridHelper(24, 24, 0xb5c1ae, 0xcbd3c2); grid.position.y = -.005; this.scene.add(grid);
            this.setMode('preview'); this.say(message || 'orbit'); this.resize();
        } catch (error) {
            if (token !== this.token) return;
            this.teardown(); this.setMode('error'); this.say('loadError');
            console.warn('Caravan preview unavailable:', error.name || 'Error');
        }
    }
    resize() {
        if (!this.renderer || !this.camera) return;
        const width = this.host.clientWidth, height = this.host.clientHeight;
        if (!width || !height) return;
        if (this.mode === 'preview') {
            this.renderer.setSize(width, height, false);
            this.camera.aspect = width / height; this.camera.updateProjectionMatrix(); this.render();
        } else if (this.mode === 'ar') {
            // XR8 observes the canvas drawing-buffer size and updates the projection.
            const canvas = this.host.querySelector('canvas');
            if (canvas) { canvas.width = width; canvas.height = height; }
        }
    }
    render() {
        if (this.mode !== 'preview' || !this.renderer) return;
        const { theta, phi, radius } = this.view;
        const distance = radius * Math.max(1, .95 / (this.camera.aspect || 1));
        this.camera.position.set(distance * Math.sin(phi) * Math.sin(theta), distance * Math.cos(phi) + 1, distance * Math.sin(phi) * Math.cos(theta));
        this.camera.lookAt(0, 1.1, 0); this.renderer.render(this.scene, this.camera);
    }
    async startAr() {
        if (this.mode !== 'preview') return;
        if (!window.isSecureContext) { this.say('secure'); return; }
        if (!/Android|iPhone|iPad|iPod/i.test(navigator.userAgent) && !(navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)) { this.say('desktop'); return; }
        const token = ++this.token;
        this.teardown(); this.setMode('ar-loading'); this.say('engine');
        try {
            // iOS requires these requests before the first await, during the button gesture.
            const permissionRequests = [window.DeviceMotionEvent, window.DeviceOrientationEvent]
                .filter(api => typeof api?.requestPermission === 'function')
                .map(api => api.requestPermission());
            const permissions = await Promise.all(permissionRequests);
            if (permissions.some(permission => permission !== 'granted')) throw new Error('Motion denied');
            if (token !== this.token || !this.dialog.open) return;
            window.THREE = this.THREE;
            const XR8 = await loadXr();
            if (token !== this.token || !this.dialog.open) return;
            XR8.XrController.configure({ disableWorldTracking: false, scale: 'absolute' });
            const canvas = document.createElement('canvas');
            canvas.width = this.host.clientWidth; canvas.height = this.host.clientHeight; this.host.append(canvas);
            const fail = () => { if (token === this.token && this.dialog.open) this.startPreview('cameraError'); };
            const T = this.THREE;
            const raycaster = new T.Raycaster();
            const ground = new T.Plane(new T.Vector3(0, 1, 0), 0);
            XR8.addCameraPipelineModules([
                XR8.GlTextureRenderer.pipelineModule(), XR8.Threejs.pipelineModule(), XR8.XrController.pipelineModule(),
                {
                    name: 'ozman-caravan',
                    onStart: () => {
                        if (token !== this.token) { XR8.stop(); return; }
                        clearTimeout(this.arTimer);
                        const { scene, camera, renderer } = XR8.Threejs.xrScene();
                        this.scene = scene; this.camera = camera; this.renderer = renderer;
                        this.addLights(scene);
                        this.model = buildCaravan(T, this.options); this.model.root.rotation.y = this.rotation;
                        this.model.root.scale.setScalar(this.arScale);
                        this.model.root.visible = false; scene.add(this.model.root);
                        this.ring = new T.Mesh(new T.RingGeometry(.22, .3, 40), new T.MeshBasicMaterial({ color: 0xbff59b, side: T.DoubleSide }));
                        this.ring.rotation.x = -Math.PI / 2; this.ring.visible = false; scene.add(this.ring);
                        XR8.XrController.updateCameraProjectionMatrix({ origin: { x: 0, y: 1.6, z: 0 }, facing: { w: 1, x: 0, y: 0, z: 0 } });
                        this.setMode('ar'); this.say('scan'); this.placeButton.disabled = true;
                        this.placeButton.textContent = this.copy.place;
                    },
                    onUpdate: ({ processCpuResult }) => {
                        if (token !== this.token || !this.model) return;
                        const normal = processCpuResult?.reality?.trackingStatus === 'NORMAL';
                        if (!normal) {
                            this.hit = null; this.ring.visible = false;
                            this.placeButton.disabled = true; this.say(this.placed ? 'lost' : 'scan'); return;
                        }
                        if (this.placed) { this.say('placed'); this.placeButton.disabled = false; return; }
                        // Prefer horizontal surfaces. Browser SLAM can expose only feature
                        // points; in absolute mode y=0 is the engine's estimated ground level.
                        const hits = XR8.XrController.hitTest(.5, .5);
                        this.hit = hits.find(hit => {
                            if (!['DETECTED_SURFACE', 'ESTIMATED_SURFACE'].includes(hit.type) || !hit.rotation) return false;
                            const up = new T.Vector3(0, 1, 0).applyQuaternion(new T.Quaternion(hit.rotation.x, hit.rotation.y, hit.rotation.z, hit.rotation.w));
                            return up.y > .94 && hit.position.y < this.camera.position.y && hit.distance > .5 && hit.distance < 35;
                        }) || null;
                        if (!this.hit && this.camera.position.y > .2) {
                            this.camera.updateMatrixWorld(true);
                            raycaster.setFromCamera({ x: 0, y: 0 }, this.camera);
                            const point = raycaster.ray.direction.y < -.05
                                ? raycaster.ray.intersectPlane(ground, new T.Vector3()) : null;
                            const distance = point?.distanceTo(this.camera.position);
                            if (point && distance > .5 && distance < 35) this.hit = { position: point, type: 'ESTIMATED_GROUND' };
                        }
                        this.ring.visible = Boolean(this.hit); this.placeButton.disabled = !this.hit;
                        if (this.hit) this.ring.position.set(this.hit.position.x, this.hit.position.y + .025, this.hit.position.z);
                        this.say(this.hit ? 'ready' : 'scan');
                    },
                    onCameraStatusChange: ({ status }) => { if (status === 'failed') queueMicrotask(fail); },
                    onException: error => { console.warn('Caravan AR engine:', error); queueMicrotask(fail); },
                },
            ]);
            this.xrRunning = true;
            this.arTimer = setTimeout(fail, 60000);
            await XR8.run({ canvas, allowedDevices: XR8.XrConfig.device().MOBILE, cameraConfig: { direction: XR8.XrConfig.camera().BACK } });
            // An outstanding permission prompt may resolve after the dialog is closed.
            if (token !== this.token && !this.xrRunning) XR8.stop();
        } catch (error) {
            console.warn('Caravan AR startup:', error?.message || 'Unavailable');
            if (token === this.token && this.dialog.open) this.startPreview('cameraError');
        }
    }
    place() {
        if (this.mode !== 'ar' || this.placeButton.disabled) return;
        if (this.placed) {
            this.placed = false; this.hit = null; this.model.root.visible = false;
            this.placeButton.disabled = true; this.placeButton.textContent = this.copy.place; this.say('scan'); return;
        }
        if (!this.hit) return;
        const { x, y, z } = this.hit.position;
        this.model.root.position.set(x, y, z); this.model.root.visible = true;
        this.placed = true; this.ring.visible = false; this.hit = null;
        this.placeButton.textContent = this.copy.move; this.say('placed');
    }
}

document.querySelectorAll('[data-caravan-demo]').forEach(element => new CaravanDemo(element));
