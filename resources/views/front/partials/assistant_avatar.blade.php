{{-- The assistants' face: Fulla's portrait, which breathes, blinks, moves her mouth while she talks, tilts while thinking
     and glows while listening or talking. Three frames of the same photo; window.OzmanAvatar.set(state) changes all of them. --}}
@php($avSize = $size ?? 48)
<span class="oz-av {{ $class ?? '' }}" data-state="idle" style="--av:{{ $avSize }}px" aria-hidden="true">
    <span class="oz-av-photo">
        <img class="oz-av-f" src="{{ asset('images/fulla/fulla.webp') }}" alt="" width="{{ $avSize }}" height="{{ $avSize }}" decoding="async">
        <img class="oz-av-f oz-av-blink" src="{{ asset('images/fulla/fulla_blink.webp') }}" alt="" width="{{ $avSize }}" height="{{ $avSize }}" decoding="async">
        <img class="oz-av-f oz-av-talk" src="{{ asset('images/fulla/fulla_talk.webp') }}" alt="" width="{{ $avSize }}" height="{{ $avSize }}" decoding="async">
    </span>
    <span class="oz-av-dots"><i></i><i></i><i></i></span>
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
