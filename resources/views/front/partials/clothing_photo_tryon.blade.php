@php($watchTryOn = $watchTryOn ?? false)
<div class="overlay try-on-overlay" id="tryOnLayer" role="dialog" aria-modal="true" aria-labelledby="photoTryTitle" data-kind="{{ $watchTryOn ? 'watch' : 'clothing' }}"
     data-endpoint="{{ route($watchTryOn ? 'watch.photo-tryon' : 'clothing.photo-tryon', $shop) }}" data-enabled="{{ \App\Http\Controllers\ClothingPhotoTryOnController::enabled($shop) ? '1' : '0' }}" data-csrf="{{ csrf_token() }}">
    <section class="photo-try-card">
        <header><div><small>OZMAN · PHOTO TRY-ON</small><h2 id="photoTryTitle">شوف القطعة عليك بصورة</h2></div><button class="x" type="button" data-close="tryOnLayer" aria-label="إغلاق">×</button></header>
        @if($watchTryOn)
        <p>صوّر إيد واحدة بدون ساعة، وخلّي ظهر الإيد والمعصم وجزء من الساعد واضحين بإضاءة متساوية. ثبّت إيدك وخلي مكان الساعة مكشوف. الصورة بتظل بنفس اتجاهها.</p>
        <details id="photoTryExample"><summary>الساعة التجريبية</summary><img src="{{ route('virtual-tryon.demo-watch') }}" alt="ساعة تجريبية فضية بمينا أزرق" style="max-height:260px;object-fit:contain"><p>تصميم تجريبي مولّد للتجربة، وليس منتجًا معروضًا للبيع. النتيجة صورة ثابتة ولا تحدد قياس الساعة أو السوار.</p></details>
        @else
        <p>خليك وحدك داخل الصورة، مقابل الكاميرا بمستوى الصدر، وأظهر رأسك كاملًا لحد الورك. ابعد إيديك شوي عن جسمك وخلي الإضاءة قدامك. المعاينة بنفس اتجاه الصورة المحفوظة.</p>
        <details id="photoTryExample" open><summary>شوف نموذج توضيحي للفكرة</summary><img src="{{ route('virtual-tryon.photo-demo') }}" alt="نموذج مولّد: قبل بالرمادي يسارًا وبعد بالتيشيرت العاجي يمينًا"><p>مثال مولّد لتوضيح الفكرة؛ ليس نتيجة صورتك أو اختبارًا لخدمة FASHN.</p></details>
        @endif
        <div class="photo-try-stage" id="photoTryStage" hidden>
            <video id="photoTryVideo" autoplay playsinline muted></video>
            <img id="photoTryCapture" alt="صورتك الملتقطة" hidden>
            <output id="photoTryCountdown" aria-live="assertive" hidden></output>
        </div>
        <div class="photo-try-garment"><img id="photoTryGarment" alt="القطعة المختارة"><span>القطعة اللي رح تجربها</span></div>
        <div @if($watchTryOn) hidden @endif>
        <label class="photo-upload">صورة بلوزة حقيقية من جهازك <input type="file" id="photoTryGarmentFile" accept="image/jpeg,image/png,image/webp"></label>
        <p>ارفع صورة البلوزة كاملة من الأمام، بدون قص الأكمام أو الحافة. للتفاصيل الأفضل استخدم إضاءة متساوية وخفف الثنيات قبل التصوير.</p>
        <button type="button" id="photoTryOriginalGarment" hidden>رجوع للقطعة الأصلية</button>
        </div>
        <label class="photo-upload">{{ $watchTryOn ? 'أو ارفع صورة معصمك بدل الكاميرا' : 'أو ارفع صورتك بدل الكاميرا' }} <input type="file" id="photoTryPersonFile" accept="image/jpeg,image/png,image/webp"></label>
        @if($watchTryOn)
        <input type="hidden" id="photoTryQuality" value="detail">
        <p>التوليد بدقة 2K · كل نتيجة تستهلك 4 أرصدة من حساب المحل في FASHN. التصوير وحده لا يستهلك رصيدًا.</p>
        @else
        <label class="photo-upload">جودة التجربة <select id="photoTryQuality"><option value="standard">عادية · رصيد واحد للصورة</option><option value="detail">تفاصيل أعلى · Try-On Max 2K · 4 أرصدة للصورة</option></select></label>
        @endif
        <p id="photoTryStatus" role="status">افتح الكاميرا، ثم اضغط التقاط بعد 5 ثوانٍ.</p>
        <div class="photo-try-actions">
            <button type="button" id="photoTryCamera">فتح الكاميرا</button>
            <button type="button" id="photoTryShoot" hidden>التقاط بعد 5 ثوانٍ</button>
            <button type="button" id="photoTryCancel" hidden>إلغاء العدّ</button>
            <button type="button" id="photoTryRetake" hidden>إعادة التصوير</button>
        </div>
        @if(\App\Http\Controllers\ClothingPhotoTryOnController::enabled($shop))
            <label class="photo-try-consent"><input type="checkbox" id="photoTryConsent"> أوافق على إرسال صورتي وصورة القطعة إلى FASHN لتوليد المعاينة.</label>
        @else
            <p class="photo-try-notice">التقاط الصورة والنموذج جاهزان. تجربة القطعة على صورتك تحتاج تفعيل خدمة الصور لدى المحل.</p>
        @endif
        <div class="photo-try-actions"><button type="button" id="photoTryGenerate" disabled>جرّب القطعة على صورتي</button></div>
        <figure id="photoTryResult" hidden><figcaption>نتيجتك · معاينة مولّدة، لا تحدد المقاس الفعلي</figcaption><img id="photoTryResultImage" alt="نتيجة تجربة القطعة على صورتك"></figure>
    </section>
