{{-- The assistants' face: an illustrated girl who blinks while waiting, glows while listening, looks up while thinking
     and moves her mouth while she talks. One drawing for every size; window.OzmanAvatar.set(state) changes all of them. --}}
@php($avSize = $size ?? 48)
<span class="oz-av {{ $class ?? '' }}" data-state="idle" style="--av:{{ $avSize }}px" aria-hidden="true">
    <svg viewBox="0 0 120 120" focusable="false">
        <defs>
            <radialGradient id="ozAvBg" cx="50%" cy="38%" r="70%"><stop offset="0" stop-color="#5ff0ff"/><stop offset="1" stop-color="#0b8fb5"/></radialGradient>
            <linearGradient id="ozAvHair" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#4a2f27"/><stop offset="1" stop-color="#2a1a15"/></linearGradient>
            <clipPath id="ozAvClip"><circle cx="60" cy="60" r="58"/></clipPath>
        </defs>
                <g clip-path="url(#ozAvClip)">
            <circle cx="60" cy="60" r="58" fill="url(#ozAvBg)"/>
            <g class="oz-av-body">
                <path d="M26 56C26 30 42 17 60 17s34 13 34 39l3 40c-9 5-18 1-20-7H43c-2 8-11 12-20 7z" fill="url(#ozAvHair)"/>
                <rect x="52" y="76" width="16" height="20" rx="7" fill="#e9b996"/>
                <path d="M16 124c2-23 20-33 44-33s42 10 44 33z" fill="#16b5d6"/>
                <path d="M50 91l10 12 10-12c-3-1-6-1.5-10-1.5s-7 .5-10 1.5z" fill="#e9b996"/>
                <path d="M50 91l10 12 10-12" fill="none" stroke="#0e93b3" stroke-width="2" stroke-linejoin="round"/>
                <ellipse cx="60" cy="56" rx="24" ry="27" fill="#f3c9a8"/>
                <path d="M35 52c1-19 13-28 26-28 14 0 25 10 25 27-7-8-16-12-27-11-10 1-17 5-24 12z" fill="url(#ozAvHair)"/>
                <path d="M61 26c-6 6-9 13-9 20 7-7 15-11 26-10-4-6-10-9-17-10z" fill="#5a3a2f" opacity=".55"/>
                <g class="oz-av-face">
                    <path class="oz-av-brows" d="M44 49q5-3 10 0M66 49q5-3 10 0" stroke="#3a2520" stroke-width="2" fill="none" stroke-linecap="round"/>
                    <g class="oz-av-eyes">
                        <ellipse cx="49" cy="58" rx="4.2" ry="5.2" fill="#2b1b18"/><ellipse cx="71" cy="58" rx="4.2" ry="5.2" fill="#2b1b18"/>
                        <circle cx="50.6" cy="56.2" r="1.5" fill="#fff"/><circle cx="72.6" cy="56.2" r="1.5" fill="#fff"/>
                    </g>
                    <ellipse cx="43" cy="67" rx="4.5" ry="2.6" fill="#ff8fa3" opacity=".45"/><ellipse cx="77" cy="67" rx="4.5" ry="2.6" fill="#ff8fa3" opacity=".45"/>
                    <path d="M59 62q1 3 2.5 3" stroke="#d99a7c" stroke-width="1.6" fill="none" stroke-linecap="round"/>
                    <path class="oz-av-smile" d="M53 70q7 6 14 0" stroke="#b9485b" stroke-width="2.4" fill="none" stroke-linecap="round"/>
                    <g class="oz-av-mouth"><ellipse cx="60" cy="72.2" rx="6.5" ry="5" fill="#8e2b40"/><ellipse cx="60" cy="74.6" rx="3.5" ry="2" fill="#e0707f"/></g>
                </g>
            </g>
            <g class="oz-av-dots" fill="#fff"><circle cx="86" cy="24" r="3"/><circle cx="95" cy="19" r="3"/><circle cx="104" cy="15" r="3"/></g>
        </g>
    </svg>
</span>
@once
@viewStyle('front.partials.assistant_avatar')
<script>
window.OzmanAvatar = window.OzmanAvatar || {
    // idle | listening | thinking | speaking
    set(state) { document.querySelectorAll('.oz-av, .oz-av-stage').forEach((el) => { el.dataset.state = state || 'idle'; }); },
};
</script>
@endonce
