/* Voice for the assistants on the website: talk instead of typing (the browser's own speech recognition), and hear
   the answer: a natural voice from the site (Azure) when it's set up, otherwise the browser's voices, otherwise the
   site's Google reader. Also drives the assistant's face (window.OzmanAvatar): listening, thinking, speaking, idle. */
(() => {
    if (window.OzmanVoice) return;
    const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const speechLang = (lang) => ({ar: 'ar-JO', he: 'he-IL', en: 'en-US'}[String(lang || 'ar').slice(0, 2)] || 'ar-JO');
    const ttsUrl = {ar: '/tts/arabic', he: '/tts/hebrew'};
    const config = {speechUrl: '/assistant-speech', csrf: () => document.querySelector('meta[name=csrf-token]')?.content || ''};
    let audio = null, queue = [], turn = 0, busy = false;

    const face = (state) => window.OzmanAvatar?.set(state);
    const started = () => face('speaking');
    const finished = (myTurn) => { if (myTurn === turn) { busy = false; face('idle'); } };

    // What the assistant wrote, as it should sound: no markdown, links or emoji.
    const plain = (text) => String(text || '')
        .replace(/\*\*(.+?)\*\*/g, '$1').replace(/^#{1,3}\s+/gm, '').replace(/^\s*(?:[-•*]|\d+[.)])\s+/gm, '')
        .replace(/https?:\/\/\S+/g, '').replace(/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/gu, '').replace(/\s+\n/g, '\n').trim();

    // Google's reader takes short pieces: split at sentence ends, then at commas, under ~180 characters.
    const pieces = (text) => {
        const out = [];
        for (const sentence of text.split(/(?<=[.!؟?\n])\s+/)) {
            let rest = sentence.trim();
            while (rest.length > 180) {
                let cut = rest.lastIndexOf('،', 180);
                if (cut < 60) cut = rest.lastIndexOf(',', 180);
                if (cut < 60) cut = rest.lastIndexOf(' ', 180);
                if (cut < 60) cut = 180;
                out.push(rest.slice(0, cut + 1).trim());
                rest = rest.slice(cut + 1).trim();
            }
            if (rest) out.push(rest);
        }
        return out;
    };

    const stopSpeaking = () => {
        turn++;
        busy = false;
        queue = [];
        if (audio) { audio.pause(); audio = null; }
        try { window.speechSynthesis?.cancel(); } catch (e) {}
        face('idle');
    };

    // The browser's own voice, or the site's Google reader.
    // The browser's own voice, or the site's Google reader. Resolves when it has finished.
    const speakLocally = (words, lang, myTurn) => new Promise((resolve) => {
        const code = speechLang(lang), short = code.slice(0, 2);
        const voice = (window.speechSynthesis?.getVoices() || []).find((v) => v.lang?.toLowerCase().startsWith(short));
        if (voice) {
            const utterance = new SpeechSynthesisUtterance(words);
            utterance.voice = voice; utterance.lang = voice.lang; utterance.rate = 1;
            utterance.onstart = started;
            utterance.onend = utterance.onerror = () => resolve();
            window.speechSynthesis.speak(utterance);
        } else if (ttsUrl[short]) {
            queue = pieces(words);
            const next = () => {
                const piece = queue.shift();
                if (!piece || myTurn !== turn) { audio = null; resolve(); return; }
                audio = new Audio(`${ttsUrl[short]}?text=${encodeURIComponent(piece)}`);
                audio.onplaying = started;
                audio.onended = next;
                audio.onerror = () => { queue = []; audio = null; resolve(); };
                audio.play().catch(() => { queue = []; audio = null; resolve(); });
            };
            next();
        } else {
            resolve();
        }
    });

    // The first sentence alone, so she starts talking quickly while the rest is being made.
    const parts = (words) => {
        const m = words.match(/^[\s\S]{12,200}?[.!؟?\n،,]/u);
        if (!m || words.length - m[0].length < 15) return [words];
        return [m[0].trim(), words.slice(m[0].length).trim()];
    };

    // One part from the site: its audio, or (no natural voice) the text written for the browser's voice.
    const fetchPart = async (piece, short) => {
        try {
            const body = new FormData();
            body.append('text', piece); body.append('lang', short); body.append('_token', config.csrf());
            const response = await fetch(config.speechUrl, {method: 'POST', body, credentials: 'same-origin', headers: {Accept: 'audio/*'}});
            if (response.ok) return {url: URL.createObjectURL(await response.blob())};
            if (response.status === 503) return {spoken: (await response.json().catch(() => ({}))).spoken || piece};
        } catch (e) {}
        return {spoken: piece};
    };

    const playUrl = (url, myTurn) => new Promise((resolve) => {
        if (myTurn !== turn) { URL.revokeObjectURL(url); resolve(); return; }
        audio = new Audio(url);
        audio.onplaying = started;
        audio.onended = audio.onerror = () => { URL.revokeObjectURL(url); audio = null; resolve(); };
        audio.play().catch(() => { URL.revokeObjectURL(url); audio = null; resolve(); });
    });

    const speak = async (text, lang) => {
        stopSpeaking();
        const words = plain(text);
        if (!words) return;
        const myTurn = turn;
        busy = true;
        face('thinking');
        const short = String(lang || 'ar').slice(0, 2);
        // Both parts are asked for at once; the first plays as soon as it's ready.
        const jobs = parts(words).map((piece) => fetchPart(piece, short));
        for (const job of jobs) {
            const result = await job;
            if (myTurn !== turn) { if (result.url) URL.revokeObjectURL(result.url); return; }
            if (result.url) await playUrl(result.url, myTurn);
            else await speakLocally(result.spoken, lang, myTurn);
            if (myTurn !== turn) return;
        }
        finished(myTurn);
    };

    /** Listens until the person has finished talking: the browser stops at the first short breath, so it is started
        again and the pieces are joined; it ends after ~2.8s of silence, or when stop() is called (a tap on the mic).
        onText gets the words as they come; onEnd the final text. Returns a stop function. */
    const listen = ({lang, onText, onEnd, onError}) => {
        stopSpeaking();
        let said = '', current = '', finished = false, stopAsked = false, recognition = null;
        let lastHeard = Date.now();
        const began = Date.now();
        const all = () => [said, current].filter((piece) => piece.trim()).join(' ').trim();
        face('listening');
        const finish = () => {
            if (finished) return;
            finished = true;
            clearInterval(watch);
            try { recognition?.abort(); } catch (e) {}
            const text = all();
            face(text ? 'thinking' : 'idle');
            onEnd?.(text);
        };
        const round = () => {
            if (finished) return;
            current = '';
            recognition = new Recognition();
            recognition.lang = speechLang(lang);
            recognition.interimResults = true;
            recognition.continuous = false;
            recognition.maxAlternatives = 1;
            recognition.onresult = (event) => {
                const heard = Array.from(event.results).map((r) => r[0].transcript).join(' ').trim();
                if (heard !== current) lastHeard = Date.now();
                current = heard;
                onText?.(all(), false);
            };
            recognition.onerror = (event) => {
                // "no-speech" / "aborted" are just pauses; anything else (no permission, no mic) ends listening.
                if (!['no-speech', 'aborted'].includes(event.error)) { onError?.(event.error); finish(); }
            };
            recognition.onend = () => {
                if (finished) return;
                if (current.trim()) { said = all(); current = ''; }
                if (stopAsked) finish(); else setTimeout(round, 120);
            };
            try { recognition.start(); } catch (e) { finish(); }
        };
        const watch = setInterval(() => {
            const quiet = Date.now() - lastHeard;
            if (Date.now() - began > 90000 || (all() && quiet > 2800) || (!all() && quiet > 8000)) finish();
        }, 250);
        round();
        return () => { stopAsked = true; try { recognition?.stop(); } catch (e) {} };
    };

    window.speechSynthesis?.getVoices(); // loads the voice list early in Chrome
    window.OzmanVoice = {
        canListen: !!Recognition, listen, speak, stopSpeaking,
        /** True from asking for a reading until it ends. */
        busy: () => busy,
        configure(options) { Object.assign(config, options || {}); },
    };
})();
