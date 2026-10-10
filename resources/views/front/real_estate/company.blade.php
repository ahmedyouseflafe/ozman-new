@php
    $locale = app()->getLocale();
    $rtl = in_array($locale, ['ar', 'he'], true);
    $copy = match ($locale) {
        'he' => [
            'services' => 'מבנים ניידים ופתרונות בנייה', 'browse' => 'לכל החנויות', 'sections' => 'קטגוריות השירות',
            'contact' => 'יצירת קשר ב-WhatsApp', 'call' => 'התקשרו לפרטים', 'available' => 'פתוח לפניות', 'social' => 'רשתות חברתיות',
            'empty' => 'אין שירותים זמינים כרגע.', 'empty_section' => 'אין שירותים בקטגוריה זו כרגע.',
            'service_hint' => 'שירות המותאם לצרכים שלכם. פנו אלינו לפרטים.',
        ],
        'en' => [
            'services' => 'Mobile buildings and construction solutions', 'browse' => 'Browse all stores', 'sections' => 'Service categories',
            'contact' => 'Contact via WhatsApp', 'call' => 'Call for details', 'available' => 'Open for inquiries', 'social' => 'Social media',
            'empty' => 'No services are available yet.', 'empty_section' => 'No services in this category yet.',
            'service_hint' => 'A service tailored to your needs. Contact the company for details.',
        ],
        default => [
            'services' => 'المباني المتنقلة وحلول البناء', 'browse' => 'تصفح جميع المحلات', 'sections' => 'أقسام الخدمات',
            'contact' => 'تواصل عن طريق الواتساب', 'call' => 'اتصل للاستفسار', 'available' => 'متاح للاستفسارات', 'social' => 'منصات التواصل الاجتماعي',
            'empty' => 'لا توجد خدمات متاحة حالياً.', 'empty_section' => 'لا توجد خدمات في هذا القسم حالياً.',
            'service_hint' => 'خدمة مخصصة حسب احتياجك. تواصل مع الشركة للتفاصيل.',
        ],
    };
    $mediaUrl = fn (?string $path) => ! filled($path) ? '' : (preg_match('/^https?:\/\//i', $path) ? $path : asset($path));
    $shopImage = $mediaUrl($shop->banner ?: $shop->logo) ?: asset('images/logo.svg');
    $logo = $mediaUrl($shop->logo) ?: asset('images/logo.svg');
    $canonical = route('real-estate.company', $shop);
    $description = $shop->description ?: $copy['services'].' — '.$shop->name;
    $social = $shop->social;
    $whatsappDigits = preg_replace('/\D+/', '', (string) ($shop->whatsapp ?: $social?->whatsapp ?: $shop->phone)) ?: '';
    if (str_starts_with($whatsappDigits, '00')) {
        $whatsappDigits = substr($whatsappDigits, 2);
    } elseif (str_starts_with($whatsappDigits, '0')) {
        $countryCode = preg_replace('/\D+/', '', (string) config('services.whatsapp_cloud.default_country_code', '972')) ?: '972';
        $whatsappDigits = $countryCode.ltrim($whatsappDigits, '0');
    }
    $callNumber = preg_replace('/[^\d+]/', '', (string) $shop->phone);
    $generalMessage = match ($locale) {
        'he' => 'שלום, אשמח לקבל פרטים על השירותים של '.$shop->name.'.',
        'en' => 'Hello, I would like to ask about the services of '.$shop->name.'.',
        default => 'مرحباً، أرغب بالاستفسار عن خدمات '.$shop->name.'.',
    };
    $generalWhatsappUrl = $whatsappDigits ? 'https://wa.me/'.$whatsappDigits.'?text='.rawurlencode($generalMessage) : null;
    $socialProfiles = collect([
        ['label' => 'Facebook', 'icon' => 'ti-brand-facebook', 'value' => $social?->facebook, 'base' => 'https://facebook.com/'],
        ['label' => 'Instagram', 'icon' => 'ti-brand-instagram', 'value' => $social?->instagram, 'base' => 'https://instagram.com/'],
        ['label' => 'TikTok', 'icon' => 'ti-brand-tiktok', 'value' => $social?->tiktok, 'base' => 'https://tiktok.com/@'],
        ['label' => 'YouTube', 'icon' => 'ti-brand-youtube', 'value' => $social?->youtube, 'base' => 'https://youtube.com/@'],
        ['label' => 'Telegram', 'icon' => 'ti-brand-telegram', 'value' => $social?->telegram, 'base' => 'https://t.me/'],
        ['label' => 'Snapchat', 'icon' => 'ti-brand-snapchat', 'value' => $social?->snapchat, 'base' => 'https://snapchat.com/add/'],
    ])->filter(fn ($profile) => filled($profile['value']))->map(function ($profile) {
        $value = trim($profile['value']);
        $profile['url'] = preg_match('/^https?:\/\//i', $value) ? $value : $profile['base'].ltrim($value, '@/');
        return $profile;
    });
    if ($generalWhatsappUrl) $socialProfiles->push(['label' => 'WhatsApp', 'icon' => 'ti-brand-whatsapp', 'url' => $generalWhatsappUrl]);
    $hasStories = $shop->stories()->where('expires_at', '>', now())->exists();
    $youtubeEmbed = function (?string $url): ?string {
        return preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/shorts\/)([A-Za-z0-9_-]+)/', (string) $url, $match)
            ? 'https://www.youtube.com/embed/'.$match[1].'?mute=1&playsinline=1&rel=0' : null;
    };
    $categoryIcons = ['home' => 'ti-home', 'bath' => 'ti-bath', 'shield' => 'ti-shield', 'caravan' => 'ti-caravan', 'tools' => 'ti-tools'];
    $design = match ($locale) {
        'en' => ['title'=>'A space that fits your life.', 'intro'=>'Explore mobile homes, caravans and building solutions. Find your starting point, then discuss the details with the company.', 'explore'=>'Explore the collection', 'request'=>'Plan your project', 'collection'=>'Find your next space', 'hint'=>'Choose a category to explore the available models.', 'details'=>'Model details', 'brief'=>'Tell us what you have in mind', 'brief_hint'=>'Share your intended use, approximate dimensions and location to start a conversation about specifications and pricing.', 'use'=>'Building type', 'size'=>'Approximate dimensions / area', 'city'=>'Installation location', 'notes'=>'Your requirements', 'send'=>'Discuss my project on WhatsApp', 'faq'=>'Before you choose', 'q1'=>'What determines the price?', 'a1'=>'Ask the company for a quote based on dimensions, layout and finishes, and clarify whether transport and installation are included.', 'q2'=>'What should I prepare about the site?', 'a2'=>'Share the location, available space and access conditions. Confirm site preparation and permit requirements with the company and the relevant local authority.', 'q3'=>'Can I customize the layout?', 'a3'=>'Discuss your preferred rooms, openings and finishes with the company to confirm the options available for your model.'],
        'he' => ['title'=>'מרחב שמתאים לחיים שלכם.', 'intro'=>'גלו מבנים ניידים, קרוואנים ופתרונות בנייה. בחרו כיוון והמשיכו לתכנון הפרטים עם החברה.', 'explore'=>'גלו את הדגמים', 'request'=>'תכנון הפרויקט', 'collection'=>'מוצאים את המרחב הבא', 'hint'=>'בחרו קטגוריה לצפייה בדגמים הזמינים.', 'details'=>'פרטי הדגם', 'brief'=>'ספרו לנו על הפרויקט', 'brief_hint'=>'שתפו את השימוש הרצוי, המידות המשוערות והמיקום כדי לברר מפרט ומחיר.', 'use'=>'סוג המבנה', 'size'=>'מידות או שטח משוערים', 'city'=>'מיקום ההתקנה', 'notes'=>'דרישות נוספות', 'send'=>'בירור הפרויקט ב-WhatsApp', 'faq'=>'לפני שבוחרים', 'q1'=>'מה קובע את המחיר?', 'a1'=>'בקשו הצעה לפי מידות, חלוקה וגמר, ובררו האם הובלה והתקנה כלולות.', 'q2'=>'מה צריך לברר לגבי השטח?', 'a2'=>'שתפו מיקום, שטח פנוי ותנאי גישה. בררו הכנת שטח והיתרים מול החברה והרשות המקומית.', 'q3'=>'אפשר לשנות את החלוקה?', 'a3'=>'שתפו את החברה בחדרים, פתחים וגמר רצויים כדי לבדוק אפשרויות לדגם שבחרתם.'],
        default => ['title'=>'مساحة جديدة. على قياس حياتك.', 'intro'=>'بيوت متنقلة، كرافانات وحلول بناء. اكتشف الخيارات، وحدد فكرتك، واحكي مع الشركة عن التفاصيل اللي بتناسبك.', 'explore'=>'اكتشف النماذج', 'request'=>'خطّط لمشروعك', 'collection'=>'أي مساحة بتناسبك؟', 'hint'=>'اختار القسم وتعرّف على النماذج المتاحة.', 'details'=>'تفاصيل النموذج', 'brief'=>'خلّينا نعرف شو ببالك', 'brief_hint'=>'حدد الاستخدام والمقاس التقريبي والموقع، وابدأ محادثة مع الشركة حول المواصفات والسعر.', 'use'=>'نوع المبنى', 'size'=>'المقاس أو المساحة التقريبية', 'city'=>'موقع التركيب', 'notes'=>'متطلباتك وملاحظاتك', 'send'=>'ناقش مشروعي على واتساب', 'faq'=>'قبل ما تختار', 'q1'=>'شو اللي بحدد السعر؟', 'a1'=>'اطلب عرض سعر حسب المقاس والتقسيم والتشطيبات، وتأكد إذا النقل والتركيب داخلين بالسعر.', 'q2'=>'شو لازم أجهز عن الموقع؟', 'a2'=>'شارك الموقع والمساحة المتاحة وطريقة الوصول. اسأل الشركة والجهة المحلية المختصة عن تجهيز الأرض والتصاريح المطلوبة.', 'q3'=>'بقدر أغيّر التقسيم؟', 'a3'=>'احكي مع الشركة عن الغرف والفتحات والتشطيبات اللي بدك إياها، وتأكد من الخيارات المتاحة للنموذج اللي اخترته.'],
    };
    $projectImage = $shop->banner ? $mediaUrl($shop->banner) : $categories->first()?->imageUrl();
@endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    @include('front.partials.merchant_pwa_head', ['pwaShop' => $shop])
    @include('front.partials.seo', ['title' => $shop->name.' | Ozman', 'description' => $description, 'canonical' => $canonical, 'image' => $shopImage, 'schema' => ['@context' => 'https://schema.org', '@type' => 'Store', 'name' => $shop->name, 'url' => $canonical, 'description' => $description, 'image' => $shopImage, 'telephone' => $shop->phone, 'address' => $shop->address]])
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    @include('front.real_estate._company_design')
</head>
<body>
<main class="shell">
    <div class="company-hero-layout">
        <section class="company-display-screen" aria-label="{{ $copy['services'] }}">
            <div class="company-display-slider" data-display-slider>
                @forelse($displayItems as $item)
                    @php
                        $embed = $item->type === 'youtube' ? $youtubeEmbed($item->media) : null;
                    @endphp
                    <article class="company-display-slide {{ $loop->first ? 'active' : '' }}" data-duration="{{ max(1, (int) ($item->duration ?? 8)) * 1000 }}">
                        @if($item->type === 'video')<video src="{{ $mediaUrl($item->media) }}" muted playsinline loop preload="metadata"></video>
                        @elseif($embed)<iframe src="{{ $embed }}" title="{{ $item->title ?: $copy['services'] }}" allow="encrypted-media; picture-in-picture" allowfullscreen></iframe>
                        @else<img src="{{ $mediaUrl($item->media) }}" alt="{{ $item->title ?: $shop->name }}">@endif
                    </article>
                @empty
                    <article class="company-display-slide active {{ $projectImage ? '' : 'is-logo' }}" data-duration="10000"><img src="{{ $projectImage ?: $shopImage }}" alt="{{ $shop->name }}"></article>
                @endforelse
            </div>
            <div class="company-display-shade" aria-hidden="true"></div>
            @include('front.partials.display_sound_toggle')
        </section>
        <div class="project-intro">
            <span class="eyebrow">{{ $copy['services'] }}</span>
            <h1>{{ $design['title'] }}</h1>
            <p>{{ $shop->description ?: $design['intro'] }}</p>
            <div class="intro-actions"><a class="primary-link" href="#collection">{{ $design['explore'] }} <span aria-hidden="true">↙</span></a>@if($generalWhatsappUrl)<a class="secondary-link" href="#project">{{ $design['request'] }}</a>@endif</div>
        </div>
        <header class="hero">
            <div class="hero-tools">
                @include('front.partials.public_language_switcher')
                <a class="ozman-directory-link" href="{{ route('front.home') }}"><img src="{{ $ozmanLogo ? $mediaUrl($ozmanLogo) : asset('ozman-favicon.png') }}" alt="" aria-hidden="true"><span>{{ $copy['browse'] }}</span></a>
            </div>
            <div class="brand">
                <div class="logo-stack">
                    <button type="button" class="story-trigger {{ $hasStories ? 'has-story' : '' }}" data-shop-story-trigger data-story-shop-id="{{ $shop->id }}" aria-label="{{ $shop->name }}" @disabled(! $hasStories)><img class="shop-logo" src="{{ $logo }}" alt="{{ $shop->name }}"></button>
                    <strong class="shop-name">{{ $shop->name }}</strong><span class="availability">{{ $copy['available'] }}</span>
                    @if($generalWhatsappUrl)<a class="main-contact-btn" href="{{ $generalWhatsappUrl }}" target="_blank" rel="noopener noreferrer"><i class="ti ti-brand-whatsapp" aria-hidden="true"></i>{{ $copy['contact'] }}</a>@endif
                </div>
                @if($socialProfiles->isNotEmpty())<nav class="social-links" aria-label="{{ $copy['social'] }}">@foreach($socialProfiles as $profile)<a class="social-link" href="{{ $profile['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $profile['label'] }}" title="{{ $profile['label'] }}"><i class="ti {{ $profile['icon'] }}" aria-hidden="true"></i></a>@endforeach</nav>@endif
            </div>
        </header>
    </div>
    <div class="layout" id="collection"><section class="service-panel" aria-label="{{ $copy['sections'] }}">
        <div class="section-heading"><div><span class="eyebrow">{{ $copy['sections'] }}</span><h2>{{ $design['collection'] }}</h2></div><p>{{ $design['hint'] }}</p></div>
        @if($categories->isNotEmpty())
            <div class="service-browser">
                <nav class="category-rail" aria-label="{{ $copy['sections'] }}">
                    @foreach($categories as $category)
                        <button type="button" class="category-tab {{ $loop->first ? 'active' : '' }}" data-category-key="{{ $category->slug }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                            @if($category->imageUrl())<img src="{{ $category->imageUrl() }}" alt="" loading="lazy">@else<span class="category-icon"><i class="ti {{ $categoryIcons[$category->icon_key] ?? 'ti-building' }}" aria-hidden="true"></i></span>@endif
                            <span>{{ $category->localized('name') }}</span>
                        </button>
                    @endforeach
                </nav>
                <div class="category-content">
                    @foreach($categories as $category)
                        <section class="category-pane" data-category-pane="{{ $category->slug }}" @if(!$loop->first) hidden @endif>
                            @if($category->imageUrl())<img class="category-background" src="{{ $category->imageUrl() }}" alt="" loading="lazy">@endif
                            <div class="services">
                                @forelse($category->services as $service)
                                    @php
                                        $serviceName = $service->localized('name');
                                        $serviceMessage = match ($locale) {
                                            'he' => 'שלום, אשמח לקבל פרטים על '.$serviceName.' מ-'.$shop->name.'.',
                                            'en' => 'Hello, I would like details about '.$serviceName.' from '.$shop->name.'.',
                                            default => 'مرحباً، أريد الاستفسار عن '.$serviceName.' من '.$shop->name.'.',
                                        };
                                        $serviceWhatsappUrl = $whatsappDigits ? 'https://wa.me/'.$whatsappDigits.'?text='.rawurlencode($serviceMessage) : null;
                                    @endphp
                                    @php
                                        $gallery = collect([$service->imageUrl()])->merge($service->images->map(fn ($image) => str_starts_with($image->path, 'images/') ? asset($image->path) : \Illuminate\Support\Facades\Storage::url($image->path)))->filter()->unique()->values();
                                    @endphp
                                    <article class="service-card" data-service-key="{{ $service->slug }}">
                                        <div class="service-image" data-gallery='@json($gallery)'>@if($service->imageUrl())<img src="{{ $service->imageUrl() }}" alt="{{ $serviceName }}" loading="lazy">@else<i class="ti {{ $categoryIcons[$category->icon_key] ?? 'ti-building' }}" aria-hidden="true"></i>@endif
                                            @if($gallery->count() > 1)<div class="gallery-controls"><button type="button" data-step="-1" aria-label="{{ $locale === 'ar' ? 'الصورة السابقة' : ($locale === 'he' ? 'התמונה הקודמת' : 'Previous image') }}">‹</button><output aria-live="polite">1 / {{ $gallery->count() }}</output><button type="button" data-step="1" aria-label="{{ $locale === 'ar' ? 'الصورة التالية' : ($locale === 'he' ? 'התמונה הבאה' : 'Next image') }}">›</button></div>@endif
                                        </div>
                                        <div class="service-body"><h2>{{ $serviceName }}</h2><p>{{ $service->localized('short_description') ?: $copy['service_hint'] }}</p><div class="service-bottom">
                                            @if($serviceWhatsappUrl)<a class="service-contact" href="{{ $serviceWhatsappUrl }}" target="_blank" rel="noopener noreferrer"><i class="ti ti-brand-whatsapp" aria-hidden="true"></i>{{ $copy['contact'] }}</a>
                                            @elseif($callNumber)<a class="service-contact is-call" href="tel:{{ $callNumber }}"><i class="ti ti-phone" aria-hidden="true"></i>{{ $copy['call'] }}</a>@endif
                                        </div>@if($service->localized('description'))<details><summary>{{ $design['details'] }}</summary><p>{{ $service->localized('description') }}</p></details>@endif</div>
                                    </article>
                                @empty<div class="empty"><i class="ti ti-building-off" aria-hidden="true"></i>{{ $copy['empty_section'] }}</div>@endforelse
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>
        @else<div class="empty"><i class="ti ti-building-off" aria-hidden="true"></i>{{ $copy['empty'] }}</div>@endif
    </section></div>
    @if($generalWhatsappUrl)
    <section class="project-brief" id="project"><div><span class="eyebrow" style="color:#dec7a1">{{ $design['request'] }}</span><h2>{{ $design['brief'] }}</h2><p>{{ $design['brief_hint'] }}</p></div>
        <form class="project-form" data-project-form data-contact="{{ $generalWhatsappUrl }}">
            <label>{{ $design['use'] }}<select name="type">@foreach($categories as $category)<option value="{{ $category->localized('name') }}">{{ $category->localized('name') }}</option>@endforeach</select></label>
            <label>{{ $design['size'] }}<input name="size" maxlength="120"></label>
            <label class="wide">{{ $design['city'] }}<input name="city" maxlength="120" autocomplete="address-level2"></label>
            <label class="wide">{{ $design['notes'] }}<textarea name="notes" maxlength="1500" rows="3"></textarea></label>
            <button class="wide" type="submit">{{ $design['send'] }} ↗</button>
        </form>
    </section>
    @endif
    <section class="buyer-faq"><h2>{{ $design['faq'] }}</h2>@foreach([1,2,3] as $number)<details><summary>{{ $design['q'.$number] }}</summary><p>{{ $design['a'.$number] }}</p></details>@endforeach</section>
    <footer class="company-footer"><strong>{{ $shop->name }}</strong><nav>@if($callNumber)<a href="tel:{{ $callNumber }}">{{ $copy['call'] }}</a>@endif @foreach($socialProfiles as $profile)<a href="{{ $profile['url'] }}" target="_blank" rel="noopener noreferrer">{{ $profile['label'] }}</a>@endforeach</nav>@if($shop->address)<span>{{ $shop->address }}</span>@endif</footer>