</div>
<style>
.photo-try-card{width:min(760px,100%);max-height:92dvh;overflow:auto;border:1px solid #d6ff3880;border-radius:24px;background:#18181b;color:#f5f1ea;padding:20px}.photo-try-card header{display:flex;align-items:center;justify-content:space-between;gap:12px}.photo-try-card h2{font-size:22px;margin:5px 0}.photo-try-card small{color:#d6ff38;font-size:10px}.photo-try-card p,.photo-try-card label,.photo-try-card figcaption{font-size:12px;line-height:1.8;color:#c8c5cc}.photo-try-card details{margin:15px 0;border:1px solid #ffffff25;border-radius:14px;padding:10px}.photo-try-card summary{cursor:pointer;font-size:13px}.photo-try-card details img{display:block;width:100%;border-radius:10px;margin-top:10px}.photo-try-stage{position:relative;background:#080809;border-radius:16px;overflow:hidden;min-height:240px}.photo-try-stage video,.photo-try-stage>img{display:block;width:100%;max-height:52dvh;object-fit:contain}.photo-try-stage video,.photo-try-stage>img,.photo-try-card figure img{transform:none}.photo-try-stage output{position:absolute;inset:0;display:grid;place-items:center;background:#0004;color:#d6ff38;font-size:100px;font-weight:900}.photo-try-card [hidden]{display:none!important}.photo-try-actions{display:flex;gap:10px;flex-wrap:wrap;margin:12px 0}.photo-try-actions button{padding:12px 18px;min-height:46px;border-radius:12px;border:1px solid #d6ff3880;background:#d6ff38;color:#141415;font-weight:800}.photo-try-actions button:disabled{opacity:.45;cursor:not-allowed}.photo-try-garment{display:flex;align-items:center;gap:12px;font-size:12px;margin-top:12px}.photo-try-garment img{width:64px;height:70px;object-fit:contain;background:#ece9e2;border-radius:10px}.photo-try-consent{display:flex;align-items:start;gap:8px}.photo-try-consent input{margin-top:7px;accent-color:#d6ff38}.photo-try-notice{padding:10px;background:#d6ff3810;border-radius:10px}.photo-try-card figure{margin:15px 0}.photo-try-card figure img{display:block;width:100%;border-radius:14px}.photo-try-card .x{color:#fff;border-color:#ffffff40}.photo-try-card button:focus-visible,.photo-try-card summary:focus-visible{outline:3px solid #c0a7ff;outline-offset:3px}
</style>
<style>
.photo-try-card header{position:sticky;top:-20px;z-index:5;background:#18181b;padding-block:12px;border-bottom:1px solid #ffffff25}
.photo-try-card header .x{flex-shrink:0;min-width:40px;min-height:40px}
</style>
<style>.photo-upload{display:grid;gap:8px;margin:14px 0}.photo-upload input,.photo-upload select{width:100%;min-width:0;padding:10px;border:1px solid #ffffff40;border-radius:10px;background:#252529;color:#f5f1ea;font:inherit}.photo-try-card button{font:inherit}#photoTryOriginalGarment{padding:8px;border-radius:8px;background:#252529;color:#d6ff38;border:1px solid #d6ff3850}</style>
