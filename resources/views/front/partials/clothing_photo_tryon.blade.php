@php($watchTryOn = $watchTryOn ?? false)
<div class="overlay try-on-overlay" id="tryOnLayer" role="dialog" aria-modal="true" aria-labelledby="photoTryTitle" data-kind="{{ $watchTryOn ? 'watch' : 'clothing' }}"
     data-endpoint="{{ route($watchTryOn ? 'watch.photo-tryon' : 'clothing.photo-tryon', $shop) }}" data-enabled="{{ \App\Http\Controllers\ClothingPhotoTryOnController::enabled($shop) ? '1' : '0' }}" data-csrf="{{ csrf_token() }}">
    <section class="photo-try-card">
        <header><div><small>OZMAN · PHOTO TRY-ON</small><h2 id="photoTryTitle">{{ $watchTryOn ? 'شوف الساعة على إيدك' : 'شوف القطعة عليك بصورة' }}</h2></div><button class="x" type="button" data-close="tryOnLayer" aria-label="{{ __('إغلاق') }}">×</button></header>
        @if($watchTryOn)
        <p>{{ __('صوّر معصمك بدون ساعة بإضاءة واضحة، أو ارفع صورته من جهازك.') }}</p>
        <details id="photoTryExample"><summary>{{ __('نصائح لصورة واضحة') }}</summary><p>{{ __('خلي المعصم قريب وواضح، وصوّر بإضاءة متساوية بدون فلاتر. المعاينة مولّدة وقد تختلف التفاصيل، ولا تحدد قياس الساعة أو السوار.') }}</p></details>
        <label class="photo-upload" for="photoTryWatchSelect">{{ __('اختار الساعة') }}
            <select id="photoTryWatchSelect">
                @foreach($watchProducts as $watchProduct)
                    <option value="{{ $watchProduct->id }}" data-image="{{ $mediaUrl($watchProduct->main_image) }}">{{ $watchProduct->localized('name') }}</option>
                @endforeach
            </select>
        </label>
        @else
        <p>{{ __('خليك وحدك داخل الصورة، مقابل الكاميرا بمستوى الصدر، وأظهر رأسك كاملًا لحد الورك. ابعد إيديك شوي عن جسمك وخلي الإضاءة قدامك. المعاينة بنفس اتجاه الصورة المحفوظة.') }}</p>
        <details id="photoTryExample" open><summary>{{ __('شوف نموذج توضيحي للفكرة') }}</summary><img src="{{ route('virtual-tryon.photo-demo') }}" alt="{{ __('نموذج مولّد: قبل بالرمادي يسارًا وبعد بالتيشيرت العاجي يمينًا') }}"><p>{{ __('مثال مولّد لتوضيح الفكرة؛ ليس نتيجة صورتك أو اختبارًا لخدمة FASHN.') }}</p></details>
        @endif
        <div class="photo-try-stage" id="photoTryStage" hidden>
            <video id="photoTryVideo" autoplay playsinline muted></video>
            <img id="photoTryCapture" alt="{{ __('صورتك الملتقطة') }}" hidden>
            <output id="photoTryCountdown" aria-live="assertive" hidden></output>
        </div>
        <div class="photo-try-garment"><img id="photoTryGarment" alt="{{ __('القطعة المختارة') }}"><span id="photoTryProductName" dir="auto">{{ __('القطعة اللي رح تجربها') }}</span></div>
        <div @if($watchTryOn) hidden @endif>
        <label class="photo-upload">{{ __('صورة بلوزة حقيقية من جهازك') }} <input type="file" id="photoTryGarmentFile" accept="image/jpeg,image/png,image/webp"></label>
        <p>{{ __('ارفع صورة البلوزة كاملة من الأمام، بدون قص الأكمام أو الحافة. للتفاصيل الأفضل استخدم إضاءة متساوية وخفف الثنيات قبل التصوير.') }}</p>
        <button type="button" id="photoTryOriginalGarment" hidden>{{ __('رجوع للقطعة الأصلية') }}</button>
        </div>
        <label class="photo-upload">{{ $watchTryOn ? 'أو ارفع صورة معصمك بدل الكاميرا' : 'أو ارفع صورتك بدل الكاميرا' }} <input type="file" id="photoTryPersonFile" accept="image/jpeg,image/png,image/webp"></label>
        @if($watchTryOn)
        <input type="hidden" id="photoTryQuality" value="detail">
        <p>{{ __('رح نستخدم صورة الساعة المختارة لتوليد معاينة على صورة معصمك.') }}</p>
        @else
        <label class="photo-upload">{{ __('جودة التجربة') }} <select id="photoTryQuality"><option value="standard">{{ __('عادية · رصيد واحد للصورة') }}</option><option value="detail">{{ __('تفاصيل أعلى · Try-On Max 2K · 4 أرصدة للصورة') }}</option></select></label>
        @endif
        <p id="photoTryStatus" role="status">{{ __('افتح الكاميرا، ثم اضغط التقاط بعد 5 ثوانٍ.') }}</p>
        <div class="photo-try-actions">
            <button type="button" id="photoTryCamera">{{ __('فتح الكاميرا') }}</button>
            <button type="button" id="photoTryShoot" hidden>{{ __('التقاط بعد 5 ثوانٍ') }}</button>
            <button type="button" id="photoTryCancel" hidden>{{ __('إلغاء العدّ') }}</button>
            <button type="button" id="photoTryRetake" hidden>{{ __('إعادة التصوير') }}</button>
        </div>
        @if(\App\Http\Controllers\ClothingPhotoTryOnController::enabled($shop))
            <label class="photo-try-consent"><input type="checkbox" id="photoTryConsent"> {{ __('أوافق على إرسال صورتي وصورة القطعة إلى FASHN لتوليد المعاينة.') }}</label>
        @else
            <p class="photo-try-notice">{{ __('التقاط الصورة والنموذج جاهزان. تجربة القطعة على صورتك تحتاج تفعيل خدمة الصور لدى المحل.') }}</p>
        @endif
        <figure id="photoTryResult" hidden><figcaption>{{ __('نتيجتك · معاينة مولّدة، لا تحدد المقاس الفعلي') }}</figcaption><img id="photoTryResultImage" alt="{{ __('نتيجة تجربة القطعة على صورتك') }}"></figure>
        <div class="photo-try-actions photo-try-submit"><button type="button" id="photoTryGenerate" disabled>{{ $watchTryOn ? 'جرّب الساعة على صورتي' : 'جرّب القطعة على صورتي' }}</button></div>
    </section>
</div>
@viewStyle('front.partials.clothing_photo_tryon')
<style>
.photo-try-card header{position:sticky;top:-20px;z-index:5;background:#18181b;padding-block:12px;border-bottom:1px solid #ffffff25}
.photo-try-card header .x{flex-shrink:0;min-width:40px;min-height:40px}
</style>
<style>.photo-upload{display:grid;gap:8px;margin:14px 0}.photo-upload input,.photo-upload select{width:100%;min-width:0;padding:10px;border:1px solid #ffffff40;border-radius:10px;background:#252529;color:#f5f1ea;font:inherit}.photo-try-card button{font:inherit}#photoTryOriginalGarment{padding:8px;border-radius:8px;background:#252529;color:#d6ff38;border:1px solid #d6ff3850}</style>
