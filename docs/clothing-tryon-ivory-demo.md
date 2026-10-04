# صورة تجربة الملابس — أوف وايت وكحلي

الصورة الحالية: `public/images/virtual-tryon/demo-ivory-navy-tee.png`.

تم تصميمها بأداة `image_gen` المدمجة، باستخدام صورة التيشيرت الأزرق كهدف تعديل للحفاظ على شكل القطعة ومعايرة التتبّع. التصميم أوف وايت مع ياقة وأطراف أكمام كحلية، تفاصيل بلون ذهبي وتطريز صغير على الصدر. الخلفية شفافة.

تم تحديث مسار صورة التجربة في `routes/web.php` وإصدار الرابط إلى `ivory-navy-3` داخل صفحة الملابس. يجب رفع الصورة الجديدة مع هذين الملفين. الصورة الزرقاء محفوظة كنسخة سابقة. لم يتغير كود تتبّع الجسم ضمن هذا التعديل، ولم يتم نشر التعديل بواسطة الوكيل.

## نص التوليد

> Use case: precise-object-edit / product-mockup. Image 1 is the edit target: the calibrated flat T-shirt texture for a live virtual try-on. Redesign ONLY its material, color and restrained decorative details into an exceptionally attractive premium contemporary ringer T-shirt people would actually want to wear: warm ivory/off-white heavyweight cotton, deep midnight-navy fine ribbed crew neckline and navy sleeve cuff bands, a fine warm tan piping accent along the navy trims, a tasteful small embroidered abstract sunburst crest on the wearer's left chest (viewer right), navy and muted gold threads, no words or letters. Subtle luxurious fabric texture and clean precise stitching, soft very even studio illumination, fresh and neatly pressed, no heavy wrinkles, no draping. CRITICAL INVARIANTS: preserve the EXACT original outer silhouette, position, proportions, shoulder seams, short sleeve geometry, sleeve angle, hem, neckline geometry, image framing and transparent canvas margins. Do not add a model, hanger or mannequin. Straight front orthographic view, perfectly flat and symmetrical. Preserve true alpha transparency outside the shirt and inside the neck opening. Remove stray blue pixels/fringing outside the silhouette. Clean high-quality ecommerce cutout, no cast shadow, no background, no watermark. The body should feel elegant and wearable, not a plain blank blue technical demo and not a sports jersey. The exact silhouette is essential because it is already matched to tracked body joints.
