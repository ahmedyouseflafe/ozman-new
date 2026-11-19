/* Voice for the assistants on the website: talk instead of typing (the browser's own speech recognition),
   and hear the answer (the browser's voices, or the site's Arabic/Hebrew reader when the browser has none). */
(() => {
    if (window.OzmanVoice) return;
    const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const speechLang = (lang) => ({ar: 'ar-JO', he: 'he-IL', en: 'en-US'}[String(lang || 'ar').slice(0, 2)] || 'ar-JO');
    const ttsUrl = {ar: '/tts/arabic', he: '/tts/hebrew'};
    let audio = null, queue = [];

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

    const playNext = (lang) => {
        const piece = queue.shift();
        if (!piece) { audio = null; return; }
        audio = new Audio(`${ttsUrl[lang]}?text=${encodeURIComponent(piece)}`);
        audio.onended = () => playNext(lang);
        audio.onerror = () => { queue = []; audio = null; };
        audio.play().catch(() => { queue = []; audio = null; });
    };

    const stopSpeaking = () => {
        queue = [];
        if (audio) { audio.pause(); audio = null; }
        try { window.speechSynthesis?.cancel(); } catch (e) {}
    };

    const speak = (text, lang) => {
        stopSpeaking();
        const words = plain(text);
        if (!words) return;
        const code = speechLang(lang), short = code.slice(0, 2);
        const voice = (window.speechSynthesis?.getVoices() || []).find((v) => v.lang?.toLowerCase().startsWith(short));
        if (voice) {
            const utterance = new SpeechSynthesisUtterance(words);
            utterance.voice = voice; utterance.lang = voice.lang; utterance.rate = 1;
            window.speechSynthesis.speak(utterance);
        } else if (ttsUrl[short]) {
            queue = pieces(words);
            playNext(short);
        }
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
        recognition.onresult = (event) => {
            text = Array.from(event.results).map((r) => r[0].transcript).join(' ').trim();
            onText?.(text, event.results[event.results.length - 1].isFinal);
        };
        recognition.onerror = (event) => onError?.(event.error);
        recognition.onend = () => onEnd?.(text);
        recognition.start();
        return () => { try { recognition.stop(); } catch (e) {} };
    };

    window.speechSynthesis?.getVoices(); // loads the voice list early in Chrome
    window.OzmanVoice = {canListen: !!Recognition, listen, speak, stopSpeaking};
})();
