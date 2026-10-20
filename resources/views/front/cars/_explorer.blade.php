@php
    $carCopy = [
        'loading'=>'بنحمّل موديل BMW M4 المفصّل… التحميل الأول حوالي 6 ميجابايت.',
        'orbit'=>'اسحب لتلفّ حول BMW M4، واستخدم + و − للتقريب. اختار مكان السوّاق لتشوف المقصورة.',
        'insideHint'=>'أنت داخل السيارة. اسحب لتطلع حولك، واختار «من برّا» للرجوع.',
        'loadError'=>'ما قدرنا نحمّل العرض الثلاثي. جرّب مرة ثانية أو افتح الصفحة بمتصفح يدعم WebGL.',
        'engine'=>'بنجهّز الكاميرا… وافق على إذن الكاميرا والحركة لما يطلبه الجوال.',
        'scan'=>'وجّه الكاميرا لأرض مستوية وتحرك شوي بهدوء حتى تظهر دائرة التثبيت.',
        'ready'=>'وجّه الدائرة للمكان المطلوب واضغط «ثبّت السيارة هون».',
        'place'=>'ثبّت السيارة هون',
        'placed'=>'السيارة ثابتة. لفّ حولها وقرّب لتشوف تفاصيلها.',
        'move'=>'غيّر مكان السيارة',
        'lost'=>'التتبّع توقف مؤقتًا. وجّه الكاميرا للأرض وتحرك بهدوء.',
        'cameraError'=>'تعذّر تشغيل تجربة الكاميرا. رجعناك للعرض الثلاثي؛ تأكد من أذونات الكاميرا والحركة ودعم جهازك.',
        'desktop'=>'افتح الصفحة على الجوال لتجربة الكاميرا. تقدر تستكشف السيارة هون بالعرض الثلاثي.',
        'secure'=>'افتح الصفحة عبر HTTPS لاستخدام الكاميرا.',
        'fullScale'=>'حجم النموذج التقريبي', 'miniScale'=>'نموذج مصغّر',
    ];
