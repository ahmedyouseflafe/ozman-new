@php
    $carCopy = [
        'loading'=>'بنجهّز السيارة الثلاثية الأبعاد…',
        'orbit'=>'اسحب لتلفّ حول السيارة، واستخدم + و − للتقريب. افتح الأجزاء من الأزرار.',
        'insideHint'=>'أنت داخل السيارة. اسحب لتطلع حولك، واختار «من برّا» للرجوع.',
        'loadError'=>'ما قدرنا نحمّل العرض الثلاثي. جرّب مرة ثانية أو افتح الصفحة بمتصفح يدعم WebGL.',
        'engine'=>'بنجهّز الكاميرا… وافق على إذن الكاميرا والحركة لما يطلبه الجوال.',
        'scan'=>'وجّه الكاميرا لأرض مستوية وتحرك شوي بهدوء حتى تظهر دائرة التثبيت.',
        'ready'=>'وجّه الدائرة للمكان المطلوب واضغط «ثبّت السيارة هون».',
        'place'=>'ثبّت السيارة هون',
        'placed'=>'السيارة ثابتة. لفّ حولها وافتح أبوابها وباقي أجزائها من الأزرار.',
        'move'=>'غيّر مكان السيارة',
        'lost'=>'التتبّع توقف مؤقتًا. وجّه الكاميرا للأرض وتحرك بهدوء.',
        'cameraError'=>'تعذّر تشغيل تجربة الكاميرا. رجعناك للعرض الثلاثي؛ تأكد من أذونات الكاميرا والحركة ودعم جهازك.',
        'desktop'=>'افتح الصفحة على الجوال لتجربة الكاميرا. تقدر تستكشف السيارة هون بالعرض الثلاثي.',
        'secure'=>'افتح الصفحة عبر HTTPS لاستخدام الكاميرا.',
        'fullScale'=>'حجم النموذج التقريبي', 'miniScale'=>'نموذج مصغّر',
    ];
