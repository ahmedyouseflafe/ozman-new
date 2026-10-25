(() => {
    'use strict';
    class CreatorCamera {
        constructor(root, onFile, onStatus, onActive) {
            this.root = root; this.onFile = onFile; this.onStatus = onStatus; this.onActive = onActive;
            this.video = root.querySelector('[data-camera-video]'); this.status = root.querySelector('[data-camera-status]');
            this.recordButton = root.querySelector('[data-camera-record]'); this.snapButton = root.querySelector('[data-camera-snap]');
            this.switchButton = root.querySelector('[data-camera-switch]');
            this.stream = null; this.recorder = null; this.sequence = 0; this.facing = 'environment'; this.mode = 'photo'; this.timer = null;
            root.querySelector('[data-camera-stop]').addEventListener('click', () => this.stop());
            this.switchButton.addEventListener('click', () => { this.facing = this.facing === 'user' ? 'environment' : 'user';this.open(this.mode); });
            this.snapButton?.addEventListener('click', () => this.snap());
            this.recordButton?.addEventListener('click', () => this.recorder?.state === 'recording' ? this.recorder.stop() : this.record());
            document.addEventListener('visibilitychange', () => { if (document.hidden) this.stop(); });
            window.addEventListener('pagehide', () => this.stop());
        }
        message(text) { this.status.textContent = text; this.onStatus(text); }
        stop() {
            this.sequence++; clearInterval(this.timer);this.timer = null;
            if (this.recorder && this.recorder.state !== 'inactive') this.recorder.stop();
            this.recorder = null;
            this.stream?.getTracks().forEach(track => track.stop());this.stream = null;
            this.video.pause();this.video.srcObject = null;this.root.hidden = true;this.onActive(false);
        }
        async open(mode) {
            this.stop();this.mode = mode;
            if (!navigator.mediaDevices?.getUserMedia || !window.isSecureContext) {
                this.message('الكاميرا غير مدعومة هنا. افتح الموقع بمتصفح حديث عبر HTTPS أو استخدم كاميرا الجهاز من خيار الملف.');return;
            }
            const attempt = this.sequence;let timedOut = false;
            this.root.hidden = false;this.onActive(true);this.switchButton.disabled = true;
            if (this.snapButton) {this.snapButton.hidden = mode !== 'photo';this.snapButton.disabled = true;}
            if (this.recordButton) {this.recordButton.hidden = mode !== 'video';this.recordButton.disabled = true;this.recordButton.textContent = 'بدء التسجيل';}
            this.message('اسمح باستخدام الكاميرا' + (mode === 'photo' ? '.' : ' والمايك.'));
            const timeout = setTimeout(() => {
                if (attempt !== this.sequence) return;
                timedOut = true;this.stop();this.message('تأخر إذن الكاميرا. اسمح بالوصول من إعدادات المتصفح وحاول مجددًا.');
            }, 15000);
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: {facingMode: {ideal: this.facing}, width: {ideal: 1280}, height: {ideal: 720}},
                    audio: mode !== 'photo',
                });
                if (attempt !== this.sequence || timedOut) {stream.getTracks().forEach(track => track.stop());return;}
                clearTimeout(timeout);this.stream = stream;this.video.srcObject = stream;
                this.video.style.transform = this.facing === 'user' ? 'scaleX(-1)' : '';
                await this.video.play();
                if (attempt !== this.sequence) return;
                this.switchButton.disabled = false;if (this.snapButton) this.snapButton.disabled = false;
                if (this.recordButton) this.recordButton.disabled = !window.MediaRecorder;
                this.message(mode === 'preview' ? 'معاينة خاصة. الكاميرا والمايك متصلان؛ لا يوجد بث أو تسجيل.' : mode === 'video' ? (window.MediaRecorder ? 'الكاميرا جاهزة. اضغط بدء التسجيل، والحد الأقصى دقيقة.' : 'التسجيل غير مدعوم بهذا المتصفح. استخدم كاميرا الجهاز من خيار الملف.') : 'الكاميرا جاهزة لالتقاط صورة.');
                stream.getTracks().forEach(track => track.addEventListener('ended', () => {if(attempt === this.sequence){this.stop();this.message('توقفت الكاميرا أو المايك. افتحها مجددًا للمحاولة.');}}));
            } catch (error) {
                if (attempt !== this.sequence) return;
                this.stop();this.message(error.name === 'NotAllowedError' ? 'لم يُسمح بالكاميرا أو المايك. فعّل الإذن من إعدادات المتصفح أو اختار ملفًا.' : error.name === 'NotFoundError' ? 'ما لقينا كاميرا أو مايك متاحين على الجهاز.' : 'تعذّر فتح الكاميرا. أغلق أي تطبيق يستخدمها وحاول مجددًا.');
            } finally {clearTimeout(timeout);}
        }
        snap() {
            if (!this.stream || !this.video.videoWidth) return;
            const attempt = this.sequence;const canvas = document.createElement('canvas');
            const scale = Math.min(1, 1920 / Math.max(this.video.videoWidth, this.video.videoHeight));
            canvas.width = Math.round(this.video.videoWidth * scale);canvas.height = Math.round(this.video.videoHeight * scale);
            canvas.getContext('2d').drawImage(this.video, 0, 0, canvas.width, canvas.height);
            this.snapButton.disabled = true;
            canvas.toBlob(blob => {
                if (attempt !== this.sequence) return;
                this.snapButton.disabled = false;
                if (!blob) {this.message('تعذّر التقاط الصورة. حاول مجددًا.');return;}
                this.stop();this.onFile(new File([blob], `photo-${Date.now()}.jpg`, {type: 'image/jpeg'}));
            }, 'image/jpeg', .9);
        }
        record() {
            if (!this.stream || !window.MediaRecorder) return;
            const mimeType = ['video/mp4', 'video/webm;codecs=vp8,opus', 'video/webm'].find(type => MediaRecorder.isTypeSupported(type));
            if (!mimeType) {this.message('صيغة التسجيل غير مدعومة. استخدم كاميرا الجهاز لرفع فيديو MP4.');return;}
            const attempt = this.sequence;const chunks = [];let size = 0, failed = false;
            try {
                const recorder = new MediaRecorder(this.stream, {mimeType, videoBitsPerSecond: 1800000, audioBitsPerSecond: 96000});
                this.recorder = recorder;
                recorder.addEventListener('dataavailable', event => {if (event.data.size) {chunks.push(event.data);size += event.data.size;if (size >= 18 * 1024 * 1024 && recorder.state === 'recording') recorder.stop();}});
                recorder.addEventListener('error', () => {failed = true;this.stop();this.message('تعذّر تسجيل الفيديو. حاول مرة أخرى أو استخدم كاميرا الجهاز.');});
                recorder.addEventListener('stop', () => {
                    if (attempt !== this.sequence || failed) return;
                    clearInterval(this.timer);this.timer = null;
                    const type = recorder.mimeType.split(';')[0];const blob = new Blob(chunks, {type});
                    this.stop();
                    if (!blob.size || blob.size > 20 * 1024 * 1024) {this.message('الفيديو فارغ أو أكبر من 20 ميجابايت. صوّر فيديو أقصر.');return;}
                    this.onFile(new File([blob], `video-${Date.now()}.${type === 'video/mp4' ? 'mp4' : 'webm'}`, {type}));
                });
                recorder.start(500);this.switchButton.disabled = true;this.recordButton.textContent = 'إيقاف التسجيل';
                const started = performance.now();this.message('جارٍ التسجيل 0 / 60 ثانية');
                this.timer = setInterval(() => {const seconds = Math.floor((performance.now() - started) / 1000);this.message(`جارٍ التسجيل ${Math.min(seconds,60)} / 60 ثانية`);if (seconds >= 60 && recorder.state === 'recording') recorder.stop();}, 250);
            } catch (_) {this.stop();this.message('تعذّر بدء التسجيل. استخدم كاميرا الجهاز أو اختار فيديو محفوظًا.');}
        }
    }
    window.OzmanCreatorCamera = CreatorCamera;
    const live = document.getElementById('creatorLivePreparation');
    if (!live) return;
    let opener = null, overflow = '';
    const status = document.getElementById('livePreparationStatus');
    const camera = new CreatorCamera(live.querySelector('[data-camera]'), () => {}, message => status.textContent = message, () => {});
    document.querySelectorAll('[data-live-prepare]').forEach(button => button.addEventListener('click', () => {
        opener = button;overflow = document.body.style.overflow;document.body.style.overflow = 'hidden';status.textContent = '';live.showModal();
    }));
    document.getElementById('livePreparationCamera').addEventListener('click', () => camera.open('preview'));
    document.getElementById('livePreparationClose').addEventListener('click', () => live.close());
    live.addEventListener('close', () => {camera.stop();document.body.style.overflow = overflow;opener?.focus({preventScroll:true});});
})();