@endphp
<style>{!! file_get_contents(base_path('public/car-explorer.css')) !!}</style>
<section class="car-experience wrap" data-car-explorer data-model-url="{{ route('front.assets', 'bmw-m4.glb') }}" id="carExperience" aria-labelledby="carExperienceTitle">
    <div class="experience-copy"><span class="eyebrow">موديل مفصّل لسيارة حقيقية · 3D / AR</span><h2 id="carExperienceTitle"><bdi>BMW M4</bdi><br>شوفها من قريب.</h2><p>استكشف موديل BMW M4 Competition من كل الجهات، وادخل شوف المقصورة والطبلون. ومن جوالك جرّب تحطّها قدّامك بالكاميرا.</p><button type="button" class="button primary" data-caravan-open>استكشف BMW M4 <span aria-hidden="true">↗</span></button><small>موديل مرجعي مفصّل، مستقل عن السيارات المعروضة للبيع.</small></div>
    <div class="experience-art"><img src="{{ route('front.assets', 'bmw-m4-preview.webp') }}" alt="BMW M4 Competition" loading="lazy" width="968" height="714"></div>
    <dialog class="car-explorer-dialog" aria-labelledby="carExplorerTitle">
        <div class="car-explorer-workspace">
            <header class="car-explorer-toolbar"><div><small>OZMAN · 3D SHOWROOM</small><h2 id="carExplorerTitle"><bdi>BMW M4 Competition</bdi></h2></div><button type="button" data-caravan-close aria-label="إغلاق تجربة السيارة">×</button></header>
            <div class="car-explorer-stage">
                <div class="car-explorer-canvas" data-caravan-canvas aria-label="عرض السيارة ثلاثي الأبعاد"></div>
                <p class="car-explorer-status" data-caravan-status role="status" aria-live="polite">{{ $carCopy['loading'] }}</p>
                <span class="car-explorer-watermark">BMW M4 · موديل مرجعي</span>
                <div class="car-explorer-controls" dir="ltr"><button type="button" data-rotate="-1" aria-label="تدوير السيارة يسارًا">↶</button><button type="button" data-rotate="1" aria-label="تدوير السيارة يمينًا">↷</button><button type="button" data-zoom="-1" aria-label="تقريب">+</button><button type="button" data-zoom="1" aria-label="إبعاد">−</button></div>
                <button type="button" class="car-explorer-place" data-caravan-place hidden disabled>{{ $carCopy['place'] }}</button>
            </div>
            <aside class="car-explorer-options">
                <h3>من وين بدك تشوفها؟</h3>
                <div class="car-explorer-views" role="group" aria-label="زاوية العرض">
                    @foreach(['outside'=>'من برّا','inside'=>'مكان السوّاق','dashboard'=>'الطبلون','rear'=>'من الخلف'] as $key=>$label)
                    <button type="button" data-car-view="{{ $key }}" aria-pressed="{{ $key==='outside'?'true':'false' }}">{{ $label }}</button>
                    @endforeach
                </div>
                <p class="car-explorer-note">هذا الموديل يدعم مشاهدة الهيكل والمقصورة. فتح الأبواب والكبوت والشنطة غير متاح في هذا الملف.</p>
                <div class="car-explorer-summary"><output data-caravan-summary hidden></output><button type="button" data-car-reset>رجوع للعرض الخارجي</button></div>
                <div class="car-explorer-scale" data-caravan-scale-controls hidden><label for="carExplorerScale">حجم السيارة <output data-caravan-scale-label></output></label><input type="range" id="carExplorerScale" data-caravan-scale min="5" max="100" value="30" step="1" dir="ltr"><button type="button" data-caravan-full-size>الحجم الكامل للنموذج</button><small>ابدأ بمكان واسع. الحجم والتثبيت تقريبيان.</small></div>
                <div class="car-explorer-actions"><button type="button" class="button primary" data-caravan-camera disabled>شوف السيارة قدّامك · AR</button><button type="button" class="button primary" data-caravan-preview hidden>رجوع للعرض الثلاثي</button><button type="button" class="button primary" data-caravan-retry hidden>جرّب تحميل العرض مرة ثانية</button></div>
                <p class="car-explorer-note">موديل مرجعي، وليس تصويرًا أو فحصًا لأي سيارة معروضة للبيع. الحجم في الكاميرا تقريبي.</p>
                <p class="car-explorer-note"><bdi>BMW M4 Competition M Package</bdi> — <a href="https://sketchfab.com/TheRealSRT" target="_blank" rel="noopener noreferrer">SRT Performance</a> · <a href="https://creativecommons.org/licenses/by/4.0/" target="_blank" rel="noopener noreferrer">CC BY 4.0</a> · <a href="https://sketchfab.com/3d-models/bmw-m4-competition-m-package-5c0a2dafb1ad408d9fc9eeef9aee531b" target="_blank" rel="noopener noreferrer">المصدر</a>. تم ضغط الملف وضبط الإضاءة وحجم العرض.</p>
                <details class="car-explorer-credits"><summary>حقوق العرض الثلاثي</summary><p>Three.js: MIT. XR Engine by Niantic Spatial, Inc. Copyright © 2026 Niantic Spatial, Inc. All rights reserved. Used under the <a href="https://github.com/8thwall/engine/blob/main/LICENSE" target="_blank" rel="noopener noreferrer">XR Engine License Agreement, including its disclaimer of warranties</a>.</p></details>
            </aside>
        </div>
    </dialog>
    <script type="application/json" data-caravan-copy>@json($carCopy)</script>
</section>
<script type="module" src="{{ route('front.assets', 'car-explorer.js') }}?v={{ hash_file('sha256', base_path('public/car-explorer.js')) }}"></script>
