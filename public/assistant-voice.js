/* Voice for the assistants on the website: talk instead of typing (the browser's own speech recognition), and hear
   the answer: a natural voice from the site (Azure) when it's set up, otherwise the browser's voices, otherwise the
   site's Google reader. Also drives the assistant's face (window.OzmanAvatar): listening, thinking, speaking, idle. */
(() => {
    if (window.OzmanVoice) return;
    const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const speechLang = (lang) => ({ar: 'ar-JO', he: 'he-IL', en: 'en-US'}[String(lang || 'ar').slice(0, 2)] || 'ar-JO');
    const ttsUrl = {ar: '/tts/arabic', he: '/tts/hebrew'};
    const config = {speechUrl: '/assistant-speech', csrf: () => document.querySelector('meta[name=csrf-token]')?.content || ''};
    let audio = null, queue = [], natural = true, turn = 0, busy = false;

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

    const playNext = (lang, myTurn) => {
        const piece = queue.shift();
        if (!piece) { audio = null; finished(myTurn); return; }
        audio = new Audio(`${ttsUrl[lang]}?text=${encodeURIComponent(piece)}`);
        audio.onplaying = started;
        audio.onended = () => playNext(lang, myTurn);
        audio.onerror = () => { queue = []; audio = null; finished(myTurn); };
        audio.play().catch(() => { queue = []; audio = null; finished(myTurn); });
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
    const speakLocally = (words, lang, myTurn) => {
        const code = speechLang(lang), short = code.slice(0, 2);
        const voice = (window.speechSynthesis?.getVoices() || []).find((v) => v.lang?.toLowerCase().startsWith(short));
        if (voice) {
            const utterance = new SpeechSynthesisUtterance(words);
            utterance.voice = voice; utterance.lang = voice.lang; utterance.rate = 1;
            utterance.onstart = started;
            utterance.onend = utterance.onerror = () => finished(myTurn);
            window.speechSynthesis.speak(utterance);
        } else if (ttsUrl[short]) {
            queue = pieces(words);
            playNext(short, myTurn);
        } else {
            finished(myTurn);
        }
    };

    const speak = async (text, lang) => {
        stopSpeaking();
        const words = plain(text);
        if (!words) return;
        const myTurn = turn;
        busy = true;
        face('thinking');
        const short = String(lang || 'ar').slice(0, 2);
        if (natural) {
            try {
                const body = new FormData();
                body.append('text', words); body.append('lang', short); body.append('_token', config.csrf());
                const response = await fetch(config.speechUrl, {method: 'POST', body, credentials: 'same-origin', headers: {Accept: 'audio/mpeg'}});
                if (myTurn !== turn) return;
                if (response.status === 503) natural = false; // not set up: the browser's voice from now on
                if (response.ok) {
                    const url = URL.createObjectURL(await response.blob());
                    if (myTurn !== turn) return;
                    audio = new Audio(url);
                    audio.onplaying = started;
                    audio.onended = audio.onerror = () => { URL.revokeObjectURL(url); audio = null; finished(myTurn); };
                    await audio.play();
                    return;
                }
            } catch (e) {
                if (myTurn !== turn) return;
            }
        }
        speakLocally(words, lang, myTurn);
    };

    /** Listens once; calls onText with what was said (interim results as they come, final=true at the end). Returns a stop function. */
    const listen = ({lang, onText, onEnd, onError}) => {
        stopSpeaking();
        const recognition = new Recognition();
        recognition.lang = speechLang(lang);
        recognition.interimResults = true;
        recognition.continuous = false;
        recognition.maxAlternatives = 1;
        let text = '';
        face('listening');
        recognition.onresult = (event) => {
            text = Array.from(event.results).map((r) => r[0].transcript).join(' ').trim();
            onText?.(text, event.results[event.results.length - 1].isFinal);
        };
        recognition.onerror = (event) => onError?.(event.error);
        recognition.onend = () => { face(text ? 'thinking' : 'idle'); onEnd?.(text); };
        recognition.start();
        return () => { try { recognition.stop(); } catch (e) {} };
    };

    window.speechSynthesis?.getVoices(); // loads the voice list early in Chrome
    window.OzmanVoice = {
        canListen: !!Recognition, listen, speak, stopSpeaking,
        /** True from asking for a reading until it ends. */
        busy: () => busy,
        configure(options) { Object.assign(config, options || {}); },
    };
})();
