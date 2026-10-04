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
        const run = { active: true, frame: null, previous: null, inference: null };
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
            run.pose = new window.Pose({ locateFile: file => 'https://cdn.jsdelivr.net/npm/@mediapipe/pose/' + file });
            run.pose.setOptions({ modelComplexity: 1, smoothLandmarks: true, enableSegmentation: false,
                minDetectionConfidence: 0.55, minTrackingConfidence: 0.55 });
            run.pose.onResults(results => {
                if (!isCurrent() || !video.videoWidth || !video.videoHeight) return;
                // Both canvas and video use identical cover sizing and mirror transforms.
                if (canvas.width !== video.videoWidth || canvas.height !== video.videoHeight) {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    run.previous = null;
                }
                clear();
                const placement = garmentPlacement(results.poseLandmarks, canvas.width, canvas.height,
                    run.garment.naturalHeight / run.garment.naturalWidth);
                if (!placement) {
                    run.previous = null;
                    setStatus('قف مقابل الكاميرا وأظهر كتفيك داخل الإطار');
                    return;
                }
                const fit = smoothPlacement(run.previous, placement);
                run.previous = fit;
                ctx.save();
                ctx.translate(fit.x, fit.y);
                ctx.rotate(fit.angle);
                // Shoulder line sits below the collar, not halfway down the shirt.
                ctx.drawImage(run.garment, -fit.width / 2, -fit.height * 0.16, fit.width, fit.height);
                ctx.restore();
                setStatus('القطعة تتبع جسمك — ابتعد قليلًا لتظهر كاملة');
            });
            setStatus('جاري تحميل التتبّع، خليك مقابل الكاميرا…');
            const tick = async () => {
                if (!isCurrent()) return;
                try {
                    if (video.readyState >= 2) {
                        run.inference = run.pose.send({ image: video });
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
