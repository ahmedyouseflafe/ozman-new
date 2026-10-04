// MediaPipe Pose returns one flat list of landmarks, in camera coordinates.
export function garmentPlacement(points, videoWidth, videoHeight, imageRatio) {
    const visible = point => point && Number.isFinite(point.x) && Number.isFinite(point.y)
        && Number.isFinite(point.visibility) && point.visibility >= 0.55;
    if (!videoWidth || !videoHeight || !(imageRatio > 0)
        || !visible(points?.[11]) || !visible(points?.[12])) return null;

    const shoulders = [points[11], points[12]]
        .map(p => ({ x: p.x * videoWidth, y: p.y * videoHeight }))
        .sort((a, b) => a.x - b.x);
    const dx = shoulders[1].x - shoulders[0].x;
    const dy = shoulders[1].y - shoulders[0].y;
    const span = Math.hypot(dx, dy);
    if (span < videoWidth * 0.035) return null;

    const x = (shoulders[0].x + shoulders[1].x) / 2;
    const y = (shoulders[0].y + shoulders[1].y) / 2;
    const angle = Math.atan2(dy, dx);
    const width = span * 1.8; // Include the sleeves beyond the shoulder seams.
    const naturalHeight = width * imageRatio;
    let height = naturalHeight;
    if (visible(points[23]) && visible(points[24])) {
        const hipX = (points[23].x + points[24].x) * videoWidth / 2;
        const hipY = (points[23].y + points[24].y) * videoHeight / 2;
        const torso = -(hipX - x) * Math.sin(angle) + (hipY - y) * Math.cos(angle);
        if (torso > span * 0.4) {
            height = Math.max(naturalHeight * 0.75, Math.min(naturalHeight * 1.35, torso / 0.72));
        }
    }
    return { x, y, width, height, angle };
}

export function smoothPlacement(previous, next, amount = 0.4) {
    if (!previous) return next;
    return Object.fromEntries(Object.keys(next).map(key => [key, previous[key] + (next[key] - previous[key]) * amount]));
}

const reliable = point => point && Number.isFinite(point.x) && Number.isFinite(point.y)
    && Number.isFinite(point.visibility) && point.visibility >= 0.55;
const clamp = (value, low, high) => Math.max(low, Math.min(high, value));
const ease = (low, high, value) => {
    const t = clamp((value - low) / (high - low), 0, 1);
    return t * t * (3 - 2 * t);
};

export function smoothLandmarks(previous, points, amount = 0.55) {
    return points.map((point, index) => {
        if (!reliable(point) || !reliable(previous?.[index])) return { ...point };
        const old = previous[index];
        return { ...point, x: old.x + (point.x - old.x) * amount,
            y: old.y + (point.y - old.y) * amount };
    });
}

// A short-sleeve, front-facing shirt rig. The centre stays on the torso while
// the two sleeve regions rotate independently around their own shoulder.
export function garmentMesh(points, fit, videoWidth, videoHeight) {
    const project = (u, v) => {
        const x = (u - 0.5) * fit.width, y = (v - 0.16) * fit.height;
        return { x: fit.x + x * Math.cos(fit.angle) - y * Math.sin(fit.angle),
            y: fit.y + x * Math.sin(fit.angle) + y * Math.cos(fit.angle) };
    };
    const ids = points[11].x < points[12].x ? [11, 12] : [12, 11];
    const arms = ids.map((id, side) => {
        const shoulder = points[id], elbow = points[id + 2];
        if (!reliable(elbow)) return null;
        const direction = side === 0 ? -1 : 1;
        const pivot = project(0.5 + direction / 3.6, 0.16);
        const cuff = project(side === 0 ? 0.1 : 0.9, 0.47);
        const dx = (elbow.x - shoulder.x) * videoWidth;
        const dy = (elbow.y - shoulder.y) * videoHeight;
        const length = Math.hypot(dx, dy);
        if (length < fit.width * 0.08) return null;
        const angle = Math.atan2(dy, dx) - Math.atan2(cuff.y - pivot.y, cuff.x - pivot.x);
        const scale = clamp(length * 0.76 / Math.hypot(cuff.x - pivot.x, cuff.y - pivot.y), 0.65, 1.45);
        return { pivot, angle, scale };
    });
    const columns = [0, 0.12, 0.24, 0.36, 0.5, 0.64, 0.76, 0.88, 1];
    const rows = [0, 0.16, 0.32, 0.48, 0.64, 0.82, 1];
    const vertices = rows.flatMap(v => columns.map(u => {
        const base = project(u, v);
        const arm = arms[u < 0.5 ? 0 : 1];
        const weight = ease(0.14, 0.3, Math.abs(u - 0.5)) * (1 - ease(0.44, 0.64, v));
        if (!arm || !weight) return { u, v, ...base };
        const x = base.x - arm.pivot.x, y = base.y - arm.pivot.y;
        const warped = {
            x: arm.pivot.x + arm.scale * (x * Math.cos(arm.angle) - y * Math.sin(arm.angle)),
            y: arm.pivot.y + arm.scale * (x * Math.sin(arm.angle) + y * Math.cos(arm.angle)),
        };
        return { u, v, x: base.x + (warped.x - base.x) * weight, y: base.y + (warped.y - base.y) * weight };
    }));
    const triangles = [];
    for (let row = 0; row < rows.length - 1; row++) {
        for (let col = 0; col < columns.length - 1; col++) {
            const a = row * columns.length + col, b = a + 1, c = a + columns.length, d = c + 1;
            triangles.push([a, b, d], [a, d, c]);
        }
    }
    return { vertices, triangles };
}

