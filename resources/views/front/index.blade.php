<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ozman</title>
    <link rel="stylesheet" href="{{ asset('style.css') }}">
    <!-- Font Awesome icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800;900&display=swap" rel="stylesheet">
    <style>.logo-dropdown > .dropdown-item:nth-child(n+3){display:none}</style>
    
</head>

<body>
    @php
        $shopName = $shop?->name ?? 'Ozman';
        $shopLogo = $shop?->logo ? asset($shop->logo) : asset('images/logo.jpg');
        $ozmanName = $ozmanShop?->name ?? 'Ozman';
        $ozmanLogo = $ozmanShop?->logo ? asset($ozmanShop->logo) : asset('images/logo.jpg');
        $ozmanWelcomeText = 'أهلا بك في Ozman - اكتشف فئاتنا ومنتجاتنا المميزة';
        $welcomeText = "أهلا بك في {$shopName} - اكتشف أقسام ومنتجات المتجر";
        $social = optional($shop?->social);
        $socialLinks = [
            ['title' => 'فيسبوك', 'icon' => 'fab fa-facebook-f', 'url' => $social->facebook],
            ['title' => 'تويتر', 'icon' => 'fab fa-twitter', 'url' => $social->twitter],
            ['title' => 'انستجرام', 'icon' => 'fab fa-instagram', 'url' => $social->instagram],
            ['title' => 'تيك توك', 'icon' => 'fab fa-tiktok', 'url' => $social->tiktok],
            ['title' => 'تلجرام', 'icon' => 'fab fa-telegram', 'url' => $social->telegram],
        ];
    @endphp

    @unless($isDashboardPreview ?? false)
    <header>
        <div class="header-right-group">
            <div class="logo-container">
                <div class="logo">
                    <img src="{{ $ozmanLogo }}" alt="{{ $ozmanName }} Logo" class="logo-img">
                    <div class="logo-dropdown">
                        @include('front.logo_dropdown')
                    </div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <div class="location-btn location-btn-trigger">
                        <i class="fas fa-map-marker-alt"></i> حدد موقعك
                    </div>
                    <a href="{{ route('lang.switch', app()->getLocale() === 'ar' ? 'he' : 'ar') }}" class="location-btn" style="text-decoration: none;">
                        <i class="fas fa-globe"></i> العربية
                    </a>
                </div>
            </div>
            <!-- Social icons -->
            <div class="social-icons-vertical">
                <div class="social-icon" title="فيسبوك"><i class="fab fa-facebook-f"></i></div>
                <div class="social-icon" title="تويتر"><i class="fab fa-twitter"></i></div>
                <div class="social-icon" title="انستجرام"><i class="fab fa-instagram"></i></div>
                <div class="social-icon" title="تيك توك"><i class="fab fa-tiktok"></i></div>
                <div class="social-icon" title="تلجرام"><i class="fab fa-telegram"></i></div>
            </div>
        </div>

        <div class="display-screen glass" style="margin-left: -100px;">
            <div class="story-slider">
                <span class="welcome-msg">{{ $ozmanWelcomeText }}</span>
                <span class="welcome-msg">{{ $ozmanWelcomeText }}</span>
            </div>
        </div>
    </header>

    <main>
        <!-- Top Products Carousel Section -->
        <section class="carousel-3d-section animate">
            <h2
                style="color: var(--primary-color); margin-bottom: 20px; font-weight: 900; text-shadow: 0 0 10px var(--primary-color);">
                فئات Ozman</h2>
            <div class="carousel-3d-container" id="carouselProducts">
                @forelse($ozmanCategories as $category)
                    <div class="carousel-item-3d prod-item" data-index="{{ $loop->index }}" data-ozman-category="{{ $category->name }}" data-product-name="{{ $category->name }}">
                        <div class="card-3d">
                            <img src="{{ $category->image ? asset($category->image) : asset('images/logo.jpg') }}" alt="{{ $category->name }}">
                        </div>
                        <span>{{ $category->name }}</span>
                    </div>
                @empty
                    <div class="carousel-item-3d prod-item" data-index="0" data-product-name="Ozman">
                        <div class="card-3d"><img src="{{ asset('images/logo.jpg') }}" alt="Ozman"></div>
                        <span>Ozman</span>
                    </div>
                @endforelse
            </div>
        </section>

        <hr class="section-divider">
    @else
    <main>
    @endunless

        <!-- Infinite Vertical Carousel Section -->
        <header>
            <div class="header-right-group">
                <div class="logo-container">
                <div class="logo">
                    <img src="{{ $shopLogo }}" alt="{{ $shopName }} Logo" class="logo-img">
                    <div class="logo-dropdown">
                        @include('front.logo_dropdown')
                    </div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <div class="location-btn location-btn-trigger">
                        <i class="fas fa-map-marker-alt"></i> حدد موقعك
                    </div>
                    <a href="{{ route('lang.switch', app()->getLocale() === 'ar' ? 'he' : 'ar') }}" class="location-btn" style="text-decoration: none;">
                        <i class="fas fa-globe"></i> العربية
                    </a>
                </div>
            </div>
                <!-- Social icons -->
                <div class="social-icons-vertical">
                    <div class="social-icon" title="فيسبوك"><i class="fab fa-facebook-f"></i></div>
                    <div class="social-icon" title="تويتر"><i class="fab fa-twitter"></i></div>
                    <div class="social-icon" title="انستجرام"><i class="fab fa-instagram"></i></div>
                    <div class="social-icon" title="تيك توك"><i class="fab fa-tiktok"></i></div>
                    <div class="social-icon" title="تلجرام"><i class="fab fa-telegram"></i></div>
                </div>
            </div>

            <div class="display-screen glass" style="margin-left: -100px;">
                <div class="story-slider">
                    <span class="welcome-msg">{{ $welcomeText }}</span>
                    <span class="welcome-msg">{{ $welcomeText }}</span>
                </div>
            </div>
        </header>


        <hr class="section-divider">

        <!-- Radial Category Selection Section -->
        <section class="radial-section animate" style="margin-right: 60px; margin-bottom: 60px;">
            <div class="side-nav-vertical v-carousel-container" id="sideVCarousel">
                <div class="side-circles-list v-carousel-track" id="sideVTrack">
                    <!-- Items will be generated by JS -->
                </div>
            </div>

            <div class="radial-container" style="position: relative;">
                <!-- Floating Info/Header Panel when showing scattered products -->
                <div class="products-scatter-header" id="productsScatterHeader"
                    style="display: none; flex-direction: column; gap: 15px; width: 100%; position: absolute; top: -10px; left: 0; padding: 0 40px; z-index: 30; direction: rtl;">
                    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                        <h3 id="productsScatterTitle"
                            style="padding: 12px 30px; border-radius: 20px; color: var(--primary-color); border: 1px solid var(--glass-border); background: rgba(0,0,0,0.85); margin: 0; font-size: 1.4rem; font-weight: 900; box-shadow: 0 0 15px rgba(0, 229, 255, 0.2);">
                            اسم القسم</h3>
                        <button class="back-btn" id="backToDeptsBtn" style="direction: rtl;">
                            <i class="fas fa-chevron-right" style="margin-left: 8px;"></i> عودة للأقسام
                        </button>
                    </div>
                    <!-- Product summary -->
                    <div id="productsScatterDesc"
                        style="display: none; width: max-content; max-width: 80%; padding: 10px 25px; border-radius: 15px; background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.08); color: rgba(255,255,255,0.85); font-size: 0.95rem; line-height: 1.5; box-shadow: 0 5px 15px rgba(0,0,0,0.2); animation: fadeInUp 0.4s ease; text-align: right;">
                    </div>
                </div>

                <div class="watch-grid-wrapper" id="watchGridWrapper" style="width: 100%; height: 650px;">
                    <div class="watch-grid-container" id="watchGridContainer">
                        <div class="watch-grid-track" id="watchGridTrack">
                            <!-- Items will be generated by JS -->
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <nav class="bottom-nav">
        <div class="nav-icons">
            <div class="nav-btn" id="navHomeBtn" title="الرئيسية"><i class="fas fa-home"></i></div>
            <div class="nav-btn" id="navSearchBtn" title="البحث"><i class="fas fa-search"></i></div>
            <div class="nav-btn" id="navCartBtn" title="سلة المشتريات"><i class="fas fa-shopping-cart"></i></div>
            <!-- Chatbot and WhatsApp actions -->
            <div class="nav-btn" id="chatbotToggleBtn" title="المساعد الذكي" style="position: relative;">
                <i class="fas fa-comments"></i>
                <span
                    style="position: absolute; top: -3px; right: -3px; width: 8px; height: 8px; background: var(--primary-color); border-radius: 50%; box-shadow: 0 0 5px var(--primary-color);"></span>
            </div>
            <a href="https://wa.me/{{ preg_replace('/\D+/', '', $shop?->whatsapp ?: $shop?->phone ?: '970599000000') }}" target="_blank" class="nav-btn" id="whatsappQuickBtn"
                title="تواصل مباشرة عبر واتساب"
                style="color: #25d366; text-decoration: none; display: flex; align-items: center;">
                <i class="fab fa-whatsapp"></i>
            </a>
        </div>
        <div class="buy-btn">اطلب الآن</div>
    </nav>

    <script>
        window.OZMAN_FRONT_DATA = @json($frontData);
    </script>
    <script src="{{ asset('script.js') }}"></script>

    <!-- Location Modal -->
    <div class="modal-overlay" id="locationModal">
        <div class="modal-content glass">
            <div class="modal-header">
                <h3><i class="fas fa-map-marker-alt"></i> تحديد الموقع</h3>
                <span class="close-modal" id="closeLocationModal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="map-placeholder">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d13606.50284483733!2d34.46089335!3d31.50655845!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2s!4v1716035123456!5m2!1sen!2s"
                        width="100%" height="250" style="border:0; border-radius:15px;" allowfullscreen=""
                        loading="lazy"></iframe>
                </div>
                <div class="location-actions">
                    <button class="buy-btn" style="width: 100%; margin-top: 15px;" id="confirmLocationBtn">تأكيد الموقع</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Gallery Modal -->
    <div class="product-gallery-modal" id="productGalleryModal">
        <div class="product-modal-card">
            <span class="product-modal-close" id="closeProductModal">&times;</span>

            <!-- Left Side: Interactive Gallery -->
            <div class="product-gallery-container">
                <div class="main-gallery-view">
                    <img id="modalMainImg" src="" alt="Product Main View">
                </div>
                <div class="thumbnails-row" id="modalThumbnailsRow">
                    <!-- Thumbnails generated dynamically by JS -->
                </div>
            </div>

            <!-- Right Side: Details & Actions -->
            <div class="product-modal-info">
                <div>
                    <h2 class="product-modal-title" id="modalProductTitle">اسم المنتج</h2>
                    <div class="product-modal-price" id="modalProductPrice">75 شيكل</div>
                    <p class="product-modal-description" id="modalProductDesc">
                        منتج مميز من متجر Ozman.
                    </p>
                    <ul class="product-features-list">
                        <!-- Will be generated by JS -->
                    </ul>
                </div>

                <div class="product-modal-actions">
                    <div class="qty-selector">
                        <button class="qty-btn" id="qtyMinus"><i class="fas fa-minus"></i></button>
                        <span class="qty-val" id="qtyVal">1</span>
                        <button class="qty-btn" id="qtyPlus"><i class="fas fa-plus"></i></button>
                    </div>
                    <div class="modal-action-buttons">
                        <button class="modal-btn-cart" id="modalAddToCartBtn">أضف إلى السلة</button>
                        <button class="modal-btn-whatsapp" id="modalWhatsappBtn">
                            <i class="fab fa-whatsapp"></i> اطلب عبر واتساب
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Premium Glassmorphic Chatbot Widget -->
    <div class="chatbot-widget active" id="chatbotWidget">
        <div class="chatbot-header">
            <button class="chatbot-close" id="closeChatbotBtn">
                <i class="fas fa-chevron-right"></i> عودة
            </button>
            <div class="chatbot-info">
                <div class="chatbot-avatar">
                    <i class="fas fa-robot"></i>
                    <span class="online-indicator"></span>
                </div>
                <h4>مساعد Ozman الذكي</h4>
                <p>متصل الآن</p>
            </div>
            <!-- Blank div to balance flexbox layout for centered info -->
            <div style="width: 55px;"></div>
        </div>

        <div class="chatbot-messages" id="chatbotMessages">
            <div class="chat-message bot">
                أهلا بك في Ozman! أنا مساعدك الذكي. كيف يمكنني مساعدتك اليوم؟
            </div>
            <!-- Quick Options / Smart Suggestion Tags -->
            <div class="chat-options-container">
                <button class="chat-option-btn" data-reply="جسم">منتجات العناية بالجسم</button>
                <button class="chat-option-btn" data-reply="شعر">منتجات العناية بالشعر</button>
                <button class="chat-option-btn" data-reply="وجه">منتجات العناية بالوجه</button>
                <button class="chat-option-btn" data-reply="طلب">كيف أقوم بالطلب والتوصيل؟</button>
                <button class="chat-option-btn" data-reply="دعم">التحدث مباشرة مع الدعم</button>
            </div>
        </div>

        <div class="chatbot-input-area">
            <button class="imessage-plus-btn"><i class="fas fa-plus"></i></button>
            <div class="imessage-input-wrapper">
                <input type="text" id="chatbotInput" placeholder="اكتب رسالتك" dir="rtl">
                <button id="chatbotSendBtn" class="chatbot-send-btn">
                    <i class="fas fa-arrow-up"></i>
                </button>
            </div>
        </div>
    </div>
</body>

</html>

