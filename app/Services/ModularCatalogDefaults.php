<?php

namespace App\Services;

use App\Models\Shop;
use Illuminate\Support\Facades\DB;

class ModularCatalogDefaults
{
    public function ensure(Shop $shop): void
    {
        if ($shop->catalog_type !== 'real_estate' || $shop->modularCategories()->exists()) {
            return;
        }

        DB::transaction(function () use ($shop): void {
            if ($shop->modularCategories()->lockForUpdate()->exists()) {
                return;
            }

            foreach ($this->catalog() as $categoryIndex => $definition) {
                $category = $shop->modularCategories()->create([
                    'name' => $definition['name'][0],
                    'name_translations' => $this->translations($definition['name']),
                    'slug' => $definition['slug'],
                    'description' => $definition['description'][0],
                    'description_translations' => $this->translations($definition['description']),
                    'icon_key' => $definition['icon'],
                    'cover_image' => $definition['image'],
                    'position' => $categoryIndex + 1,
                    'is_active' => true,
                ]);

                $service = $category->services()->create([
                    'name' => $definition['service'][0],
                    'name_translations' => $this->translations($definition['service']),
                    'slug' => $definition['service_slug'],
                    'short_description' => $definition['description'][0],
                    'short_description_translations' => $this->translations($definition['description']),
                    'description' => $definition['long_description'][0],
                    'description_translations' => $this->translations($definition['long_description']),
                    'cover_image' => $definition['image'],
                    'position' => 1,
                    'is_featured' => true,
                    'is_active' => true,
                ]);

                foreach ($definition['groups'] as $groupIndex => $groupDefinition) {
                    [$key, $names, $help, $values] = $groupDefinition;
                    $group = $service->optionGroups()->create([
                        'key' => $key,
                        'name' => $names[0],
                        'name_translations' => $this->translations($names),
                        'help_text' => $help[0],
                        'help_text_translations' => $this->translations($help),
                        'is_required' => true,
                        'position' => $groupIndex + 1,
                    ]);

                    foreach ($values as $valueIndex => $value) {
                        $group->values()->create([
                            'key' => $value[0],
                            'name' => $value[1],
                            'name_translations' => ['he' => $value[2], 'en' => $value[3]],
                            'is_default' => $valueIndex === 0,
                            'is_active' => true,
                            'position' => $valueIndex + 1,
                        ]);
                    }
                }
            }
        });
    }

    private function translations(array $values): array
    {
        return ['he' => $values[1], 'en' => $values[2]];
    }