export function foregroundArms(points, width, height) {
    if (!reliable(points?.[11]) || !reliable(points?.[12])) return [];
    const span = Math.hypot((points[11].x - points[12].x) * width, (points[11].y - points[12].y) * height);
    const torsoZ = (points[11].z + points[12].z) / 2;
    if (!Number.isFinite(torsoZ)) return [];
    const result = [];
    for (const id of [11, 12]) {
        const elbow = points[id + 2], wrist = points[id + 4];
        if (!reliable(elbow) || !reliable(wrist) || !Number.isFinite(elbow.z) || !Number.isFinite(wrist.z)) continue;
        // Smaller z is closer to the camera. Do not paste a hidden arm over the shirt.
        const threshold = torsoZ + 0.02;
        if (elbow.z > threshold && wrist.z > threshold) continue;
        let from = 0, to = 1;
        if (elbow.z > threshold) from = (elbow.z - threshold) / (elbow.z - wrist.z);
        if (wrist.z > threshold) to = (threshold - elbow.z) / (wrist.z - elbow.z);
        const at = t => ({ x: (elbow.x + (wrist.x - elbow.x) * t) * width,
            y: (elbow.y + (wrist.y - elbow.y) * t) * height });
        const fingers = [points[id + 6], points[id + 8], points[id + 10]]
            .filter(p => reliable(p) && Number.isFinite(p.z) && p.z <= threshold);
        const hand = to === 1 && fingers.length ? {
            x: (wrist.x + fingers.reduce((sum, p) => sum + p.x, 0) / fingers.length) * width / 2,
            y: (wrist.y + fingers.reduce((sum, p) => sum + p.y, 0) / fingers.length) * height / 2,
        } : null;
        result.push({ from: at(from), to: at(to), radius: span * 0.055, hand, handRadius: span * 0.09 });
    }
    return result;
}

function drawMesh(ctx, image, mesh) {
    const iw = image.naturalWidth, ih = image.naturalHeight;
    for (const triangle of mesh.triangles) {
        const [a, b, c] = triangle.map(index => mesh.vertices[index]);
        const sx1 = (b.u - a.u) * iw, sy1 = (b.v - a.v) * ih;
        const sx2 = (c.u - a.u) * iw, sy2 = (c.v - a.v) * ih;
        const determinant = sx1 * sy2 - sy1 * sx2;
        if (Math.abs(determinant) < 0.001) continue;
        const dx1 = b.x - a.x, dy1 = b.y - a.y, dx2 = c.x - a.x, dy2 = c.y - a.y;
        const m11 = (dx1 * sy2 - dx2 * sy1) / determinant;
        const m12 = (dy1 * sy2 - dy2 * sy1) / determinant;
        const m21 = (dx2 * sx1 - dx1 * sx2) / determinant;
        const m22 = (dy2 * sx1 - dy1 * sx2) / determinant;
        ctx.save();
        ctx.beginPath();
        const center = { x: (a.x + b.x + c.x) / 3, y: (a.y + b.y + c.y) / 3 };
        // Subpixel overlap avoids hairline gaps between antialiased triangle clips.
        [a, b, c].forEach((p, i) => {
            const length = Math.hypot(p.x - center.x, p.y - center.y) || 1;
            const x = p.x + (p.x - center.x) * 0.35 / length;
            const y = p.y + (p.y - center.y) * 0.35 / length;
            if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
        });
        ctx.closePath(); ctx.clip();
        ctx.transform(m11, m12, m21, m22, a.x - m11 * a.u * iw - m21 * a.v * ih,
            a.y - m12 * a.u * iw - m22 * a.v * ih);
        const x = Math.min(a.u, b.u, c.u) * iw, y = Math.min(a.v, b.v, c.v) * ih;
        const w = Math.max(a.u, b.u, c.u) * iw - x, h = Math.max(a.v, b.v, c.v) * ih - y;
        ctx.drawImage(image, x, y, w, h, x, y, w, h);
        ctx.restore();
    }
}

