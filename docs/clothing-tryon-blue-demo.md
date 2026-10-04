# تجربة التيشيرت الأزرق — 2026-10-04

تم استبدال صورة التجربة بتيشيرت أزرق مفرود وشفاف الخلفية، ومعايرة موضع مفصل الكتف داخل رأس الكم بدل تثبيته على أعلى حافة الصورة. تحرّك الأكمام مستقل عن الصدر؛ لا يتم شد شبكة الصدر عند رفع الذراع.

## الملفات المطلوبة للرفع

- `public/clothing-tryon.js`
- `public/images/virtual-tryon/demo-smooth-blue-tee.png` — الصورة الجديدة؛ يجب تضمين هذا الملف الجديد في Git والرفع.
- `resources/views/front/clothing_store.blade.php`
- `routes/web.php`

كود تشغيل الكاميرا ما زال مضمّنًا داخل Blade لتجنب مشكلة 404 التي ظهرت مع طلب ملف JavaScript المنفصل. رابط صورة التجربة يحمل `v=smooth-blue-2` لتجاوز النسخة القديمة المخبأة في المتصفح. لم يحصل رفع أو commit بواسطة الوكيل.

## التتبع والرسم

- كتفان بثقة لا تقل عن 0.75، مع تنعيم إحداثيات الجسم.
- خياطة الكتف في الصورة عند `u=0.224/0.776`؛ المفصل داخل رأس الكم عند `v=0.20`، وأعلى الخياطة عند `v=0.15`.
- كمّان مستقلان مرتبطان بالمرفقين، مع لوح صدر ثابت لا يتجعّد بسبب حركة الذراع.
- استعادة الأجزاء الأمامية من الساعد واليد باستخدام تقدير العمق وقناع الشخص؛ هذه أقنعة تقريبية وليست تقسيمًا دقيقًا لكل إصبع.
- الرسم من نفس إطار الكاميرا الذي حُلّل، لمنع اختلاف توقيت الجسم والملابس.
- عرض إطار الكاميرا كاملًا بدل قص جانبيه، وخيار لإظهار نقاط الكتفين والمرفقين والرسغين.

## التحقق وحدود النتيجة

نجح 11 اختبار JavaScript للحركة والمحاذاة واستقلال الأكمام وعمق الساعدين، واختبارا Laravel للصفحة وملف PNG الشفاف، إضافة إلى اختبار توليد المعاينة. نجح اختبار متصفح لرفع ذراع واحدة، ثبات الصدر، فقدان الجسم، وإغلاق الكاميرا أثناء انتظار الإذن وعند فشل النموذج.

شُغّل نموذج MediaPipe الحقيقي، بما فيه قناع الشخص، على [صورة الاختبار العامة من MediaPipe](https://storage.googleapis.com/mediapipe-assets/pose.jpg). اكتشف 33 نقطة ورسم التيشيرت الجديد دون أخطاء JavaScript في الاختبار. الصورة المرجعية اختبار ثابت؛ لا تمثل تحققًا على كاميرا المستخدم أو على كل الأجسام والإضاءات والحركات.

النتيجة معاينة ثنائية الأبعاد معايرة لتيشيرت قصير من الأمام. لا تمثل محاكاة قماش ثلاثية الأبعاد أو ضمان ملاءمة المقاس، ولا تعطي واقعية 100% عند الاستدارة أو تقاطع الأطراف أو مع صور منتجات مختلفة القصّة. يلزم نموذج ملابس ثلاثي الأبعاد ومعايرة أبعاد الجسم لمحاكاة تلك الحالات بدقة.

## الصورة الجديدة

أُنشئت بأداة `image_gen` المدمجة وفق مهارة `imagegen`، وحُفظت في `public/images/virtual-tryon/demo-smooth-blue-tee.png` مع قناة RGBA. الصورة الأصلية السوداء محفوظة ولم تُحذف.

النص المستخدم لتوليد الصورة:

> Use case: product-mockup. Asset type: transparent PNG texture for a live virtual short-sleeve T-shirt try-on. Create ONE premium plain medium royal blue short-sleeved crew-neck T-shirt, absolutely straight frontal orthographic view, symmetrical, perfectly ironed and smooth, very subtle fine cotton texture, clearly defined shoulder seams and collar, no rumpled fabric, no drape, no heavy folds, no lighting gradients. Garment laid flat in a T shape with both short sleeves extending horizontally sideways (NOT drooping), straight torso sides, horizontal straight hem. Entire shirt alone, no model, no mannequin, no hanger, no background, no shadow, no words, logos, labels or decorations. True alpha transparency outside the garment AND through the neck opening. Use a square canvas, full garment centered with a small consistent transparent margin, left and right sleeves identical. Crisp clean professional ecommerce product cutout, soft even frontal studio illumination. This is a texture that will be attached to tracked shoulders and upper arms: keep the body and sleeve boundaries clean, smooth, clearly recognizable and suitable for separate articulated sleeves.
