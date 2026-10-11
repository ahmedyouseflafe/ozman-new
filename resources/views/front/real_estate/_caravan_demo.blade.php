@php
    $demo = match ($locale) {
        'en' => [
            'badge'=>'Interactive demo', 'title'=>'Try a caravan on your land', 'intro'=>'Explore a sample 6 × 3 m home. Add a side bathroom and an outdoor kitchen, then try placing it with your phone camera.',
            'open'=>'Design & try it', 'close'=>'Close', 'camera'=>'Place on my land', 'preview'=>'Back to 3D', 'bathroom'=>'Side bathroom', 'kitchen'=>'Outdoor kitchen', 'included'=>'Included', 'add'=>'Add',
            'note'=>'Illustrative model, not an available product or a construction plan. Dimensions and AR placement are approximate.',
            'orbit'=>'Drag to look around. Use + / − to zoom.', 'loading'=>'Loading the model…', 'loadError'=>'The model could not load. Check your connection and try again.', 'retry'=>'Try again',
            'engine'=>'Preparing the camera… Allow camera and motion access when asked.', 'scan'=>'Point at level ground and walk a few steps slowly until the placement ring appears.',
            'ready'=>'Point the ring at the intended spot, then tap Place here.', 'place'=>'Place here', 'placed'=>'Placed. You can now add or remove parts and walk around it.', 'move'=>'Change position',
            'lost'=>'Tracking is recovering. Slowly point the camera at the same ground.', 'cameraError'=>'Camera AR could not start. Allow camera and motion access; on iPhone open this page in Safari. You can still use the 3D preview.',
            'desktop'=>'To place it on your land, open this page on your phone. You can customize the 3D model here.', 'secure'=>'Open this page over HTTPS to use the camera.',
            'rotateLeft'=>'Rotate left', 'rotateRight'=>'Rotate right', 'zoomIn'=>'Zoom in', 'zoomOut'=>'Zoom out', 'size'=>'Demo home · 6 × 3 m', 'configure'=>'Choose your additions',
            'selected'=>'Current selection', 'yes'=>'with', 'no'=>'without', 'send'=>'Ask about this configuration', 'message'=>'Hello, I tried the illustrative caravan demo. I would like to discuss a 6 × 3 m home',
        ],
        'he' => [
            'badge'=>'דגם להדגמה', 'title'=>'נסו קרוואן בשטח שלכם', 'intro'=>'גלו דגם של מבנה בגודל 6 × 3 מ׳. הוסיפו יחידת רחצה צדדית ומטבח חיצוני ונסו להציב אותו באמצעות מצלמת הטלפון.',
            'open'=>'עיצוב והתנסות', 'close'=>'סגירה', 'camera'=>'הצבה בשטח שלי', 'preview'=>'חזרה לתלת־ממד', 'bathroom'=>'יחידת רחצה צדדית', 'kitchen'=>'מטבח חיצוני', 'included'=>'כלול', 'add'=>'הוספה',
            'note'=>'דגם להמחשה בלבד, לא מוצר זמין או תוכנית בנייה. המידות וההצבה במציאות רבודה הן משוערות.',
            'orbit'=>'גררו כדי לסובב. השתמשו ב־+ / − להגדלה.', 'loading'=>'טוען את הדגם…', 'loadError'=>'הדגם לא נטען. בדקו את החיבור ונסו שוב.', 'retry'=>'נסו שוב',
            'engine'=>'מכין את המצלמה… אשרו גישה למצלמה ולתנועה כשתתבקשו.', 'scan'=>'כוונו לקרקע ישרה ולכו כמה צעדים לאט, עד להופעת טבעת ההצבה.',
            'ready'=>'כוונו את הטבעת למקום הרצוי ולחצו על הצבה כאן.', 'place'=>'הצבה כאן', 'placed'=>'הדגם הוצב. אפשר להוסיף או להסיר חלקים וללכת סביבו.', 'move'=>'שינוי מיקום',
            'lost'=>'משחזר מעקב. כוונו לאט את המצלמה לאותו אזור בקרקע.', 'cameraError'=>'לא ניתן להפעיל מציאות רבודה. אשרו גישה למצלמה ולתנועה; ב־iPhone פתחו ב־Safari. אפשר להמשיך בתצוגת תלת־ממד.',
            'desktop'=>'להצבה בשטח פתחו את הדף בטלפון. כאן אפשר לעצב את הדגם בתלת־ממד.', 'secure'=>'פתחו את הדף בחיבור HTTPS כדי להשתמש במצלמה.',
            'rotateLeft'=>'סיבוב שמאלה', 'rotateRight'=>'סיבוב ימינה', 'zoomIn'=>'הגדלה', 'zoomOut'=>'הקטנה', 'size'=>'דגם הדגמה · 6 × 3 מ׳', 'configure'=>'בחרו תוספות',
            'selected'=>'הבחירה שלכם', 'yes'=>'עם', 'no'=>'בלי', 'send'=>'בירור לגבי הדגם שבחרתי', 'message'=>'שלום, ניסיתי את דגם הקרוואן להמחשה. אשמח לברר לגבי מבנה בגודל 6 × 3 מ׳',
        ],
        default => [
            'badge'=>'نسخة تجريبية تفاعلية', 'title'=>'جرّب الكرفان على أرضك', 'intro'=>'لفّ حول نموذج بيت 6 × 3 متر، اختار الحمّام والمطبخ الخارجي، وبعدين جرّب تحطّه على أرضك بكاميرا التلفون.',
            'open'=>'صمّم وجرّب الكرفان', 'close'=>'إغلاق', 'camera'=>'شوفه على أرضك', 'preview'=>'رجوع للعرض الثلاثي', 'bathroom'=>'حمّام جانبي', 'kitchen'=>'مطبخ خارجي', 'included'=>'مضاف', 'add'=>'إضافة',
            'note'=>'نموذج توضيحي للتجربة، مش منتج معروض للبيع أو مخطط بناء. المقاسات والتثبيت على الأرض تقريبية.',
            'orbit'=>'اسحب لتشوفه من الجهات. كبّر وصغّر بأزرار + / −.', 'loading'=>'بنجهّز النموذج…', 'loadError'=>'ما قدرنا نحمّل النموذج. تأكّد من الاتصال وجرّب مرة ثانية.', 'retry'=>'إعادة المحاولة',
            'engine'=>'بنجهّز الكاميرا… وافق على إذن الكاميرا والحركة لما يطلبهم التلفون.', 'scan'=>'وجّه الكاميرا على أرض مستوية وامشي كم خطوة بهدوء لحد ما تظهر دائرة التثبيت.',
            'ready'=>'وجّه الدائرة للمكان المطلوب واضغط «ثبّت هون».', 'place'=>'ثبّت هون', 'placed'=>'تثبّت الكرفان. ضيف أو الغِ القطع ولفّ حوله.', 'move'=>'غيّر مكانه',
            'lost'=>'بنستعيد التثبيت. وجّه الكاميرا بهدوء لنفس الأرض.', 'cameraError'=>'تعذّر تشغيل المعاينة بالكاميرا. اسمح للكاميرا والحركة؛ على الآيفون افتح الصفحة في Safari. بتقدر تكمّل بالعرض الثلاثي.',
            'desktop'=>'عشان تشوفه على الأرض، افتح نفس الصفحة من تلفونك. هون بتقدر تجرّب النموذج وتغيّر إضافاته.', 'secure'=>'افتح الصفحة برابط HTTPS لتشغيل الكاميرا.',
            'rotateLeft'=>'لفّ لليسار', 'rotateRight'=>'لفّ لليمين', 'zoomIn'=>'تكبير', 'zoomOut'=>'تصغير', 'size'=>'نموذج تجريبي · 6 × 3 متر', 'configure'=>'اختار الإضافات',
            'selected'=>'اختيارك الحالي', 'yes'=>'مع', 'no'=>'بدون', 'send'=>'استفسر عن هاد التكوين', 'message'=>'مرحباً، جرّبت نموذج الكرفان التوضيحي، وبدي أستفسر عن بيت بمقاس 6 × 3 متر',
        ],
    };