function drawForeground(ctx, buffer, source, mask, arms) {
    if (!arms.length) return;
    if (buffer.width !== ctx.canvas.width || buffer.height !== ctx.canvas.height) {
        buffer.width = ctx.canvas.width; buffer.height = ctx.canvas.height;
    }
    const front = buffer.getContext('2d');
    front.clearRect(0, 0, buffer.width, buffer.height);
    front.save();
    front.strokeStyle = front.fillStyle = '#fff';
    front.lineCap = 'round';
    for (const arm of arms) {
        front.lineWidth = arm.radius * 2;
        front.beginPath(); front.moveTo(arm.from.x, arm.from.y); front.lineTo(arm.to.x, arm.to.y); front.stroke();
        if (arm.hand) {
            front.beginPath(); front.arc(arm.hand.x, arm.hand.y, arm.handRadius, 0, Math.PI * 2); front.fill();
        }
    }
    front.globalCompositeOperation = 'source-in';
    front.drawImage(source, 0, 0, buffer.width, buffer.height);
    if (mask) {
        front.globalCompositeOperation = 'destination-in';
        front.drawImage(mask, 0, 0, buffer.width, buffer.height);
    }
    front.restore();
    ctx.drawImage(buffer, 0, 0);
}

export function initClothingTryOn(root = document) {
    const layer = root.getElementById('tryOnLayer');
    if (!layer) return;
    const video = root.getElementById('tryVideo');
    const canvas = root.getElementById('tryCanvas');
    const status = root.getElementById('tryStatus');
    const ctx = canvas.getContext('2d');
    let current = null;
    const setStatus = text => { status.textContent = text; };
    const clear = () => ctx.clearRect(0, 0, canvas.width, canvas.height);

    const release = run => {
        if (!run || run.released) return;
        run.released = true;
        run.active = false;
        cancelAnimationFrame(run.frame);
        run.stream?.getTracks().forEach(track => track.stop());
        // Closing the WASM model during send() can race with its result callback.
        Promise.resolve(run.inference).catch(() => {}).then(() => run.pose?.close()).catch(() => {});
    };
    const stop = () => {
        const run = current;
        current = null;
        release(run);
        video.pause();
        video.srcObject = null;
        clear();
    };
    const close = () => { layer.classList.remove('open'); stop(); };
    const loadImage = url => new Promise((resolve, reject) => {
        const image = new Image();
        image.crossOrigin = 'anonymous';
        image.onload = () => resolve(image);
        image.onerror = () => reject(new Error('garment'));
        image.src = url;
    });

    const start = async url => {
        stop();
        layer.classList.add('open');
        const run = { active: true, frame: null, previous: null, inference: null,
            cameraFrame: root.createElement('canvas'), foreground: root.createElement('canvas') };
        current = run;
        const isCurrent = () => current === run && run.active;
        setStatus('جاري تجهيز القطعة والكاميرا…');
        try {
            if (!navigator.mediaDevices?.getUserMedia) throw new Error('camera-unavailable');
            if (!window.Pose) throw new Error('tracking-unavailable');
            run.garment = await loadImage(url);
            if (!isCurrent()) return;
            setStatus('اسمح للكاميرا واظهر كتفيك وخصرك داخل الإطار');
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 960 } }, audio: false,
            });
            if (!isCurrent()) { stream.getTracks().forEach(track => track.stop()); return; }
            run.stream = stream;
            video.srcObject = stream;
            await video.play();
            if (!isCurrent()) return;
            video.parentElement.style.aspectRatio = `${video.videoWidth} / ${video.videoHeight}`;
            run.pose = new window.Pose({ locateFile: file => 'https://cdn.jsdelivr.net/npm/@mediapipe/pose/' + file });
            run.pose.setOptions({ modelComplexity: 1, smoothLandmarks: true, enableSegmentation: true, smoothSegmentation: true,
                minDetectionConfidence: 0.55, minTrackingConfidence: 0.55 });
            run.pose.onResults(results => {
                if (!isCurrent() || !video.videoWidth || !video.videoHeight) return;
                // Both layers contain the whole camera frame, including raised arms.
                if (canvas.width !== video.videoWidth || canvas.height !== video.videoHeight) {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    run.previous = null;
                }
                clear();
                // Use the exact frame passed to inference, so arms and cloth cannot
                // drift apart while the live camera advances during model processing.
                ctx.drawImage(run.cameraFrame, 0, 0, canvas.width, canvas.height);
                const placement = garmentPlacement(results.poseLandmarks, canvas.width, canvas.height,
                    run.garment.naturalHeight / run.garment.naturalWidth);
                if (!placement) {
                    run.previous = null;
                    run.points = null;
                    setStatus('قف مقابل الكاميرا وأظهر كتفيك داخل الإطار');
                    return;
                }
                run.points = smoothLandmarks(run.points, results.poseLandmarks);
                const fit = garmentPlacement(run.points, canvas.width, canvas.height,
                    run.garment.naturalHeight / run.garment.naturalWidth);
                drawMesh(ctx, run.garment, garmentMesh(run.points, fit, canvas.width, canvas.height));
                drawForeground(ctx, run.foreground, run.cameraFrame, results.segmentationMask,
                    foregroundArms(results.poseLandmarks, canvas.width, canvas.height));
                setStatus(reliable(results.poseLandmarks[13]) && reliable(results.poseLandmarks[14])
                    ? 'حرّك ذراعيك بهدوء — الأكمام تتبع حركتك'
                    : 'أظهر مرفقيك داخل الإطار حتى تتحرك الأكمام');
            });
            setStatus('جاري تحميل التتبّع، خليك مقابل الكاميرا…');
            const tick = async () => {
                if (!isCurrent()) return;
                try {
                    if (video.readyState >= 2 && video.videoWidth && video.videoHeight) {
                        if (run.cameraFrame.width !== video.videoWidth || run.cameraFrame.height !== video.videoHeight) {
                            run.cameraFrame.width = video.videoWidth; run.cameraFrame.height = video.videoHeight;
                            run.points = null;
                        }
                        run.cameraFrame.getContext('2d').drawImage(video, 0, 0);
                        run.inference = run.pose.send({ image: run.cameraFrame });
                        await run.inference;
                    }
                    if (isCurrent()) run.frame = requestAnimationFrame(tick);
                } catch (_) {
                    if (!isCurrent()) return;
                    stop();
                    setStatus('تعذّر تشغيل تتبّع الجسم. تأكد من الاتصال وأعد فتح التجربة.');
                }
            };
            void tick();
        } catch (error) {
            if (!isCurrent()) return;
            stop();
            const messages = {
                garment: 'تعذّر تحميل صورة القطعة. جرّب صورة أخرى بخلفية شفافة.',
                'camera-unavailable': 'الكاميرا غير متاحة هنا. افتح الصفحة باتصال HTTPS أو من localhost.',
                'tracking-unavailable': 'تعذّر تحميل التتبّع. تأكد من الاتصال وأعد تحميل الصفحة.',
                NotAllowedError: 'اسمح باستخدام الكاميرا من إعدادات المتصفح ثم أعد المحاولة.',
                NotFoundError: 'ما لقينا كاميرا متصلة بالجهاز.',
                NotReadableError: 'تعذّر فتح الكاميرا. أغلق أي تطبيق آخر يستخدمها ثم حاول مجددًا.',
            };
            setStatus(messages[error.name] || messages[error.message] || 'تعذّر بدء التجربة. أغلقها وحاول مجددًا.');
        }
    };

    root.getElementById('tryOn')?.addEventListener('click', () => {
        const url = root.getElementById('quickImage')?.src;
        if (url) void start(url);
    });
    root.getElementById('demoTryOn')?.addEventListener('click', event => void start(event.currentTarget.dataset.image));
    root.querySelectorAll('[data-close="tryOnLayer"]').forEach(button => button.addEventListener('click', close));
    layer.addEventListener('click', event => { if (event.target === layer) close(); });
    root.addEventListener('keydown', event => { if (event.key === 'Escape' && layer.classList.contains('open')) close(); });
    window.addEventListener('pagehide', close);
    root.addEventListener('visibilitychange', () => { if (root.hidden) close(); });
}

if (typeof document !== 'undefined') initClothingTryOn();
