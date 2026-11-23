{{-- Styles + script for product hearts and share buttons. Included once by product_like / page_share, or directly by pages that build cards in JS. --}}
@php($shareCopy = match (app()->getLocale()) {
    'he' => ['title' => 'שיתוף', 'whatsapp' => 'וואטסאפ', 'facebook' => 'פייסבוק', 'telegram' => 'טלגרם', 'x' => 'X', 'copy' => 'העתקת קישור', 'copied' => 'הקישור הועתק ✓', 'more' => 'אפליקציות נוספות', 'close' => 'סגירה'],
    'en' => ['title' => 'Share', 'whatsapp' => 'WhatsApp', 'facebook' => 'Facebook', 'telegram' => 'Telegram', 'x' => 'X', 'copy' => 'Copy link', 'copied' => 'Link copied ✓', 'more' => 'Other apps', 'close' => 'Close'],
    default => ['title' => 'مشاركة', 'whatsapp' => 'واتساب', 'facebook' => 'فيسبوك', 'telegram' => 'تلغرام', 'x' => 'X', 'copy' => 'نسخ الرابط', 'copied' => 'تم نسخ الرابط ✓', 'more' => 'تطبيقات ثانية', 'close' => 'إغلاق'],
})
@viewStyle('front.partials.product_like_assets')
<script>
window.OZMAN_LIKES = {stateUrl: @json(route('product-likes.state')), toggleUrl: @json(route('product-likes.toggle', ['product' => '__ID__'])), csrf: @json(csrf_token()), shareUrl: @json(route('share.product', ['product' => '__ID__'])), share: @json($shareCopy)};
</script>
{{-- Inline so the buttons work even when a separate file is cached or missing on the server. --}}
<script>{!! file_get_contents(public_path('product-likes.js')) !!}</script>