</main>
@include('front.shop_stories', ['showStoryList' => false])
<script>
(() => {
    document.querySelectorAll('[data-gallery]').forEach(gallery => {
        const images = JSON.parse(gallery.dataset.gallery); let index = 0;
        gallery.querySelectorAll('[data-step]').forEach(button => button.addEventListener('click', () => {
            index = (index + Number(button.dataset.step) + images.length) % images.length;
            gallery.querySelector('img').src = images[index];
            gallery.querySelector('output').textContent = `${index + 1} / ${images.length}`;
        }));
    });
    document.querySelector('[data-project-form]')?.addEventListener('submit', event => {
        event.preventDefault();
        const form = event.currentTarget, url = new URL(form.dataset.contact);
        const lines = [...form.querySelectorAll('input,select,textarea')].filter(input => input.value.trim()).map(input => `${input.closest('label').firstChild.textContent.trim()}: ${input.value.trim()}`);
        url.searchParams.set('text', url.searchParams.get('text') + '\n' + lines.join('\n'));
        window.location.assign(url.href);
    });
    const slider = document.querySelector('[data-display-slider]');
    const slides = [...(slider?.querySelectorAll('.company-display-slide') || [])];
    let slideIndex = 0;
    const showSlide = next => { slides[slideIndex]?.querySelector('video')?.pause(); slides[slideIndex]?.classList.remove('active'); slideIndex = next; slides[slideIndex]?.classList.add('active'); slides[slideIndex]?.querySelector('video')?.play().catch(() => {}); };
    slides[0]?.querySelector('video')?.play().catch(() => {});
    if (slides.length > 1) { const schedule = () => window.setTimeout(() => { showSlide((slideIndex + 1) % slides.length); schedule(); }, Number(slides[slideIndex]?.dataset.duration) || 8000); schedule(); }
    const tabs = [...document.querySelectorAll('[data-category-key]')];
    const panes = [...document.querySelectorAll('[data-category-pane]')];
    const selectCategory = (key, updateUrl = false) => {
        if (!tabs.some(tab => tab.dataset.categoryKey === key)) key = tabs[0]?.dataset.categoryKey;
        if (!key) return;
        tabs.forEach(tab => { const active = tab.dataset.categoryKey === key; tab.classList.toggle('active', active); tab.setAttribute('aria-pressed', String(active)); });
        panes.forEach(pane => pane.hidden = pane.dataset.categoryPane !== key);
        if (updateUrl) { const url = new URL(window.location.href); url.searchParams.set('category', key); url.searchParams.delete('service'); history.replaceState({}, '', url); }
    };
    tabs.forEach(tab => tab.addEventListener('click', () => selectCategory(tab.dataset.categoryKey, true)));
    const params = new URLSearchParams(window.location.search);
    selectCategory(params.get('category') || tabs[0]?.dataset.categoryKey);
    const requestedService = params.get('service');
    if (requestedService) window.setTimeout(() => document.querySelector(`[data-service-key="${CSS.escape(requestedService)}"]`)?.scrollIntoView({ block: 'center' }), 100);
})();
</script>
</body>
</html>
