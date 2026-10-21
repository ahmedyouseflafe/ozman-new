<button type="button" id="eleganceReshareOrder" hidden>مشاركة آخر طلب بالصور</button>
<dialog id="eleganceOrderShareDialog" class="cart-sheet beauty-viewport-modal" aria-labelledby="eleganceOrderShareTitle" aria-hidden="true">
    <section class="elegance-share-card">
        <header><h2 id="eleganceOrderShareTitle">مشاركة الطلب بالصور</h2><button type="button" id="eleganceShareClose" aria-label="إغلاق" autofocus>×</button></header>
        <p>اختار واتساب من قائمة المشاركة، ثم اختار محادثة {{ $shop->name }} وأرسل الصور.</p>
        <p id="eleganceShareStatus" role="status" aria-live="polite"></p>
        <div id="eleganceShareImages"></div>
        <button type="button" id="eleganceSharePhotos" disabled>تجهيز الصور…</button>
        <button type="button" id="eleganceShareRetry" hidden>إعادة تحميل الصور</button>
        <button type="button" id="eleganceShareCopy" disabled>نسخ تفاصيل الطلب ورابط المحل</button>
        <p class="elegance-share-note">إذا ما ظهر نص الطلب مع الصور في واتساب، انسخ التفاصيل وأرسلها بنفس المحادثة.</p>
        <details><summary>خيارات إرسال أخرى</summary>
            <p>يمكنك حفظ الصور من القائمة وإرفاقها يدويًا في واتساب.</p>
            <a id="eleganceShareText" hidden target="_blank" rel="noopener noreferrer">فتح واتساب بتفاصيل الطلب فقط</a>
            <textarea id="eleganceShareMessage" readonly aria-label="تفاصيل الطلب للنسخ" rows="6"></textarea>
        </details>
        <a href="{{ $shop->publicUrl() }}">رجوع للتسوّق</a>
    </section>
</dialog>
<style>
#eleganceReshareOrder{width:100%;min-height:44px;margin-bottom:12px;border:1px solid #ff86bb80;border-radius:12px;background:#302035;color:#fff;font:inherit;cursor:pointer}
#eleganceOrderShareDialog[hidden],#eleganceOrderShareDialog [hidden],#eleganceReshareOrder[hidden]{display:none!important}
.elegance-share-card{width:min(520px,100%);min-width:0;max-height:100%;overflow:auto;overscroll-behavior:contain;padding:18px;border:1px solid #ff86bb80;border-radius:22px;background:#160d19;overflow-wrap:anywhere}
.elegance-share-card header{display:flex;justify-content:space-between;align-items:center;gap:12px}.elegance-share-card h2{margin:0;font-size:20px}
.elegance-share-card button{display:block;width:100%;min-height:46px;margin:12px 0;padding:10px;border:1px solid #ff86bb80;border-radius:12px;background:#302035;color:#fff;font:inherit;cursor:pointer}
.elegance-share-card header button{flex:none;width:44px;margin:0;border-radius:50%;font-size:24px}
.elegance-share-card button:disabled{opacity:.5;cursor:wait}#eleganceSharePhotos{background:#ff86bb;color:#210615;font-weight:bold}
.elegance-share-card p,.elegance-share-card summary,.elegance-share-card a{font-size:13px;line-height:1.8}.elegance-share-card a{color:#ffb8d7}
.elegance-share-card textarea{width:100%;max-width:100%;margin:10px 0;font:inherit;font-size:16px;color:#fff;background:#110b15;border:1px solid #51404f;border-radius:12px;padding:10px}
#eleganceShareImages{display:grid;gap:8px}#eleganceShareImages a{display:flex;align-items:center;gap:12px;padding:8px;border:1px solid #51404f;border-radius:12px}
#eleganceShareImages img{width:64px;height:72px;object-fit:contain;background:#fff;border-radius:8px;flex:none}.elegance-share-note{color:#c4b5c6}
</style>
<script>{!! file_get_contents(base_path('public/elegance-order-share.js')) !!}</script>
