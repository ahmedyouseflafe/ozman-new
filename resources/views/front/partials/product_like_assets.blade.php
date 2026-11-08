{{-- Styles + script for product hearts and share buttons. Included once by product_like / page_share, or directly by pages that build cards in JS. --}}
@php($shareCopy = match (app()->getLocale()) {
    'he' => ['title' => 'שיתוף', 'whatsapp' => 'וואטסאפ', 'facebook' => 'פייסבוק', 'telegram' => 'טלגרם', 'x' => 'X', 'copy' => 'העתקת קישור', 'copied' => 'הקישור הועתק ✓', 'more' => 'אפליקציות נוספות', 'close' => 'סגירה'],
    'en' => ['title' => 'Share', 'whatsapp' => 'WhatsApp', 'facebook' => 'Facebook', 'telegram' => 'Telegram', 'x' => 'X', 'copy' => 'Copy link', 'copied' => 'Link copied ✓', 'more' => 'Other apps', 'close' => 'Close'],
    default => ['title' => 'مشاركة', 'whatsapp' => 'واتساب', 'facebook' => 'فيسبوك', 'telegram' => 'تلغرام', 'x' => 'X', 'copy' => 'نسخ الرابط', 'copied' => 'تم نسخ الرابط ✓', 'more' => 'تطبيقات ثانية', 'close' => 'إغلاق'],
})
<style>
.oz-actions{position:absolute;top:10px;inset-inline-end:10px;z-index:6;display:flex;flex-direction:column;align-items:center;gap:6px}
.oz-actions[data-side=right]{inset-inline-start:auto;inset-inline-end:auto;left:auto;right:10px}
.oz-actions[data-side=left]{inset-inline-start:auto;inset-inline-end:auto;right:auto;left:10px}
.oz-actions--bubble{inset-inline-start:auto;inset-inline-end:auto;left:auto;right:-8px;top:-10px;z-index:7}
.oz-actions--inline{position:static;flex-direction:row;align-items:center;flex:none}
.oz-like,.oz-share{position:static;display:inline-flex;align-items:center;justify-content:center;gap:4px;height:34px;min-width:34px;padding:0 8px;margin:0;border:1px solid rgba(255,255,255,.22);border-radius:999px;background:rgba(14,14,20,.58);-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px);color:#fff;font-family:inherit;font-size:13px;font-weight:800;line-height:1;letter-spacing:0;text-transform:none;cursor:pointer;box-shadow:0 4px 14px rgba(0,0,0,.25);-webkit-tap-highlight-color:transparent;transition:background .2s,transform .15s}
.oz-like:active,.oz-share:active{transform:scale(.92)}
.oz-like:focus-visible,.oz-share:focus-visible,.oz-page-share:focus-visible{outline:2px solid #ff6b86;outline-offset:2px}
.oz-like svg{width:18px;height:18px;flex:none;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;transition:fill .2s,stroke .2s}
.oz-like.is-liked{background:rgba(255,255,255,.95);color:#e11d48;border-color:transparent}
.oz-like.is-liked svg{fill:#ff2d55;stroke:#ff2d55}
.oz-like.pop svg{animation:ozLikePop .45s ease}
.oz-like-count:empty{display:none}
.oz-actions--inline .oz-like,.oz-actions--inline .oz-share{background:rgba(127,127,127,.12);color:inherit;box-shadow:none;border-color:rgba(127,127,127,.25);-webkit-backdrop-filter:none;backdrop-filter:none}
.oz-share{flex-direction:column;gap:2px;height:auto;min-width:50px;padding:6px 6px 5px;border-radius:14px}
.oz-share-arrow{width:24px;height:24px;flex:none;fill:currentColor;filter:drop-shadow(0 1px 2px rgba(0,0,0,.35))}
.oz-share-label{font-size:10.5px;font-weight:800;line-height:1.1;white-space:nowrap}
.oz-actions--inline .oz-share{border:0;background:transparent;padding:2px 4px;min-width:44px}
.oz-actions--inline .oz-share-arrow{filter:none}
.oz-page-share{position:fixed;z-index:9990;inset-inline-start:12px;bottom:calc(96px + env(safe-area-inset-bottom,0px));display:flex;flex-direction:column;align-items:center;gap:4px;padding:0;border:0;background:transparent;color:#fff;font-family:inherit;cursor:pointer;-webkit-tap-highlight-color:transparent}
.oz-page-share-icon{display:grid;place-items:center;width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#25d366,#128c7e);border:2px solid rgba(255,255,255,.35);box-shadow:0 10px 26px rgba(0,0,0,.35)}
.oz-page-share-icon .oz-share-arrow{width:26px;height:26px}
.oz-page-share .oz-share-label{padding:3px 8px;border-radius:999px;background:rgba(10,10,14,.72);font-size:11.5px}
.oz-page-share:active .oz-page-share-icon{transform:scale(.92)}
.oz-actions--inline .oz-like.is-liked{background:rgba(255,45,85,.12);color:#e11d48}
.oz-share-sheet{position:fixed;inset:0;z-index:2147483646;display:flex;align-items:flex-end;justify-content:center;background:rgba(0,0,0,.5);font-family:inherit;opacity:0;transition:opacity .2s}
.oz-share-sheet.is-open{opacity:1}
.oz-share-card{width:min(440px,100%);margin:0 10px calc(10px + env(safe-area-inset-bottom,0px));padding:18px 16px 14px;border-radius:24px;background:#fff;color:#1d1b22;box-shadow:0 24px 70px rgba(0,0,0,.4);transform:translateY(30px);transition:transform .25s ease}
.oz-share-sheet.is-open .oz-share-card{transform:none}
.oz-share-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px}
.oz-share-head b{font-size:17px}
.oz-share-head small{display:block;color:#6b6475;font-size:13px;font-weight:600;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:300px}
.oz-share-close{width:34px;height:34px;border:0;border-radius:50%;background:#f1eff4;color:#1d1b22;font-size:20px;line-height:1;cursor:pointer}
.oz-share-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.oz-share-grid a,.oz-share-grid button{display:flex;flex-direction:column;align-items:center;gap:7px;padding:12px 4px;border:0;border-radius:16px;background:#f6f4f8;color:#1d1b22;font-family:inherit;font-size:13px;font-weight:700;text-decoration:none;cursor:pointer}
.oz-share-grid i{display:grid;place-items:center;width:44px;height:44px;border-radius:50%;color:#fff;font-style:normal;font-weight:900;font-size:19px}
.oz-share-grid svg{width:22px;height:22px;fill:currentColor}
.oz-share-highlight{animation:ozShareGlow 2.6s ease}
@keyframes ozShareGlow{0%,70%{box-shadow:0 0 0 4px #25d366,0 0 30px #25d366}100%{box-shadow:none}}
@keyframes ozLikePop{0%{transform:scale(1)}35%{transform:scale(1.4)}100%{transform:scale(1)}}
@media (prefers-reduced-motion:reduce){.oz-like.pop svg{animation:none}.oz-share-highlight{animation:none}}
</style>
<script>
window.OZMAN_LIKES = {stateUrl: @json(route('product-likes.state')), toggleUrl: @json(route('product-likes.toggle', ['product' => '__ID__'])), csrf: @json(csrf_token()), shareUrl: @json(route('share.product', ['product' => '__ID__'])), share: @json($shareCopy)};
</script>
{{-- Inline so the buttons work even when a separate file is cached or missing on the server. --}}
<script>{!! file_get_contents(public_path('product-likes.js')) !!}</script>
