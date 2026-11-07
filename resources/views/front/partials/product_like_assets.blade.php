{{-- Styles + script for product hearts. Included once by product_like, or directly by pages that build cards in JS. --}}
<style>
.oz-like{position:absolute;top:10px;inset-inline-end:10px;z-index:6;display:inline-flex;align-items:center;justify-content:center;gap:4px;height:34px;min-width:34px;padding:0 8px;margin:0;border:1px solid rgba(255,255,255,.22);border-radius:999px;background:rgba(14,14,20,.58);-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px);color:#fff;font-family:inherit;font-size:13px;font-weight:800;line-height:1;letter-spacing:0;text-transform:none;cursor:pointer;box-shadow:0 4px 14px rgba(0,0,0,.25);-webkit-tap-highlight-color:transparent;transition:background .2s,transform .15s}
.oz-like[data-like-side=right]{inset-inline-start:auto;inset-inline-end:auto;left:auto;right:10px}
.oz-like[data-like-side=left]{inset-inline-start:auto;inset-inline-end:auto;right:auto;left:10px}
.oz-like.oz-like--bubble{inset-inline-start:auto;inset-inline-end:auto;left:auto;right:-8px;top:-10px;height:36px;min-width:36px;z-index:7}
.oz-like:active{transform:scale(.92)}
.oz-like:focus-visible{outline:2px solid #ff6b86;outline-offset:2px}
.oz-like svg{width:18px;height:18px;flex:none;fill:none;stroke:currentColor;stroke-width:2;stroke-linejoin:round;transition:fill .2s,stroke .2s}
.oz-like.is-liked{background:rgba(255,255,255,.95);color:#e11d48;border-color:transparent}
.oz-like.is-liked svg{fill:#ff2d55;stroke:#ff2d55}
.oz-like.pop svg{animation:ozLikePop .45s ease}
.oz-like-count:empty{display:none}
.oz-like[data-like-variant=inline]{position:static;background:rgba(127,127,127,.12);color:inherit;box-shadow:none;border-color:rgba(127,127,127,.25)}
.oz-like[data-like-variant=inline].is-liked{background:rgba(255,45,85,.12);color:#e11d48}
@keyframes ozLikePop{0%{transform:scale(1)}35%{transform:scale(1.4)}100%{transform:scale(1)}}
@media (prefers-reduced-motion:reduce){.oz-like.pop svg{animation:none}}
</style>
<script>
window.OZMAN_LIKES = {stateUrl: @json(route('product-likes.state')), toggleUrl: @json(route('product-likes.toggle', ['product' => '__ID__'])), csrf: @json(csrf_token())};
</script>
{{-- Inline so the heart works even when a separate file is cached or missing on the server. --}}
<script>{!! file_get_contents(public_path('product-likes.js')) !!}</script>