@endphp
<style>{!! file_get_contents(base_path('public/car-explorer.css')) !!}</style>
<section class="car-experience wrap" data-car-explorer id="carExperience" aria-labelledby="carExperienceTitle">
    <div class="experience-copy"><span class="eyebrow">اكتشفها من كل زاوية · 3D / AR</span><h2 id="carExperienceTitle">افتح الباب.<br>واقعد مكان السوّاق.</h2><p>لفّ حول سيارة توضيحية، افتح الشنطة وغطاء المحرّك، وقرّب على تفاصيل المقصورة. ومن جوالك جرّب تحطّها قدّامك بالكاميرا.</p><button type="button" class="button primary" data-caravan-open>استكشف السيارة التوضيحية <span aria-hidden="true">↗</span></button><small>نموذج تفاعلي مبسّط، مستقل عن السيارات المعروضة للبيع.</small></div>
    <div class="experience-art" aria-hidden="true"><span class="experience-orbit"></span><svg viewBox="0 0 400 230"><ellipse cx="203" cy="181" rx="167" ry="22" fill="#071d25" opacity=".5"/><path d="M45 128 75 118 123 72Q132 63 160 63H258L310 113 351 128 361 162 43 162Z" fill="#2c9aab" stroke="#85d4dc" stroke-width="2"/><path d="m94 115 39-40h54v40Zm107-40h50l43 40h-93Z" fill="#132d38"/><path d="M52 138h24m249 0h26" stroke="#e0fcff" stroke-width="7"/><path d="M188 119v39m13-30h18" stroke="#123e49" stroke-width="3"/><g fill="#15242c" stroke="#6e929f" stroke-width="7"><circle cx="103" cy="164" r="29"/><circle cx="298" cy="164" r="29"/></g><g fill="#a2c3cd"><circle cx="103" cy="164" r="11"/><circle cx="298" cy="164" r="11"/></g></svg><span class="experience-art-label">EXPLORE / OPEN / STEP INSIDE</span></div>
    <dialog class="car-explorer-dialog" aria-labelledby="carExplorerTitle">
        <div class="car-explorer-workspace">
            <header class="car-explorer-toolbar"><div><small>OZMAN · INTERACTIVE DEMO</small><h2 id="carExplorerTitle">سيارة بين إيديك</h2></div><button type="button" data-caravan-close aria-label="إغلاق تجربة السيارة">×</button></header>
            <div class="car-explorer-stage">
                <div class="car-explorer-canvas" data-caravan-canvas aria-label="عرض السيارة ثلاثي الأبعاد"></div>
                <p class="car-explorer-status" data-caravan-status role="status" aria-live="polite">{{ $carCopy['loading'] }}</p>
                <span class="car-explorer-watermark">نموذج توضيحي</span>
                <div class="car-explorer-controls" dir="ltr"><button type="button" data-rotate="-1" aria-label="تدوير السيارة يسارًا">↶</button><button type="button" data-rotate="1" aria-label="تدوير السيارة يمينًا">↷</button><button type="button" data-zoom="-1" aria-label="تقريب">+</button><button type="button" data-zoom="1" aria-label="إبعاد">−</button></div>
                <button type="button" class="car-explorer-place" data-caravan-place hidden disabled>{{ $carCopy['place'] }}</button>
            </div>
            <aside class="car-explorer-options">
                <h3>من وين بدك تشوفها؟</h3>
                <div class="car-explorer-views" role="group" aria-label="زاوية العرض">
                    @foreach(['outside'=>'من برّا','inside'=>'مكان السوّاق','dashboard'=>'الطبلون','engine'=>'الماتور','luggage'=>'داخل الشنطة'] as $key=>$label)
                    <button type="button" data-car-view="{{ $key }}" aria-pressed="{{ $key==='outside'?'true':'false' }}">{{ $label }}</button>
                    @endforeach
                </div>
                <h3>افتح وجرّب</h3>
                <div class="car-explorer-parts">
                    @foreach(['driverDoor'=>'باب السوّاق','passengerDoor'=>'باب الراكب','hood'=>'غطاء المحرّك','trunk'=>'الشنطة','glovebox'=>'درج الطبلون'] as $key=>$label)
                    <button type="button" data-part="{{ $key }}" aria-pressed="false"><span>{{ $label }}<small data-part-label>مغلق · اضغط للفتح</small></span><b aria-hidden="true">+</b></button>
                    @endforeach
                </div>
                <div class="car-explorer-summary"><output data-caravan-summary></output><button type="button" data-car-reset>إعادة الضبط</button></div>
                <div class="car-explorer-scale" data-caravan-scale-controls hidden><label for="carExplorerScale">حجم السيارة <output data-caravan-scale-label></output></label><input type="range" id="carExplorerScale" data-caravan-scale min="5" max="100" value="30" step="1" dir="ltr"><button type="button" data-caravan-full-size>الحجم الكامل للنموذج</button><small>ابدأ بمكان واسع. الحجم والتثبيت تقريبيان.</small></div>
                <div class="car-explorer-actions"><button type="button" class="button primary" data-caravan-camera disabled>شوف السيارة قدّامك · AR</button><button type="button" class="button primary" data-caravan-preview hidden>رجوع للعرض الثلاثي</button><button type="button" class="button primary" data-caravan-retry hidden>جرّب تحميل العرض مرة ثانية</button></div>
                <p class="car-explorer-note">سيارة توضيحية مبسّطة، وليست تصويرًا أو فحصًا لأي سيارة معروضة للبيع.</p>
                <details class="car-explorer-credits"><summary>حقوق العرض الثلاثي</summary><p>Original demo geometry: Ozman. Three.js: MIT. XR Engine by Niantic Spatial, Inc. Copyright © 2026 Niantic Spatial, Inc. All rights reserved. Used under the <a href="https://github.com/8thwall/engine/blob/main/LICENSE" target="_blank" rel="noopener noreferrer">XR Engine License Agreement, including its disclaimer of warranties</a>.</p></details>
            </aside>
        </div>
    </dialog>
    <script type="application/json" data-caravan-copy>@json($carCopy)</script>
</section>
<script type="module" src="{{ route('front.assets', 'car-explorer.js') }}?v={{ hash_file('sha256', base_path('public/car-explorer.js')) }}"></script>