@endphp
<link rel="stylesheet" href="{{ route('front.assets', 'caravan-demo.css') }}?v=1">
<section class="caravan-demo" id="caravan-demo" data-caravan-demo data-contact="{{ $generalWhatsappUrl }}" aria-labelledby="caravan-demo-title">
    <div class="caravan-demo-copy">
        <span class="eyebrow">{{ $demo['badge'] }}</span>
        <h2 id="caravan-demo-title">{{ $demo['title'] }}</h2>
        <p>{{ $demo['intro'] }}</p>
        <button class="primary-link" type="button" data-caravan-open>{{ $demo['open'] }} <span aria-hidden="true">↗</span></button>
        <small>{{ $demo['note'] }}</small>
    </div>
    <div class="caravan-demo-art" aria-hidden="true">
        <img src="{{ route('front.assets', 'caravan-demo-poster.png') }}" alt="" loading="lazy" width="960" height="640">
        <span>{{ $demo['size'] }}</span>
    </div>
    <dialog class="caravan-dialog" aria-labelledby="caravan-dialog-title">
        <div class="caravan-workspace">
            <header class="caravan-toolbar"><div><span>{{ $demo['badge'] }}</span><h2 id="caravan-dialog-title">{{ $demo['title'] }}</h2></div><button type="button" data-caravan-close aria-label="{{ $demo['close'] }}">×</button></header>
            <div class="caravan-stage" data-caravan-stage>
                <div class="caravan-canvas-host" data-caravan-canvas></div>
                <p class="caravan-status" data-caravan-status role="status" aria-live="polite">{{ $demo['loading'] }}</p>
                <div class="caravan-view-controls" dir="ltr">
                    <button type="button" data-rotate="-1" aria-label="{{ $demo['rotateLeft'] }}">↶</button>
                    <button type="button" data-rotate="1" aria-label="{{ $demo['rotateRight'] }}">↷</button>
                    <button type="button" data-zoom="-1" aria-label="{{ $demo['zoomIn'] }}">+</button>
                    <button type="button" data-zoom="1" aria-label="{{ $demo['zoomOut'] }}">−</button>
                </div>
                <button class="caravan-place" type="button" data-caravan-place hidden disabled>{{ $demo['place'] }}</button>
            </div>
            <aside class="caravan-options">
                <h3>{{ $demo['configure'] }}</h3>
                <div class="caravan-option-list">
                    <button type="button" class="caravan-option" data-part="bathroom" aria-pressed="true">
                        <svg viewBox="0 0 96 72" aria-hidden="true"><path fill="#b98555" d="M18 19 57 8 80 21 80 61 43 70 18 56Z"/><path fill="#273d3d" d="m18 19 39-11 23 13-37 12Z"/><path fill="#e2ded0" d="m48 37 16-4v29l-16 4Z"/><path fill="#81b8bc" d="m23 30 13 7v11l-13-7Z"/></svg>
                        <span>{{ $demo['bathroom'] }}<small data-part-label>{{ $demo['included'] }}</small></span><b aria-hidden="true">✓</b>
                    </button>
                    <button type="button" class="caravan-option" data-part="kitchen" aria-pressed="false">
                        <svg viewBox="0 0 96 72" aria-hidden="true"><path fill="#b98555" d="m10 54 48-15 30 17-48 15Z"/><path fill="#273d3d" d="m15 13 5 2v42l-5-3Zm62-1 5-2v45l-5 2ZM39 26l5 2v39l-5-2Z"/><path fill="#cda775" d="m10 10 48-9 30 13-48 16Z"/><path fill="#233e3c" d="m27 36 30-9 23 10v18l-30 10-23-13Z"/><path fill="#e3e0d5" d="m25 34 32-9 25 11-32 10Z"/><ellipse fill="#253737" cx="59" cy="35" rx="7" ry="3"/></svg>
                        <span>{{ $demo['kitchen'] }}<small data-part-label>{{ $demo['add'] }}</small></span><b aria-hidden="true">+</b>
                    </button>
                </div>
                <div class="caravan-summary"><strong>{{ $demo['size'] }}</strong><output data-caravan-summary aria-label="{{ $demo['selected'] }}"></output></div>
                <div class="caravan-actions">
                    <button type="button" class="primary-link" data-caravan-camera disabled>{{ $demo['camera'] }}</button>
                    <button type="button" class="secondary-link" data-caravan-preview hidden>{{ $demo['preview'] }}</button>
                    <button type="button" class="secondary-link" data-caravan-retry hidden>{{ $demo['retry'] }}</button>
                    @if($generalWhatsappUrl)<a class="secondary-link" data-caravan-contact href="{{ $generalWhatsappUrl }}" target="_blank" rel="noopener noreferrer">{{ $demo['send'] }} ↗</a>@endif
                </div>
                <p class="caravan-disclaimer">{{ $demo['note'] }}</p>
                <details class="caravan-license"><summary>3D / AR credits</summary><p>Original demo geometry: Ozman. Three.js: MIT. XR Engine by Niantic Spatial, Inc. Copyright © 2026 Niantic Spatial, Inc. All rights reserved. Used under the <a href="https://github.com/8thwall/engine/blob/main/LICENSE" target="_blank" rel="noopener noreferrer">XR Engine License Agreement, including its disclaimer of warranties</a>.</p></details>
            </aside>
        </div>
    </dialog>
    <script type="application/json" data-caravan-copy>@json($demo)</script>
</section>
<script type="module" src="{{ route('front.assets', 'caravan-demo.js') }}?v=1"></script>