    private function catalog(): array
    {
        $required = ['اختر المواصفات المناسبة لمشروعك.', 'בחרו את המפרט המתאים לפרויקט.', 'Choose the specification that fits your project.'];

        return [
            [
                'slug' => 'mobile-homes', 'icon' => 'home', 'image' => 'images/real-estate-services/mobile-home.webp',
                'name' => ['البيوت المتنقلة', 'בתים ניידים', 'Mobile homes'],
                'service' => ['بيت متنقل حسب الطلب', 'בית נייד בהתאמה אישית', 'Custom mobile home'],
                'service_slug' => 'custom-mobile-home',
                'description' => ['بيوت عملية ومريحة بتصاميم عصرية، جاهزة للنقل والتركيب في الموقع.', 'בתים נוחים ומודרניים המוכנים להובלה ולהתקנה באתר.', 'Comfortable modern homes, ready for transportation and on-site installation.'],
                'long_description' => ['صمّم بيتك المتنقل باختيار المقاس والتقسيم والهيكل والعزل والتشطيبات الداخلية والخارجية.', 'תכננו את הבית הנייד באמצעות בחירת המידה, החלוקה, השלד, הבידוד והגמר.', 'Configure the size, layout, structure, insulation and interior and exterior finishes of your mobile home.'],
                'groups' => [
                    $this->group('dimensions', ['المقاس الخارجي', 'מידות חיצוניות', 'External dimensions'], $required, [
                        ['3x6', '3 × 6 متر', '3 × 6 מטר', '3 × 6 m'], ['3x8', '3 × 8 متر', '3 × 8 מטר', '3 × 8 m'], ['3x10', '3 × 10 متر', '3 × 10 מטר', '3 × 10 m'], ['4x10', '4 × 10 متر', '4 × 10 מטר', '4 × 10 m'], ['custom', 'مقاس خاص', 'מידה מיוחדת', 'Custom size'],
                    ]),
                    $this->group('layout', ['التقسيم الداخلي', 'חלוקה פנימית', 'Interior layout'], $required, [
                        ['studio', 'مساحة مفتوحة', 'חלל פתוח', 'Open studio'], ['one-bedroom', 'غرفة نوم واحدة', 'חדר שינה אחד', 'One bedroom'], ['two-bedroom', 'غرفتا نوم', 'שני חדרי שינה', 'Two bedrooms'], ['custom', 'تقسيم خاص', 'חלוקה מיוחדת', 'Custom layout'],
                    ]),
                    $this->group('structure', ['الهيكل', 'שלד', 'Structure'], $required, [
                        ['galvanized-light', 'حديد مجلفن خفيف', 'פלדה מגולוונת קלה', 'Light galvanized steel'], ['galvanized-reinforced', 'حديد مجلفن معزّز', 'פלדה מגולוונת מחוזקת', 'Reinforced galvanized steel'],
                    ]),
                    $this->group('wall-system', ['نظام الجدران', 'מערכת קירות', 'Wall system'], $required, [
                        ['sandwich-panel', 'ساندويتش بانل معزول', 'פאנל מבודד', 'Insulated sandwich panel'], ['fiber-cement', 'فايبر إسمنت مع كسوة', 'פייבר צמנט עם חיפוי', 'Fiber cement with cladding'], ['metal-wood', 'معدن مع ديكور خشبي', 'מתכת בשילוב עץ', 'Metal with wood accent'],
                    ]),
                    $this->group('insulation', ['مادة العزل', 'חומר בידוד', 'Insulation'], $required, [
                        ['eps', 'EPS بوليسترين', 'פוליסטירן EPS', 'EPS polystyrene'], ['rockwool', 'صوف صخري', 'צמר סלעים', 'Rock wool'], ['polyurethane', 'بولي يوريثان PU', 'פוליאוריתן PU', 'Polyurethane PU'],
                    ]),
                    $this->group('flooring', ['تشطيب الأرضية', 'גמר רצפה', 'Floor finish'], $required, [
                        ['vinyl', 'فينيل مقاوم للماء', 'ויניל עמיד למים', 'Water-resistant vinyl'], ['ceramic', 'سيراميك', 'קרמיקה', 'Ceramic tile'], ['laminate', 'لامينيت', 'למינציה', 'Laminate'],
                    ]),
                    $this->group('windows', ['الشبابيك', 'חלונות', 'Windows'], $required, [
                        ['pvc-double', 'PVC زجاج مزدوج', 'PVC זיגוג כפול', 'PVC double glazing'], ['aluminum-double', 'ألمنيوم زجاج مزدوج', 'אלומיניום זיגוג כפול', 'Aluminium double glazing'], ['security', 'ألمنيوم مع حماية', 'אלומיניום עם מיגון', 'Aluminium with security bars'],
                    ]),
                    $this->group('kitchen', ['المطبخ', 'מטבח', 'Kitchen'], $required, [
                        ['none', 'بدون مطبخ', 'ללא מטבח', 'No kitchen'], ['basic', 'مطبخ تحضيري صغير', 'מטבחון בסיסי', 'Basic kitchenette'], ['full', 'مطبخ كامل', 'מטבח מלא', 'Full kitchen'],
                    ]),
                    $this->group('bathroom', ['الحمّام الداخلي', 'חדר רחצה', 'Internal bathroom'], $required, [
                        ['none', 'بدون حمّام', 'ללא חדר רחצה', 'No bathroom'], ['basic', 'حمّام أساسي', 'חדר רחצה בסיסי', 'Basic bathroom'], ['full', 'حمّام كامل مع دش', 'חדר רחצה מלא עם מקלחת', 'Full bathroom with shower'],
                    ]),
                ],
            ],
            [
                'slug' => 'bathroom-units', 'icon' => 'bath', 'image' => 'images/real-estate-services/bathroom-unit.webp',
                'name' => ['الحمّامات', 'יחידות שירותים', 'Bathroom units'],
                'service' => ['وحدة حمّام جاهزة', 'יחידת שירותים מוכנה', 'Ready bathroom unit'],
                'service_slug' => 'ready-bathroom-unit',
                'description' => ['وحدات حمّامات مجهزة بعناية للاستخدام المؤقت أو الدائم وبمقاسات متعددة.', 'יחידות שירותים מאובזרות לשימוש זמני או קבוע ובמגוון גדלים.', 'Fully equipped bathroom units for temporary or permanent use in multiple sizes.'],
                'long_description' => ['اختر المقاس، تشطيب السيراميك، نوع المرحاض أو بدونه، المغسلة، الدش، التهوية وتجهيزات المياه.', 'בחרו מידה, גמר קרמיקה, סוג אסלה או ללא אסלה, כיור, מקלחת, אוורור ומערכת מים.', 'Choose the size, ceramic finish, toilet type or no toilet, basin, shower, ventilation and water setup.'],
                'groups' => [
                    $this->group('dimensions', ['المقاس الخارجي', 'מידות חיצוניות', 'External dimensions'], $required, [
                        ['1.2x1.2', '1.2 × 1.2 متر', '1.2 × 1.2 מטר', '1.2 × 1.2 m'], ['1.5x1.5', '1.5 × 1.5 متر', '1.5 × 1.5 מטר', '1.5 × 1.5 m'], ['1.5x2', '1.5 × 2 متر', '1.5 × 2 מטר', '1.5 × 2 m'], ['2x2', '2 × 2 متر', '2 × 2 מטר', '2 × 2 m'], ['custom', 'مقاس خاص', 'מידה מיוחדת', 'Custom size'],
                    ]),
                    $this->group('wall-finish', ['تشطيب الجدران', 'גמר קירות', 'Wall finish'], $required, [
                        ['sandwich', 'ساندويتش بانل سهل التنظيف', 'פאנל מבודד קל לניקוי', 'Easy-clean sandwich panel'], ['ceramic-wet-area', 'سيراميك في المناطق المبللة', 'קרמיקה באזורים רטובים', 'Ceramic in wet areas'], ['full-ceramic', 'سيراميك كامل', 'חיפוי קרמיקה מלא', 'Full ceramic finish'],
                    ]),
                    $this->group('flooring', ['الأرضية', 'רצפה', 'Flooring'], $required, [
                        ['anti-slip-ceramic', 'سيراميك مانع للانزلاق', 'קרמיקה נגד החלקה', 'Anti-slip ceramic'], ['waterproof-vinyl', 'فينيل مقاوم للماء', 'ויניל עמיד למים', 'Waterproof vinyl'], ['fiberglass', 'أرضية فايبرجلاس', 'רצפת פיברגלס', 'Fiberglass floor'],
                    ]),
                    $this->group('toilet', ['نوع القاعدة', 'סוג אסלה', 'Toilet type'], $required, [
                        ['none', 'بدون قعادة', 'ללא אסלה', 'No toilet'], ['western-floor', 'قعادة إفرنجية أرضية', 'אסלה מערבית רצפתית', 'Floor-mounted western toilet'], ['wall-hung', 'قعادة معلّقة', 'אסלה תלויה', 'Wall-hung toilet'], ['squat', 'قعادة عربية', 'אסלת כריעה', 'Squat toilet'],
                    ]),
                    $this->group('basin', ['المغسلة', 'כיור', 'Washbasin'], $required, [
                        ['none', 'بدون مغسلة', 'ללא כיור', 'No basin'], ['wall', 'مغسلة جدارية', 'כיור תלוי', 'Wall basin'], ['cabinet', 'مغسلة مع خزانة', 'כיור עם ארון', 'Basin with cabinet'],
                    ]),
                    $this->group('shower', ['الدش', 'מקלחת', 'Shower'], $required, [
                        ['none', 'بدون دش', 'ללא מקלחת', 'No shower'], ['open', 'منطقة دش مفتوحة', 'מקלחת פתוחה', 'Open shower area'], ['enclosure', 'دش مع فاصل زجاج', 'מקלחון זכוכית', 'Glass shower enclosure'],
                    ]),
                    $this->group('water-heater', ['تسخين المياه', 'חימום מים', 'Water heating'], $required, [
                        ['none', 'بدون سخان', 'ללא דוד', 'No water heater'], ['instant', 'سخان فوري', 'מחמם מיידי', 'Instant heater'], ['tank', 'سخان خزان', 'דוד אגירה', 'Tank heater'],
                    ]),
                    $this->group('ventilation', ['التهوية', 'אוורור', 'Ventilation'], $required, [
                        ['window', 'شباك تهوية', 'חלון אוורור', 'Ventilation window'], ['extractor', 'شفّاط كهربائي', 'מפוח חשמלי', 'Electric extractor'], ['both', 'شباك وشفّاط', 'חלון ומפוח', 'Window and extractor'],
                    ]),
                ],
            ],
            [
                'slug' => 'guard-rooms', 'icon' => 'shield', 'image' => 'images/real-estate-services/guard-room.webp',
                'name' => ['غرف الحراسة', 'עמדות שמירה', 'Guard rooms'],
                'service' => ['غرفة حراسة مجهزة', 'עמדת שמירה מאובזרת', 'Equipped guard room'],
                'service_slug' => 'equipped-guard-room',
                'description' => ['غرف آمنة وعملية للحراس ونقاط الاستقبال، مع إمكانية تجهيزها حسب الموقع.', 'עמדות בטוחות ושימושיות לשומרים ולקבלה, בהתאמה לתנאי השטח.', 'Safe, practical guard and reception rooms configured for each location.'],
                'long_description' => ['جهّز غرفة الحراسة بالمقاس ونظام الجدران والشبابيك والباب والكاونتر والكهرباء والتكييف المناسب.', 'התאימו את עמדת השמירה במידה, בקירות, בחלונות, בדלת, בדלפק, בחשמל ובמיזוג.', 'Configure the guard room size, wall system, windows, door, counter, electrical work and air conditioning.'],
                'groups' => [
                    $this->group('dimensions', ['المقاس الخارجي', 'מידות חיצוניות', 'External dimensions'], $required, [
                        ['1.5x1.5', '1.5 × 1.5 متر', '1.5 × 1.5 מטר', '1.5 × 1.5 m'], ['2x2', '2 × 2 متر', '2 × 2 מטר', '2 × 2 m'], ['2x3', '2 × 3 متر', '2 × 3 מטר', '2 × 3 m'], ['3x3', '3 × 3 متر', '3 × 3 מטר', '3 × 3 m'], ['custom', 'مقاس خاص', 'מידה מיוחדת', 'Custom size'],
                    ]),
                    $this->group('wall-system', ['نظام الجدران', 'מערכת קירות', 'Wall system'], $required, [
                        ['sandwich', 'ساندويتش بانل معزول', 'פאנל מבודד', 'Insulated sandwich panel'], ['abs', 'ألواح ABS مع عزل PU', 'לוחות ABS עם בידוד PU', 'ABS panels with PU insulation'], ['fiber-cement', 'فايبر إسمنت', 'פייבר צמנט', 'Fiber cement'],
                    ]),
                    $this->group('windows', ['توزيع الشبابيك', 'חלונות', 'Window layout'], $required, [
                        ['three-sides', 'شبابيك على 3 جهات', 'חלונות ב-3 צדדים', 'Windows on 3 sides'], ['four-sides', 'شبابيك على 4 جهات', 'חלונות ב-4 צדדים', 'Windows on 4 sides'], ['security-glass', 'زجاج حماية مع شبك', 'זכוכית מיגון וסורג', 'Security glass with bars'],
                    ]),
                    $this->group('door', ['الباب', 'דלת', 'Door'], $required, [
                        ['aluminum', 'باب ألمنيوم', 'דלת אלומיניום', 'Aluminium door'], ['steel', 'باب حديد أمان', 'דלת פלדה', 'Security steel door'], ['pvc', 'باب PVC', 'דלת PVC', 'PVC door'],
                    ]),
                    $this->group('counter', ['كاونتر الخدمة', 'דלפק שירות', 'Service counter'], $required, [
                        ['none', 'بدون كاونتر', 'ללא דלפק', 'No counter'], ['inside', 'كاونتر داخلي', 'דלפק פנימי', 'Internal counter'], ['external', 'كاونتر شباك خارجي', 'דלפק חלון חיצוני', 'External window counter'],
                    ]),
                    $this->group('air-conditioning', ['التكييف', 'מיזוג', 'Air conditioning'], $required, [
                        ['none', 'بدون', 'ללא', 'None'], ['preparation', 'تجهيز للتكييف', 'הכנה למזגן', 'AC preparation'], ['installed', 'مكيف مركّب', 'מזגן מותקן', 'Installed AC unit'],
                    ]),
                ],
            ],
            [
                'slug' => 'caravans', 'icon' => 'caravan', 'image' => 'images/real-estate-services/caravan.webp',
                'name' => ['الكرفانات', 'קרוואנים', 'Caravans'],
                'service' => ['كرفان متعدد الاستخدام', 'קרוואן רב-שימושי', 'Multi-purpose caravan'],
                'service_slug' => 'multi-purpose-caravan',
                'description' => ['كرفانات متعددة الاستخدام للسكن والعمل والمواقع، بتوزيعات داخلية مرنة.', 'קרוואנים למגורים, לעבודה ולאתרי פרויקט עם חלוקה פנימית גמישה.', 'Flexible caravans for living, work and project sites with adaptable interiors.'],
                'long_description' => ['حدّد استخدام الكرفان ومقاسه وطريقة الحركة والتقسيم والعزل والتشطيبات والخدمات المطلوبة.', 'בחרו שימוש, מידה, ניידות, חלוקה, בידוד, גמר ומערכות נדרשות.', 'Choose the caravan use, dimensions, mobility, layout, insulation, finishes and required utilities.'],
                'groups' => [
                    $this->group('use', ['الاستخدام', 'שימוש', 'Intended use'], $required, [
                        ['housing', 'سكن', 'מגורים', 'Housing'], ['office', 'مكتب', 'משרד', 'Office'], ['worksite', 'موقع عمل', 'אתר עבודה', 'Worksite'], ['clinic', 'عيادة متنقلة', 'מרפאה ניידת', 'Mobile clinic'], ['classroom', 'صف أو تدريب', 'כיתה או הדרכה', 'Classroom or training'],
                    ]),
                    $this->group('dimensions', ['المقاس الخارجي', 'מידות חיצוניות', 'External dimensions'], $required, [
                        ['3x6', '3 × 6 متر', '3 × 6 מטר', '3 × 6 m'], ['3x8', '3 × 8 متر', '3 × 8 מטר', '3 × 8 m'], ['3x10', '3 × 10 متر', '3 × 10 מטר', '3 × 10 m'], ['3x12', '3 × 12 متر', '3 × 12 מטר', '3 × 12 m'], ['custom', 'مقاس خاص', 'מידה מיוחדת', 'Custom size'],
                    ]),
                    $this->group('mobility', ['نظام الحركة', 'ניידות', 'Mobility'], $required, [
                        ['fixed', 'شاسيه ثابت قابل للنقل بالرافعة', 'שלדה קבועה להובלה במנוף', 'Fixed chassis for crane transport'], ['towable', 'شاسيه مع عجلات للسحب', 'שלדה נגררת עם גלגלים', 'Towable wheeled chassis'], ['special', 'نظام حركة خاص', 'מערכת ניידות מיוחדת', 'Custom mobility system'],
                    ]),
                    $this->group('layout', ['التقسيم الداخلي', 'חלוקה פנימית', 'Interior layout'], $required, [
                        ['open', 'مساحة مفتوحة', 'חלל פתוח', 'Open plan'], ['two-zone', 'مساحتان منفصلتان', 'שני אזורים', 'Two zones'], ['rooms', 'عدة غرف', 'מספר חדרים', 'Multiple rooms'], ['custom', 'تقسيم خاص', 'חלוקה מיוחדת', 'Custom layout'],
                    ]),
                    $this->group('insulation', ['مادة العزل', 'חומר בידוד', 'Insulation'], $required, [
                        ['eps', 'EPS بوليسترين', 'פוליסטירן EPS', 'EPS polystyrene'], ['rockwool', 'صوف صخري', 'צמר סלעים', 'Rock wool'], ['polyurethane', 'بولي يوريثان PU', 'פוליאוריתן PU', 'Polyurethane PU'],
                    ]),
                    $this->group('utilities', ['الخدمات الداخلية', 'מערכות פנימיות', 'Internal utilities'], $required, [
                        ['electric', 'كهرباء فقط', 'חשמל בלבד', 'Electrical only'], ['electric-water', 'كهرباء ومياه', 'חשמל ומים', 'Electrical and water'], ['full', 'كهرباء ومياه وصرف', 'חשמל, מים וביוב', 'Electrical, water and drainage'],
                    ]),
                ],
            ],
            [
                'slug' => 'custom-builds', 'icon' => 'tools', 'image' => 'images/real-estate-services/custom-build.webp',
                'name' => ['تصنيع حسب الطلب', 'ייצור לפי הזמנה', 'Custom builds'],
                'service' => ['مشروع مخصص بالكامل', 'פרויקט בהתאמה מלאה', 'Fully custom project'],
                'service_slug' => 'fully-custom-project',
                'description' => ['عندك فكرة مختلفة؟ نصمم وننفّذ الوحدة بالمقاس والتقسيم والتجهيز الذي يناسبك.', 'יש לכם רעיון מיוחד? נתכנן ונייצר את היחידה במידות ובמפרט המתאימים לכם.', 'Have a different idea? We design and build it to your dimensions, layout and specifications.'],
                'long_description' => ['ابدأ بتحديد نوع المشروع والحجم والهيكل والعزل والتشطيبات والخدمات، ثم أرسل التفاصيل على واتساب لمناقشتها.', 'התחילו מבחירת סוג הפרויקט, הגודל, השלד, הבידוד, הגמר והמערכות ושלחו את הפרטים בוואטסאפ.', 'Start with the project type, size, structure, insulation, finishes and utilities, then send the details through WhatsApp.'],
                'groups' => [
                    $this->group('purpose', ['نوع المشروع', 'סוג הפרויקט', 'Project type'], $required, [
                        ['home', 'بيت متنقل', 'בית נייד', 'Mobile home'], ['office', 'مكتب أو إدارة', 'משרד או הנהלה', 'Office or administration'], ['bathroom', 'وحدة حمّام', 'יחידת שירותים', 'Bathroom unit'], ['guard', 'غرفة حراسة', 'עמדת שמירה', 'Guard room'], ['clinic', 'عيادة', 'מרפאה', 'Clinic'], ['kiosk', 'كشك بيع', 'קיוסק', 'Sales kiosk'], ['other', 'فكرة أخرى', 'רעיון אחר', 'Other idea'],
                    ]),
                    $this->group('size-range', ['الحجم التقريبي', 'גודל משוער', 'Approximate size'], $required, [
                        ['small', 'صغير — حتى 15 م²', 'קטן — עד 15 מ״ר', 'Small — up to 15 m²'], ['medium', 'متوسط — 16 إلى 35 م²', 'בינוני — 16 עד 35 מ״ר', 'Medium — 16 to 35 m²'], ['large', 'كبير — أكثر من 35 م²', 'גדול — מעל 35 מ״ר', 'Large — over 35 m²'], ['custom', 'غير محدد بعد', 'טרם נקבע', 'Not decided yet'],
                    ]),
                    $this->group('structure', ['الهيكل', 'שלד', 'Structure'], $required, [
                        ['galvanized', 'حديد مجلفن', 'פלדה מגולוונת', 'Galvanized steel'], ['reinforced', 'حديد مجلفن معزّز', 'פלדה מגולוונת מחוזקת', 'Reinforced galvanized steel'], ['recommend', 'حسب توصية الشركة', 'לפי המלצת החברה', 'Company recommendation'],
                    ]),
                    $this->group('insulation', ['مادة العزل', 'חומר בידוד', 'Insulation'], $required, [
                        ['eps', 'EPS بوليسترين', 'פוליסטירן EPS', 'EPS polystyrene'], ['rockwool', 'صوف صخري', 'צמר סלעים', 'Rock wool'], ['polyurethane', 'بولي يوريثان PU', 'פוליאוריתן PU', 'Polyurethane PU'], ['recommend', 'حسب توصية الشركة', 'לפי המלצת החברה', 'Company recommendation'],
                    ]),
                    $this->group('finish-level', ['مستوى التشطيب', 'רמת גמר', 'Finish level'], $required, [
                        ['shell', 'هيكل وإغلاق فقط', 'מעטפת בלבד', 'Shell only'], ['standard', 'تشطيب قياسي', 'גמר סטנדרטי', 'Standard finish'], ['premium', 'تشطيب فاخر', 'גמר פרימיום', 'Premium finish'],
                    ]),
                    $this->group('utilities', ['الخدمات المطلوبة', 'מערכות נדרשות', 'Required utilities'], $required, [
                        ['electric', 'كهرباء', 'חשמל', 'Electrical'], ['electric-water', 'كهرباء ومياه', 'חשמל ומים', 'Electrical and water'], ['full', 'كهرباء ومياه وصرف وتكييف', 'חשמל, מים, ביוב ומיזוג', 'Electrical, water, drainage and AC'],
                    ]),
                ],
            ],
        ];
    }

    private function group(string $key, array $names, array $help, array $values): array
    {
        return [$key, $names, $help, $values];
    }
}
