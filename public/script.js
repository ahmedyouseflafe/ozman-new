
function initMediaStorySlider(slider) {
        const slides = Array.from(slider.querySelectorAll('.media-story-slide'));
        if (slides.length <= 1) {
            const video = slides[0]?.querySelector('video');
            if (video) {
                video.play().catch(() => {});
            }
            return;
        }

        let index = Math.max(0, slides.findIndex((slide) => slide.classList.contains('active')));

        const activateSlide = (nextIndex) => {
            slides[index]?.classList.remove('active');
            slides[index]?.querySelectorAll('video').forEach((video) => video.pause());

            index = nextIndex % slides.length;
            const activeSlide = slides[index];
            activeSlide.classList.add('active');
            activeSlide.querySelectorAll('video').forEach((video) => {
                video.currentTime = 0;
                video.play().catch(() => {});
            });

            window.setTimeout(() => activateSlide(index + 1), Number(activeSlide.dataset.duration || 8000));
        };

        activateSlide(index);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-media-story]').forEach((slider) => {
        initMediaStorySlider(slider);
    });
});

        // --- Radial Section Logic ---
        // --- Radial Section Logic ---
        const ozmanFrontData = window.OZMAN_FRONT_DATA || {};
        const centersData = ozmanFrontData.centersData || [
            {
                title: 'متجر Ozman',
                img: 'images/logo.jpg',
                departments: [
                    { title: 'العناية بالجسم', img: 'images/logo.jpg' },
                    { title: 'العناية بالشعر', img: 'images/logo.jpg' },
                    { title: 'العناية بالوجه', img: 'images/logo.jpg' }
                ]
            }
        ];

        let activeCenterIndex = 0;
        let watchItemsElements = [];
        let activeDeptTitle = '';
        let activeProductTitle = '';
        let activePersonContext = null;

        const carouselProductsDb = ozmanFrontData.carouselProductsDb || {
            'منتج تجريبي': {
                name: 'منتج تجريبي',
                price: '0',
                img: 'images/logo.jpg',
                gallery: ['images/logo.jpg']
            }
        };

        const productsDb = ozmanFrontData.productsDb || {
            'العناية بالجسم': [
                { name: 'منتج تجريبي', price: '0', img: 'images/logo.jpg', gallery: ['images/logo.jpg'] }
            ]
        };
        let activeProductsDb = productsDb;

        const CART_STORAGE_KEY = 'ozman_front_cart';
        const CUSTOMER_STORAGE_KEY = 'ozman_customer_profile';
        const CUSTOMER_LOCATION_STORAGE_KEY = 'ozman_customer_location';
        const REFRESH_LOCATION_REQUEST_KEY = 'ozman_refresh_location_request';
        const VISITOR_REGISTRATION_STORAGE_KEY = 'ozman_visitor_registration_done_v2';
        const VISITOR_TYPE_STORAGE_KEY = 'ozman_visitor_type';
        const REWARD_DISCOUNT_STORAGE_KEY = 'ozman_customer_signup_reward';
        const PURCHASE_REWARD_STORAGE_PREFIX = 'ozman_purchase_reward_';
        const PURCHASE_UNLOCK_STORAGE_PREFIX = 'ozman_purchase_unlock_';
        const PENDING_PURCHASE_ORDER_STORAGE_KEY = 'ozman_pending_purchase_order';
        const LAST_REWARD_ORDER_STORAGE_KEY = 'ozman_last_reward_order';
        const LAST_REWARD_PAYLOAD_STORAGE_KEY = 'ozman_last_reward_payload';
        let activeWhatsappNumber = (window.OZMAN_FRONT_CONFIG?.shopWhatsapp || '970599000000').replace(/\D+/g, '');
        let ozmanCart = loadCart();
        let cartToastTimer = null;
        let pendingSingleProduct = null;
        let pendingUnitChoice = null;
        let pendingPurchaseOrder = loadPendingPurchaseOrder();
        let pendingPurchaseOrderPromise = null;
        let lastRewardOrder = loadLastRewardOrder() || pendingPurchaseOrder;
        let lastRewardPayload = loadLastRewardPayload();

        function loadCart() {
            try {
                const parsed = JSON.parse(localStorage.getItem(CART_STORAGE_KEY) || '[]');
                return Array.isArray(parsed) ? parsed : [];
            } catch (error) {
                return [];
            }
        }

        function saveCart() {
            localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(ozmanCart));
        }

        function clearCartState() {
            ozmanCart = [];
            saveCart();
            localStorage.removeItem(REWARD_DISCOUNT_STORAGE_KEY);
        }

        function shopWhatsappNumber() {
            return activeWhatsappNumber || (window.OZMAN_FRONT_CONFIG?.shopWhatsapp || '970599000000').replace(/\D+/g, '');
        }

        function updateWhatsappRecipient(target = {}) {
            const fallback = (window.OZMAN_FRONT_CONFIG?.shopWhatsapp || '970599000000').replace(/\D+/g, '');
            const nextNumber = String(target.whatsapp_number || fallback).replace(/\D+/g, '');
            activeWhatsappNumber = nextNumber || fallback;

            const quickBtn = document.getElementById('whatsappQuickBtn');
            if (quickBtn) {
                quickBtn.href = `https://wa.me/${shopWhatsappNumber()}`;
            }
        }

        function openShopWhatsappMessage(message, popupWindow = null) {
            const url = `https://wa.me/${shopWhatsappNumber()}?text=${encodeURIComponent(message)}`;

            if (popupWindow && !popupWindow.closed) {
                popupWindow.location.href = url;
                return;
            }

            window.open(url, '_blank');
        }

        function frontLabel(key, fallback) {
            return window.OZMAN_FRONT_CONFIG?.labels?.[key] || fallback;
        }

        function frontLabelTemplate(key, fallback, replacements = {}) {
            return Object.entries(replacements).reduce(
                (text, [name, value]) => text.replaceAll(`:${name}`, value),
                frontLabel(key, fallback)
            );
        }

        function loadCustomerProfile() {
            try {
                const parsed = JSON.parse(localStorage.getItem(CUSTOMER_STORAGE_KEY) || '{}');
                return parsed && typeof parsed === 'object' ? parsed : {};
            } catch (error) {
                return {};
            }
        }

        function saveCustomerProfile(profile) {
            localStorage.setItem(CUSTOMER_STORAGE_KEY, JSON.stringify(profile || {}));
        }

        function loadCustomerLocation() {
            try {
                const parsed = JSON.parse(localStorage.getItem(CUSTOMER_LOCATION_STORAGE_KEY) || '{}');
                const latitude = Number(parsed.latitude);
                const longitude = Number(parsed.longitude);
                return Number.isFinite(latitude) && Number.isFinite(longitude)
                    ? {
                        latitude,
                        longitude,
                        accuracy: Number.isFinite(Number(parsed.accuracy)) ? Number(parsed.accuracy) : null
                    }
                    : null;
            } catch (error) {
                return null;
            }
        }

        function saveCustomerLocation(location) {
            if (!location) return;
            localStorage.setItem(CUSTOMER_LOCATION_STORAGE_KEY, JSON.stringify({
                latitude: location.latitude,
                longitude: location.longitude,
                accuracy: location.accuracy ?? null,
                saved_at: new Date().toISOString()
            }));
        }

        function clearCustomerLocation() {
            localStorage.removeItem(CUSTOMER_LOCATION_STORAGE_KEY);
        }

        function loadPendingPurchaseOrder() {
            try {
                const parsed = JSON.parse(localStorage.getItem(PENDING_PURCHASE_ORDER_STORAGE_KEY) || '{}');
                return parsed && typeof parsed === 'object' && parsed.order_id ? parsed : null;
            } catch (error) {
                return null;
            }
        }

        function loadLastRewardOrder() {
            try {
                const parsed = JSON.parse(localStorage.getItem(LAST_REWARD_ORDER_STORAGE_KEY) || '{}');
                return parsed && typeof parsed === 'object' && parsed.order_number ? parsed : null;
            } catch (error) {
                return null;
            }
        }

        function saveLastRewardOrder(order) {
            lastRewardOrder = order && order.order_number ? order : null;
            if (lastRewardOrder) {
                localStorage.setItem(LAST_REWARD_ORDER_STORAGE_KEY, JSON.stringify(lastRewardOrder));
            }
        }

        function loadLastRewardPayload() {
            try {
                const parsed = JSON.parse(localStorage.getItem(LAST_REWARD_PAYLOAD_STORAGE_KEY) || '{}');
                return parsed && typeof parsed === 'object' && parsed.label ? parsed : null;
            } catch (error) {
                return null;
            }
        }

        function saveLastRewardPayload(reward) {
            lastRewardPayload = reward && reward.label ? reward : null;
            if (lastRewardPayload) {
                localStorage.setItem(LAST_REWARD_PAYLOAD_STORAGE_KEY, JSON.stringify(lastRewardPayload));
            }
        }

        function savePendingPurchaseOrder(order) {
            pendingPurchaseOrder = order && order.order_id ? order : null;
            if (pendingPurchaseOrder) {
                localStorage.setItem(PENDING_PURCHASE_ORDER_STORAGE_KEY, JSON.stringify(pendingPurchaseOrder));
            } else {
                localStorage.removeItem(PENDING_PURCHASE_ORDER_STORAGE_KEY);
            }
        }

        function trackPendingPurchaseOrderPromise(orderPromise) {
            pendingPurchaseOrderPromise = orderPromise;
            orderPromise.finally(() => {
                if (pendingPurchaseOrderPromise === orderPromise) {
                    pendingPurchaseOrderPromise = null;
                }
            });
        }

        function rewardWheelConfig() {
            const wheel = window.OZMAN_FRONT_CONFIG?.rewardWheel;
            const segments = Array.isArray(wheel?.segments) ? wheel.segments : [];
            if (!wheel || segments.length < 2) return null;

            return {
                title: wheel.title || 'لف العجلة واحصل على خصمك الأول',
                segments
            };
        }

        function loadRewardDiscount() {
            try {
                const reward = JSON.parse(localStorage.getItem(REWARD_DISCOUNT_STORAGE_KEY) || '{}');
                return reward && typeof reward === 'object' && reward.label ? reward : null;
            } catch (error) {
                return null;
            }
        }

        function saveRewardDiscount(reward) {
            localStorage.setItem(REWARD_DISCOUNT_STORAGE_KEY, JSON.stringify(reward || {}));
        }

        function storedReward(storageKey) {
            try {
                const reward = JSON.parse(localStorage.getItem(storageKey) || '{}');
                return reward && typeof reward === 'object' && reward.label ? reward : null;
            } catch (error) {
                return null;
            }
        }

        function purchaseRewardWheels() {
            return Array.isArray(window.OZMAN_FRONT_CONFIG?.purchaseRewardWheels)
                ? window.OZMAN_FRONT_CONFIG.purchaseRewardWheels
                : [];
        }

        function visitorCanUsePurchaseWheels() {
            return currentVisitorType() !== 'merchant';
        }

        function eligiblePurchaseRewardWheel(total) {
            if (!visitorCanUsePurchaseWheels()) return null;

            return purchaseRewardWheels().find((wheel) => {
                const min = Number(wheel.min_order_total || 0);
                const max = wheel.max_order_total === null || wheel.max_order_total === undefined || wheel.max_order_total === ''
                    ? Number.POSITIVE_INFINITY
                    : Number(wheel.max_order_total);

            return total >= min && total <= max && Array.isArray(wheel.segments) && wheel.segments.length >= 2;
        }) || null;
        }

        function purchaseWheelRangeText(wheel) {
            const min = Number(wheel?.min_order_total || 0);
            const hasMax = wheel?.max_order_total !== null && wheel?.max_order_total !== undefined && wheel?.max_order_total !== '';
            const minText = formatCartPrice(min);

            if (!hasMax) {
                return `من ${minText} فما فوق`;
            }

            return `من ${minText} إلى ${formatCartPrice(Number(wheel.max_order_total || 0))}`;
        }

        function purchaseWheelGradient(wheel) {
            const segments = Array.isArray(wheel?.segments) ? wheel.segments : [];
            if (segments.length < 2) return '#00e5ff 0deg 180deg, #7000ff 180deg 360deg';

            const step = 360 / segments.length;
            return segments.map((segment, index) => {
                const start = Math.round(index * step);
                const end = Math.round((index + 1) * step);
                return `${segment.color || '#00e5ff'} ${start}deg ${end}deg`;
            }).join(', ');
        }

        function purchaseWheelSegmentBadges(wheel) {
            const segments = Array.isArray(wheel?.segments) ? wheel.segments.slice(0, 6) : [];
            if (segments.length === 0) return '';

            const step = 360 / segments.length;
            return segments.map((segment, index) => {
                const label = segment.label || 'جائزة';
                const angle = (index * step) + (step / 2);
                const image = segment.gift_image
                    ? `<img src="${escapeCartHtml(segment.gift_image)}" alt="${escapeCartHtml(label)}">`
                    : `<span>${escapeCartHtml(label.trim().charAt(0) || 'ج')}</span>`;

                return `<em class="purchase-wheel-prize" style="--prize-angle:${angle}deg;--prize-color:${escapeCartHtml(segment.color || '#00e5ff')}">${image}</em>`;
            }).join('');
        }

        function purchaseWheelStorageKey(wheel) {
            return `${PURCHASE_REWARD_STORAGE_PREFIX}${wheel?.id || ''}`;
        }

        function purchaseWheelUnlockKey(wheel) {
            return `${PURCHASE_UNLOCK_STORAGE_PREFIX}${wheel?.id || ''}`;
        }

        function isPurchaseWheelUnlocked(wheel) {
            try {
                const unlocked = JSON.parse(localStorage.getItem(purchaseWheelUnlockKey(wheel)) || '{}');
                return Boolean(unlocked && unlocked.wheel_id === wheel?.id && unlocked.unlocked_at);
            } catch (error) {
                return false;
            }
        }

        function unlockPurchaseWheel(wheel, total) {
            if (!wheel) return;
            localStorage.setItem(purchaseWheelUnlockKey(wheel), JSON.stringify({
                wheel_id: wheel.id,
                total,
                unlocked_at: new Date().toISOString()
            }));
            updatePurchaseWheelStates();
        }

        function resetAllPurchaseWheelSessions() {
            purchaseRewardWheels().forEach((wheel) => {
                localStorage.removeItem(purchaseWheelStorageKey(wheel));
                localStorage.removeItem(purchaseWheelUnlockKey(wheel));
            });
            updatePurchaseWheelStates();
        }

        function hasPurchaseWheelSession() {
            return purchaseRewardWheels().some((wheel) => (
                Boolean(localStorage.getItem(purchaseWheelStorageKey(wheel)))
                || Boolean(localStorage.getItem(purchaseWheelUnlockKey(wheel)))
            ));
        }

        function updatePurchaseWheelStates() {
            const container = document.getElementById('purchaseWheelsCarousel');
            const track = document.getElementById('purchaseWheelsTrack');
            if (!track) return;

            if (!visitorCanUsePurchaseWheels()) {
                if (container) container.hidden = true;
                track.innerHTML = '';
                return;
            }

            track.querySelectorAll('[data-purchase-wheel-index]').forEach((button) => {
                const wheel = purchaseRewardWheels()[Number(button.dataset.purchaseWheelIndex)];
                if (!wheel) return;

                const isUnlocked = isPurchaseWheelUnlocked(wheel);
                const isWon = Boolean(storedReward(purchaseWheelStorageKey(wheel)));
                const status = button.querySelector('[data-purchase-wheel-status]');
                const lockIcon = button.querySelector('[data-purchase-wheel-lock]');

                button.classList.toggle('is-eligible', isUnlocked && !isWon);
                button.classList.toggle('is-locked', !isUnlocked && !isWon);
                button.classList.toggle('is-won', isWon);
                button.setAttribute('aria-disabled', isUnlocked && !isWon ? 'false' : 'true');

                if (lockIcon) {
                    lockIcon.innerHTML = isWon
                        ? '<i class="fas fa-check"></i>'
                        : (isUnlocked ? '<i class="fas fa-lock-open"></i>' : '<i class="fas fa-lock"></i>');
                }

                if (status) {
                    status.textContent = isWon
                        ? 'تم اللف'
                        : (isUnlocked ? 'القفل مفتوح' : purchaseWheelRangeText(wheel));
                }
            });
        }

        function setupPurchaseWheelsVertical() {
            const container = document.getElementById('purchaseWheelsCarousel');
            const track = document.getElementById('purchaseWheelsTrack');
            const wheels = purchaseRewardWheels();
            if (!container || !track || wheels.length === 0) return;
            if (!visitorCanUsePurchaseWheels()) {
                container.hidden = true;
                track.innerHTML = '';
                return;
            }

            container.hidden = false;
            track.innerHTML = '';

            const totalCount = wheels.length;
            const repeatCount = Math.max(3, Math.ceil(12 / totalCount));

            Array.from({ length: repeatCount }, () => wheels).flat().forEach((wheel, cloneIndex) => {
                const wheelIndex = cloneIndex % wheels.length;
                const item = document.createElement('div');
                item.className = 'purchase-wheel-item v-item';
                item.innerHTML = `
                    <button type="button" class="purchase-wheel-bubble" data-purchase-wheel-index="${wheelIndex}" aria-label="${escapeCartHtml(wheel.title || 'عجلة الشراء')}">
                        <span class="purchase-wheel-pointer"></span>
                        <span class="purchase-wheel-mini" style="--purchase-wheel-gradient: ${purchaseWheelGradient(wheel)}">
                            <span class="purchase-wheel-prizes">${purchaseWheelSegmentBadges(wheel)}</span>
                            <span class="purchase-wheel-lock" data-purchase-wheel-lock><i class="fas fa-lock"></i></span>
                        </span>
                        <span class="purchase-wheel-title">${escapeCartHtml(wheel.title || 'عجلة الشراء')}</span>
                        <span class="purchase-wheel-status" data-purchase-wheel-status>${escapeCartHtml(purchaseWheelRangeText(wheel))}</span>
                    </button>
                `;
                track.appendChild(item);
            });

            const itemHeight = 270;
            container.scrollTop = totalCount * itemHeight;

            const updateWheelCarousel = () => {
                const items = track.querySelectorAll('.v-item');
                const centerY = container.offsetHeight / 2;
                const containerRect = container.getBoundingClientRect();

                items.forEach((item) => {
                    const rect = item.getBoundingClientRect();
                    const itemCenter = rect.top + rect.height / 2 - containerRect.top;
                    const dist = Math.abs(itemCenter - centerY);
                    const scale = Math.max(0.84, Math.min(1.28, (1 - dist / 260) * 1.38));

                    item.style.opacity = Math.max(0.72, 1 - dist / 520);
                    item.style.transform = `scale(${scale})`;
                    item.classList.toggle('active', dist < 72);
                });
            };

            let wheelUpdateFrame = 0;
            container.onscroll = () => {
                const currentScroll = container.scrollTop;
                const maxScroll = (totalCount * 2) * itemHeight;

                if (currentScroll >= maxScroll) {
                    container.scrollTop = currentScroll - (totalCount * itemHeight);
                } else if (currentScroll <= (totalCount * 0.5) * itemHeight) {
                    container.scrollTop = currentScroll + (totalCount * itemHeight);
                }

                if (wheelUpdateFrame) return;
                wheelUpdateFrame = requestAnimationFrame(() => {
                    wheelUpdateFrame = 0;
                    updateWheelCarousel();
                });
            };

            track.onclick = (event) => {
                const button = event.target.closest('[data-purchase-wheel-index]');
                if (!button) return;

                const wheel = purchaseRewardWheels()[Number(button.dataset.purchaseWheelIndex)];
                if (!wheel || !isPurchaseWheelUnlocked(wheel) || storedReward(purchaseWheelStorageKey(wheel))) {
                    return;
                }

                window.ozmanOpenPurchaseRewardWheel?.(wheel);
            };

            window.setTimeout(() => {
                container.dispatchEvent(new Event('scroll'));
                updatePurchaseWheelStates();
            }, 100);
        }

        function productCartKey(product) {
            return `${product.name || ''}|${product.img || ''}|${product.unit_key || ''}`;
        }

        function parseCartPrice(price) {
            const cleaned = String(price || '').replace(/[^\d.]/g, '');
            const value = Number.parseFloat(cleaned);
            return Number.isFinite(value) ? value : 0;
        }

        function formatCartPrice(value) {
            if (!value) return '0';
            return `${value.toFixed(2)} شيكل`;
        }

        function currentVisitorType() {
            return localStorage.getItem(VISITOR_TYPE_STORAGE_KEY) === 'merchant' ? 'merchant' : 'customer';
        }

        function productDisplayPrice(product) {
            if (!product) return '';
            if (product.unit_price) {
                return product.unit_price;
            }

            if (currentVisitorType() === 'merchant' && product.merchant_price) {
                return product.merchant_price;
            }

            return product.customer_price || product.price || '';
        }

        function activeBundleCampaign(product) {
            if (!product) {
                return null;
            }

            const campaigns = Array.isArray(product.campaigns) ? product.campaigns : [];
            const unitKey = product.unit_key || '';
            const validCampaigns = campaigns.filter((campaign) => {
                return campaign
                    && campaign.offer_type === 'bundle_price'
                    && Number(campaign.offer_quantity) >= 1
                    && Number(campaign.offer_price) >= 0;
            });

            return validCampaigns.find((campaign) => (campaign.unit_key || '') === unitKey)
                || validCampaigns.find((campaign) => !campaign.unit_key)
                || null;
        }

        function campaignCartMeta(product) {
            const campaign = activeBundleCampaign(product);
            if (!campaign) return null;

            const quantity = Number.parseInt(campaign.offer_quantity, 10);
            const price = Number(campaign.offer_price);

            if (!Number.isFinite(quantity) || quantity < 1 || !Number.isFinite(price)) {
                return null;
            }

            return {
                quantity,
                price,
                label: campaign.title || campaign.offer_note || `${quantity} بسعر ${price.toFixed(2)}`,
            };
        }

        function campaignUnitChoiceText(campaignOffer) {
            if (!campaignOffer) return '';

            if (campaignOffer.label) {
                return campaignOffer.label;
            }

            return `كل ${campaignOffer.quantity} بسعر ${formatCartPrice(Number(campaignOffer.price))}`;
        }

        function campaignCartText(campaignOffer) {
            if (!campaignOffer) return '';

            return campaignOffer.label
                || `كل ${campaignOffer.quantity} بسعر ${formatCartPrice(Number(campaignOffer.price))}`;
        }

        function cartItemLineTotal(item) {
            const qty = Number(item.qty || 0);
            const unitPrice = parseCartPrice(item.price);
            const campaign = item.campaign_offer;

            if (!campaign || !campaign.quantity || campaign.quantity < 1 || campaign.price === undefined) {
                return unitPrice * qty;
            }

            const bundles = Math.floor(qty / Number(campaign.quantity));
            const remainder = qty % Number(campaign.quantity);

            return (bundles * Number(campaign.price)) + (remainder * unitPrice);
        }

        function cartItemCampaignSavings(item) {
            const qty = Number(item.qty || 0);
            const unitPrice = parseCartPrice(item.price);
            const regularTotal = unitPrice * qty;
            const campaignTotal = cartItemLineTotal(item);

            return Math.max(regularTotal - campaignTotal, 0);
        }

        function productUnitOptions(product) {
            if (!product) return [];

            return [
                { key: 'package', label: 'العبوة', price: product.package_price, icon: 'fa-box' },
                { key: 'pallet', label: 'المشطاح', price: product.pallet_price, icon: 'fa-boxes-stacked' },
                { key: 'carton', label: 'الكرتونة', price: product.carton_price, icon: 'fa-cube' },
            ]
                .filter((option) => option.price)
                .map((option) => ({
                    ...option,
                    campaign_offer: campaignCartMeta({
                        ...product,
                        unit_key: option.key,
                        unit_price: option.price,
                        price: option.price,
                    }),
                }));
        }

        function productWithUnit(product, unitOption) {
            return {
                ...product,
                unit_key: unitOption.key,
                unit_label: unitOption.label,
                unit_price: unitOption.price,
                price: unitOption.price,
                campaign_offer: unitOption.campaign_offer || null,
            };
        }

        function closeUnitChoiceModal() {
            const modal = document.getElementById('unitChoiceModal');
            if (!modal) return;

            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            pendingUnitChoice = null;
        }

        function openUnitChoiceModal(product, qty = 1, openPanel = false) {
            const options = productUnitOptions(product);
            if (!options.length) {
                addToCart(product, qty, openPanel, true);
                return;
            }

            const modal = document.getElementById('unitChoiceModal');
            const title = document.getElementById('unitChoiceTitle');
            const optionsEl = document.getElementById('unitChoiceOptions');
            if (!modal || !optionsEl) {
                addToCart(productWithUnit(product, options[0]), qty, openPanel, true);
                return;
            }

            pendingUnitChoice = { product, qty, openPanel };
            if (title) title.textContent = product.name || 'إضافة المنتج للسلة';

            optionsEl.innerHTML = options.map((option) => `
                <button type="button" class="unit-choice-option" data-unit-key="${escapeCartHtml(option.key)}">
                    <span class="unit-choice-main">
                        <span class="unit-choice-label">
                            <i class="fas ${escapeCartHtml(option.icon)}"></i>
                            <span>${escapeCartHtml(option.label)}</span>
                        </span>
                        <span class="unit-choice-price">${escapeCartHtml(option.price)}</span>
                    </span>
                    ${option.campaign_offer ? `<span class="unit-choice-offer">عرض: ${escapeCartHtml(campaignUnitChoiceText(option.campaign_offer))}</span>` : ''}
                </button>
            `).join('');

            optionsEl.querySelectorAll('[data-unit-key]').forEach((button) => {
                button.addEventListener('click', () => {
                    const choice = productUnitOptions(pendingUnitChoice?.product).find((option) => option.key === button.dataset.unitKey);
                    if (!choice || !pendingUnitChoice) return;

                    addToCart(
                        productWithUnit(pendingUnitChoice.product, choice),
                        pendingUnitChoice.qty,
                        pendingUnitChoice.openPanel,
                        true
                    );
                    closeUnitChoiceModal();
                });
            });

            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
        }

        function productForCurrentVisitor(product) {
            if (!product) return product;

            return {
                ...product,
                price: productDisplayPrice(product),
                unit_key: product.unit_key || '',
                unit_label: product.unit_label || '',
                unit_price: product.unit_price || '',
                campaign_offer: campaignCartMeta(product),
            };
        }

        function syncCartPricesForVisitor() {
            let changed = false;

            ozmanCart.forEach((item) => {
                const product = findProductByName(item.name);
                if (!product) return;

                const unitOption = item.unit_key
                    ? productUnitOptions(product).find((option) => option.key === item.unit_key)
                    : null;
                const price = unitOption ? unitOption.price : productDisplayPrice(product);
                if (price && item.price !== price) {
                    item.price = price;
                    changed = true;
                }

                const campaignOffer = unitOption
                    ? campaignCartMeta({
                        ...product,
                        unit_key: unitOption.key,
                        unit_price: unitOption.price,
                        price: unitOption.price,
                    })
                    : campaignCartMeta(product);
                if (JSON.stringify(item.campaign_offer || null) !== JSON.stringify(campaignOffer || null)) {
                    item.campaign_offer = campaignOffer || null;
                    changed = true;
                }
            });

            if (changed) saveCart();
        }

        function escapeCartHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function youtubeEmbedUrl(url) {
            const value = String(url || '');
            const match = value.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/shorts\/)([A-Za-z0-9_-]+)/);
            return match ? `https://www.youtube.com/embed/${match[1]}?autoplay=1&mute=1&playsinline=1&rel=0` : value;
        }

        function renderShopPeopleDropdown(shop) {
            const dropdown = document.getElementById('activeShopLogoDropdown');
            if (!dropdown) return;

            const renderGroup = (title, people, borderClass, emptyText) => {
                const items = Array.isArray(people) && people.length
                    ? people.map((person) => `
                        <button type="button" class="sub-item agent-item" data-person-shop-id="${escapeCartHtml(person.shop_id || shop.id || '')}" data-person-id="${escapeCartHtml(person.id || '')}" data-person-type="${escapeCartHtml(person.type || '')}">
                            <div class="agent-main">
                                <div class="agent-logo-wrapper">
                                    <img src="${escapeCartHtml(person.image || shop.logo || shop.img || 'images/logo.jpg')}" alt="${escapeCartHtml(person.name || title)}" class="agent-img-logo ${borderClass}">
                                </div>
                                <span class="agent-name">${escapeCartHtml(person.name || '')}</span>
                            </div>
                            <div class="agent-shop">${escapeCartHtml(person.contact || shop.title || '')}</div>
                        </button>
                    `).join('')
                    : `<div class="sub-item">${escapeCartHtml(emptyText)}</div>`;

                return `
                    <div class="dropdown-item">
                        ${escapeCartHtml(title)}
                        <div class="sub-dropdown">${items}</div>
                    </div>
                `;
            };

            dropdown.innerHTML = [
                renderGroup(frontLabel('distributors', 'الموزعون'), shop.distributors, 'border-cyan', frontLabel('noDistributors', 'لا يوجد موزعون بعد')),
                renderGroup(frontLabel('agents', 'الوكلاء'), shop.agents, 'border-blue', frontLabel('noAgents', 'لا يوجد وكلاء بعد')),
            ].join('');

            dropdown.querySelectorAll('[data-person-shop-id]').forEach((item) => {
                item.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    selectPersonItem(item);
                    dropdown.classList.remove('show');
                    scrollToDepartments();
                });
            });
        }

        function scrollToDepartments() {
            document.querySelector('.radial-section')?.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        function numericCoordinate(value) {
            const number = Number(value);
            return Number.isFinite(number) ? number : null;
        }

        function hasShopCoordinates(shop) {
            return numericCoordinate(shop?.latitude) !== null && numericCoordinate(shop?.longitude) !== null;
        }

        function shopMapsUrl(shop) {
            if (shop?.map_url && shop.map_url !== '#') {
                return shop.map_url;
            }

            const latitude = numericCoordinate(shop?.latitude);
            const longitude = numericCoordinate(shop?.longitude);

            if (latitude !== null && longitude !== null) {
                return `https://www.google.com/maps/dir/?api=1&destination=${latitude},${longitude}`;
            }

            return shop?.map_url || `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(shop?.address || shop?.title || '')}`;
        }

        function shopMapEmbedUrl(shop, origin = null) {
            const latitude = numericCoordinate(shop?.latitude);
            const longitude = numericCoordinate(shop?.longitude);
            const originLatitude = numericCoordinate(origin?.latitude);
            const originLongitude = numericCoordinate(origin?.longitude);

            if (latitude !== null && longitude !== null && originLatitude !== null && originLongitude !== null) {
                return `https://maps.google.com/maps?saddr=${originLatitude},${originLongitude}&daddr=${latitude},${longitude}&dirflg=d&output=embed`;
            }

            if (latitude !== null && longitude !== null) {
                return `https://maps.google.com/maps?q=${latitude},${longitude}&z=15&output=embed`;
            }

            return `https://maps.google.com/maps?q=${encodeURIComponent(shop?.address || shop?.title || '')}&z=14&output=embed`;
        }

        function distanceBetweenKm(origin, shop) {
            const lat1 = numericCoordinate(origin?.latitude);
            const lon1 = numericCoordinate(origin?.longitude);
            const lat2 = numericCoordinate(shop?.latitude);
            const lon2 = numericCoordinate(shop?.longitude);

            if ([lat1, lon1, lat2, lon2].some((value) => value === null)) return null;

            const toRadians = (degrees) => degrees * Math.PI / 180;
            const earthRadiusKm = 6371;
            const deltaLat = toRadians(lat2 - lat1);
            const deltaLon = toRadians(lon2 - lon1);
            const a = Math.sin(deltaLat / 2) ** 2
                + Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) * Math.sin(deltaLon / 2) ** 2;

            return earthRadiusKm * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        }

        function formatDistance(distance) {
            if (distance === null) return 'المسافة غير محددة';
            if (distance < 1) return `${Math.round(distance * 1000)} متر`;
            return `${distance.toFixed(distance < 10 ? 1 : 0)} كم`;
        }

        function shopDistanceLabel(shop, distance) {
            if (!hasShopCoordinates(shop)) return 'بدون إحداثيات';
            return formatDistance(distance);
        }

        function formatLocationAccuracy(location) {
            const accuracy = Number(location?.accuracy);
            if (!Number.isFinite(accuracy)) return '';
            return `دقة الموقع: حوالي ${Math.round(accuracy)} متر`;
        }

        function formatLocationCoordinates(location) {
            const latitude = Number(location?.latitude);
            const longitude = Number(location?.longitude);
            if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return '';
            return `الإحداثيات: ${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
        }

        function positionToCustomerLocation(position) {
            return {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: Number.isFinite(Number(position.coords.accuracy)) ? Number(position.coords.accuracy) : null
            };
        }

        function requestPreciseCustomerLocation(onProgress) {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('geolocation_unavailable'));
                    return;
                }

                let bestLocation = null;
                let settled = false;
                let watchId = null;
                const options = {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                };

                const finish = (location) => {
                    if (settled) return;
                    settled = true;
                    if (watchId !== null) navigator.geolocation.clearWatch(watchId);
                    location ? resolve(location) : reject(new Error('location_unavailable'));
                };

                const acceptPosition = (position) => {
                    const location = positionToCustomerLocation(position);
                    if (!bestLocation || (location.accuracy ?? Infinity) < (bestLocation.accuracy ?? Infinity)) {
                        bestLocation = location;
                        onProgress?.(bestLocation);
                    }

                    if ((bestLocation.accuracy ?? Infinity) <= 35) {
                        finish(bestLocation);
                    }
                };

                watchId = navigator.geolocation.watchPosition(acceptPosition, () => finish(bestLocation), options);
                window.setTimeout(() => finish(bestLocation), 8500);
            });
        }

        function shopsSortedByDistance(location) {
            return centersData
                .map((shop, index) => ({
                    shop,
                    index,
                    distance: location ? distanceBetweenKm(location, shop) : null
                }))
                .sort((a, b) => {
                    if (a.distance === null && b.distance === null) return a.index - b.index;
                    if (a.distance === null) return 1;
                    if (b.distance === null) return -1;
                    return a.distance - b.distance;
                });
        }

        function renderShopDirectionsPanel(shop) {
            const panel = document.getElementById('shopDirectionsPanel');
            const title = document.getElementById('shopDirectionsTitle');
            const link = document.getElementById('shopDirectionsLink');
            if (!panel || !title || !link || !shop) return;

            title.textContent = shop.title || frontLabel('shop', 'المحل');
            const label = panel.querySelector('[data-directions-label]');
            const linkText = panel.querySelector('[data-directions-link-text]');
            const subject = shop.display_label || frontLabel('shop', 'المحل');
            if (label) label.textContent = frontLabelTemplate('directionsTo', 'الوصول إلى :subject', { subject });
            if (linkText) linkText.textContent = frontLabelTemplate('directionsLinkTo', 'انقر فوق الخارطة للوصول إلى :subject عبر GPS', { subject });
            link.href = shopMapsUrl(shop);
            panel.hidden = false;
        }

        function selectShopById(shopId) {
            const index = centersData.findIndex((center) => String(center.id || '') === String(shopId || ''));
            selectCategory(index >= 0 ? index : activeCenterIndex);
        }

        function findPersonInShop(shop, personId, personType) {
            const groups = personType === 'agent'
                ? [shop.agents || []]
                : personType === 'distributor'
                    ? [shop.distributors || []]
                    : [shop.agents || [], shop.distributors || []];

            for (const people of groups) {
                const person = people.find((item) => String(item.id || '') === String(personId || ''));
                if (person) return person;
            }

            return null;
        }

        function selectPersonContext(index, person, personType = 'distributor') {
            if (index < 0 || !person) {
                return false;
            }

            if (!person || !Array.isArray(person.departments) || !person.departments.length) {
                selectCategory(index);
                return true;
            }

            activeCenterIndex = index;
            const shop = centersData[index];
            activeProductsDb = person.products_db || {};
            activePersonContext = person;
            renderActiveShopHeader({
                ...shop,
                title: person.name || shop.title,
                img: person.image || shop.img,
                logo: person.image || shop.logo || shop.img,
                whatsapp_number: person.whatsapp_number || shop.whatsapp_number || null,
                display_label: person.display_label || (personType === 'distributor' ? frontLabel('distributor', 'الموزع') : frontLabel('agent', 'الوكيل')),
                address: person.address || '',
                latitude: person.latitude ?? null,
                longitude: person.longitude ?? null,
                map_url: person.map_url || '#',
                social_links: [],
            });
            renderDepartmentsForCenter(index, person);

            return true;
        }

        function selectPersonItem(item) {
            const shopId = item.dataset.personShopId;
            const personId = item.dataset.personId;
            const personType = item.dataset.personType;
            const index = centersData.findIndex((center) => String(center.id || '') === String(shopId || ''));

            if (index < 0 || !personId) {
                selectShopById(shopId);
                return;
            }

            const shop = centersData[index];
            const person = findPersonInShop(shop, personId, personType);
            selectPersonContext(index, person, personType);
        }

        function applyInitialPersonContext() {
            const context = window.OZMAN_FRONT_CONFIG?.initialPersonContext;
            if (!context?.id || !context?.shop_id) {
                return;
            }

            const index = centersData.findIndex((center) => String(center.id || '') === String(context.shop_id || ''));
            if (index < 0) {
                return;
            }

            const person = findPersonInShop(centersData[index], context.id, context.type || 'distributor');
            if (selectPersonContext(index, person, context.type || 'distributor')) {
                setTimeout(scrollToDepartments, 350);
            }
        }

        document.addEventListener('click', (event) => {
            const personItem = event.target.closest('[data-person-shop-id]');
            if (!personItem) return;

            event.preventDefault();
            event.stopPropagation();
            selectPersonItem(personItem);
            personItem.closest('.logo-dropdown')?.classList.remove('show');
            scrollToDepartments();
        });

        function renderActiveShopHeader(shop) {
            if (!shop) return;
            updateWhatsappRecipient(shop);

            const logo = document.getElementById('activeShopLogo');
            const socials = document.getElementById('activeShopSocials');
            const display = document.getElementById('activeShopDisplay');

            if (logo) {
                logo.src = shop.logo || shop.img || 'images/logo.jpg';
                logo.alt = `${shop.title || 'المحل'} Logo`;
            }

            renderShopDirectionsPanel(shop);
            renderShopPeopleDropdown(shop);

            if (socials) {
                const links = Array.isArray(shop.social_links) ? shop.social_links : [];
                socials.innerHTML = links.length
                    ? links.map((link) => `
                        <a href="${escapeCartHtml(link.url)}" target="_blank" rel="noopener noreferrer" class="social-icon" title="${escapeCartHtml(link.title)}" aria-label="${escapeCartHtml(link.title)}">
                            <i class="${escapeCartHtml(link.icon)}"></i>
                        </a>
                    `).join('')
                    : `<span class="social-icon social-icon-muted" title="لا توجد روابط تواصل"><i class="fas fa-share-nodes"></i></span>`;
            }

            if (display) {
                const items = Array.isArray(shop.display_items) ? shop.display_items : [];
                if (items.length) {
                    display.innerHTML = `<div class="media-story-slider" data-media-story>${items.map((item, index) => {
                        const title = escapeCartHtml(item.title || shop.title || 'عرض المتجر');
                        const src = escapeCartHtml(item.type === 'youtube' ? youtubeEmbedUrl(item.src) : item.src);
                        const media = item.type === 'video'
                            ? `<video src="${src}" muted playsinline loop></video>`
                            : item.type === 'youtube'
                                ? `<iframe src="${src}" title="${title}" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>`
                                : `<img src="${src}" alt="${title}">`;

                        return `<article class="media-story-slide ${index === 0 ? 'active' : ''}" data-duration="${Number(item.duration || 8000)}">${media}</article>`;
                    }).join('')}</div>`;

                    const slider = display.querySelector('[data-media-story]');
                    if (slider) initMediaStorySlider(slider);
                } else {
                    const template = display.dataset.emptyTextTemplate || 'أهلا بك في :shop - اكتشف أقسام ومنتجات المتجر';
                    const text = escapeCartHtml(template.replace(':shop', shop.title || 'المحل'));
                    display.innerHTML = `<div class="story-slider"><span class="welcome-msg">${text}</span><span class="welcome-msg">${text}</span></div>`;
                }
            }
        }

        function showCartToast(message) {
            let toast = document.getElementById('cartToast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'cartToast';
                toast.className = 'cart-toast';
                document.body.appendChild(toast);
            }

            toast.textContent = message;
            toast.classList.add('show');
            clearTimeout(cartToastTimer);
            cartToastTimer = setTimeout(() => {
                toast.classList.remove('show');
            }, 1700);
        }

        function cartTotalQty() {
            return ozmanCart.reduce((sum, item) => sum + Number(item.qty || 0), 0);
        }

        function cartTotalValue() {
            return ozmanCart.reduce((sum, item) => sum + cartItemLineTotal(item), 0);
        }

        function cartDiscountEligible() {
            return ozmanCart.length >= 2;
        }

        function standardCartDiscountValue() {
            return cartDiscountEligible() ? cartTotalValue() * 0.05 : 0;
        }

        function rewardDiscountValue(subtotal = cartTotalValue()) {
            const reward = loadRewardDiscount();
            if (!reward || subtotal <= 0) return 0;

            const value = Number(reward.discount_value || 0);
            if (reward.discount_type === 'percent') {
                return subtotal * Math.max(value, 0) / 100;
            }

            if (reward.discount_type === 'amount') {
                return Math.min(Math.max(value, 0), subtotal);
            }

            return 0;
        }

        function cartDiscountBreakdown(subtotal = cartTotalValue()) {
            const standard = Math.min(standardCartDiscountValue(), subtotal);
            const reward = Math.min(rewardDiscountValue(subtotal), Math.max(subtotal - standard, 0));
            const total = standard + reward;

            return {
                standard,
                reward,
                total
            };
        }

        function cartDiscountValue() {
            return cartDiscountBreakdown().total;
        }

        function cartFinalValue() {
            return Math.max(cartTotalValue() - cartDiscountValue(), 0);
        }

        function rewardDiscountLine(prefix = '\n') {
            const reward = loadRewardDiscount();
            const rewardValue = rewardDiscountValue();
            if (!reward || rewardValue <= 0) return '';

            return `${prefix}خصم العجلة (${reward.label}): ${formatCartPrice(rewardValue)}`;
        }

        function cartDiscountMessageLine(prefix = '\n') {
            const breakdown = cartDiscountBreakdown();
            const lines = [];

            if (breakdown.standard > 0) {
                lines.push(`خصم السلة 5%: ${formatCartPrice(breakdown.standard)}`);
            }

            if (breakdown.reward > 0) {
                const reward = loadRewardDiscount();
                lines.push(`خصم العجلة (${reward?.label || 'خصمك الأول'}): ${formatCartPrice(breakdown.reward)}`);
            }

            return lines.length ? `${prefix}${lines.join('\n')}` : '';
        }

        function updateCartBadge() {
            const badge = document.getElementById('cartCountBadge');
            if (!badge) return;

            const count = cartTotalQty();
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.classList.toggle('show', count > 0);
        }

        function addToCart(product, qty = 1, openPanel = false, skipUnitChoice = false) {
            if (!product || !product.name) return;

            if (!skipUnitChoice && !product.unit_key && productUnitOptions(product).length) {
                openUnitChoiceModal(product, qty, openPanel);
                return;
            }

            product = productForCurrentVisitor(product);
            const key = productCartKey(product);
            const existing = ozmanCart.find(item => item.key === key);
            const amount = Math.max(Number.parseInt(qty, 10) || 1, 1);

            if (existing) {
                existing.qty += amount;
            } else {
                ozmanCart.push({
                    key,
                    name: product.name,
                    price: product.price || '',
                    img: product.img || '',
                    unit_key: product.unit_key || '',
                    unit_label: product.unit_label || '',
                    campaign_offer: product.campaign_offer || null,
                    qty: amount
                });
            }

            saveCart();
            renderCart();
            showCartToast(`تمت إضافة "${product.name}"${product.unit_label ? ` - ${product.unit_label}` : ''} إلى السلة`);

            if (openPanel) {
                openCartPanel();
            }
        }

        function setCartItemQty(key, qty) {
            const item = ozmanCart.find(cartItem => cartItem.key === key);
            if (!item) return;

            item.qty = Math.max(Number.parseInt(qty, 10) || 1, 1);
            saveCart();
            renderCart();
        }

        function removeCartItem(key) {
            ozmanCart = ozmanCart.filter(item => item.key !== key);
            saveCart();
            renderCart();
        }

        function clearCart() {
            ozmanCart = [];
            saveCart();
            renderCart();
        }

            function openCartPanel() {
                const panel = document.getElementById('cartPanel');
                const navCartBtn = document.getElementById('navCartBtn');
                if (!panel) return;

                document.getElementById('chatbotWidget')?.classList.remove('active');
                panel.classList.add('active');
                panel.setAttribute('aria-hidden', 'false');
                if (navCartBtn) navCartBtn.classList.add('active');
                renderCart();
            }

        function closeCartPanel() {
            const panel = document.getElementById('cartPanel');
            const navCartBtn = document.getElementById('navCartBtn');
            if (!panel) return;

            panel.classList.remove('active');
            panel.setAttribute('aria-hidden', 'true');
            if (navCartBtn) navCartBtn.classList.remove('active');
        }

        function cartWhatsappMessage() {
            const lines = ozmanCart.map((item, index) => {
                const lineTotal = cartItemLineTotal(item);
                const unitText = item.unit_label ? ` - النوع: ${item.unit_label}` : '';
                const priceText = item.price ? ` - السعر: ${item.price}` : '';
                const campaignText = item.campaign_offer ? ` - عرض الحملة: ${campaignCartText(item.campaign_offer)}` : '';
                const totalText = lineTotal ? ` - المجموع: ${formatCartPrice(lineTotal)}` : '';
                return `${index + 1}. ${item.name}${unitText} - الكمية: ${item.qty}${priceText}${campaignText}${totalText}`;
            });

            const discountLine = cartDiscountMessageLine();

            return `مرحبا، أود طلب المنتجات التالية:\n${lines.join('\n')}\n\nالمجموع قبل الخصم: ${formatCartPrice(cartTotalValue())}${discountLine}\nالمجموع النهائي: ${formatCartPrice(cartFinalValue())}`;
        }

        function marketingMessageLine() {
            const context = window.OZMAN_FRONT_CONFIG?.marketingContext || {};

            if (context.source === 'marketer' && context.marketer_name) {
                return `مصدر الطلب: عبر المسوق ${context.marketer_name}`;
            }

            return '';
        }

        function orderQrImageUrl(order) {
            const lookupUrl = order?.order_lookup_url || order?.order_qr_url || '';
            if (!lookupUrl) return '';

            return `https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=${encodeURIComponent(lookupUrl)}`;
        }

        function customerOrderMessage(profile, order = null) {
            const qrImageUrl = orderQrImageUrl(order);
            const customerLines = [
                'مرحبا، أود تأكيد طلب جديد.',
                order?.order_number ? `رقم الطلب: ${order.order_number}` : '',
                qrImageUrl ? `صورة QR الطلب:\n${qrImageUrl}` : '',
                order?.order_lookup_url ? `رابط الطلب في الداشبورد:\n${order.order_lookup_url}` : '',
                marketingMessageLine(),
                '',
                'بيانات العميل:',
                `الاسم: ${profile.name || '-'}`,
                `رقم الهاتف: ${profile.phone || '-'}`,
                `رقم الواتس اب: ${profile.whatsapp || '-'}`,
                `العنوان: ${profile.address || '-'}`,
                `رابط الموقع: ${profile.mapLink || '-'}`
            ].filter((line) => line !== '');

            const orderItems = pendingSingleProduct
                ? [{
                    name: pendingSingleProduct.name,
                    price: pendingSingleProduct.price || '',
                    qty: pendingSingleProduct.qty || 1
                }]
                : ozmanCart;

            const orderLines = orderItems.map((item, index) => {
                const lineTotal = cartItemLineTotal(item);
                const unitText = item.unit_label ? ` - النوع: ${item.unit_label}` : '';
                const priceText = item.price ? ` - السعر: ${item.price}` : '';
                const campaignText = item.campaign_offer ? ` - عرض الحملة: ${campaignCartText(item.campaign_offer)}` : '';
                const totalText = lineTotal ? ` - المجموع: ${formatCartPrice(lineTotal)}` : '';
                return `${index + 1}. ${item.name}${unitText} - الكمية: ${item.qty}${priceText}${campaignText}${totalText}`;
            });

            const subtotal = pendingSingleProduct
                ? cartItemLineTotal(pendingSingleProduct)
                : cartTotalValue();
            const discount = pendingSingleProduct ? 0 : cartDiscountValue();
            const total = Math.max(subtotal - discount, 0);
            const discountLine = pendingSingleProduct ? '' : cartDiscountMessageLine();

            return `${customerLines.join('\n')}\n\nالمنتجات:\n${orderLines.join('\n') || '-'}\n\nالمجموع قبل الخصم: ${formatCartPrice(subtotal)}${discountLine}\nالمجموع النهائي: ${formatCartPrice(total)}`;
        }

        function paymentMethodLabel(method) {
            const configuredLabel = window.OZMAN_FRONT_CONFIG?.payment?.method_label;
            if (configuredLabel) return configuredLabel;

            return {
                paypal: 'PayPal',
                mastercard: 'Mastercard',
                visa: 'Visa',
                bank_transfer: 'تحويل بنكي',
                wallet: 'محفظة إلكترونية',
                cash: 'كاش / عند الاستلام',
                other: 'أخرى',
                shop_account: 'حساب المتجر'
            }[method] || 'الدفع الفوري';
        }

        function shopPaymentMessageDetails() {
            const payment = window.OZMAN_FRONT_CONFIG?.payment || {};
            const lines = [
                ['طريقة الدفع', payment.method_label],
                ['البنك أو مزود الدفع', payment.provider],
                ['اسم صاحب الحساب', payment.account_holder],
                ['رقم الحساب', payment.account_number],
                ['IBAN', payment.iban],
                ['رقم المحفظة', payment.wallet_number],
                ['ملاحظات الدفع', payment.notes]
            ]
                .filter(([, value]) => value)
                .map(([label, value]) => `${label}: ${value}`);

            return lines.length ? `\n\nبيانات حساب المتجر:\n${lines.join('\n')}` : '';
        }

        function customerPaymentMessage(profile, method, order = null) {
            return `${customerOrderMessage(profile, order)}\n\nطريقة الدفع المختارة: ${paymentMethodLabel(method)}${shopPaymentMessageDetails()}\nحالة الدفع: بانتظار تأكيد التحويل من المتجر.`;
        }

        function currentOrderItems() {
            const items = pendingSingleProduct
                ? [pendingSingleProduct]
                : ozmanCart;

                return items.map((item) => ({
                    name: item.name || '',
                    price: item.price || '',
                    unit_label: item.unit_label || '',
                    campaign_offer: item.campaign_offer || null,
                    qty: Number(item.qty || 1),
                    img: item.img || ''
                }));
        }

        function currentOrderTotals() {
            const subtotal = pendingSingleProduct
                ? cartItemLineTotal(pendingSingleProduct)
                : cartTotalValue();
            const discount = pendingSingleProduct ? 0 : cartDiscountValue();
            const total = Math.max(subtotal - discount, 0);

            return { subtotal, discount, total };
        }

        function clearSubmittedOrderItems() {
            if (!pendingSingleProduct) {
                clearCartState();
            }

            renderCart();
        }

        function csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        }

        async function recordFrontOrder(profile, channel, paymentMethod = '') {
            const urls = window.OZMAN_FRONT_CONFIG || {};
            const marketingContext = urls.marketingContext || {};
            const { subtotal, discount, total } = currentOrderTotals();
            const eligibleWheel = eligiblePurchaseRewardWheel(total);

            const response = await fetch(urls.orderStoreUrl || '/front-orders', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken()
                },
                body: JSON.stringify({
                    shop_id: urls.shopId || null,
                    distributor_id: marketingContext.distributor_id || null,
                    distributor_marketer_id: marketingContext.marketer_id || null,
                    marketing_source: marketingContext.source || null,
                    reward_wheel_id: eligibleWheel?.id || null,
                    customer_name: profile.name || '',
                    customer_phone: profile.phone || '',
                    customer_whatsapp: profile.whatsapp || '',
                    customer_address: profile.address || '',
                    latitude: profile.latitude || null,
                    longitude: profile.longitude || null,
                    map_link: profile.mapLink || '',
                    items: currentOrderItems(),
                    subtotal,
                    discount,
                    total,
                    order_channel: channel,
                    payment_method: paymentMethod || null
                })
            });

            const result = await response.json().catch(() => ({}));
            if (!response.ok) {
                const firstError = result?.errors ? Object.values(result.errors).flat()[0] : null;
                throw new Error(firstError || result?.message || 'تعذر حفظ الطلب في الداشبورد.');
            }

            savePendingPurchaseOrder(result);
            saveLastRewardOrder(result);
            return result;
        }

        async function recordFrontOrderReward(reward) {
            let order = pendingPurchaseOrder || loadPendingPurchaseOrder();
            if (!order?.order_id && pendingPurchaseOrderPromise) {
                order = await pendingPurchaseOrderPromise.catch(() => null);
            }

            const template = window.OZMAN_FRONT_CONFIG?.orderRewardUrlTemplate;
            if (!order?.order_id || !template || !reward?.label) return false;

            try {
                const response = await fetch(template.replace('__ORDER__', encodeURIComponent(order.order_id)), {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken()
                    },
                    body: JSON.stringify({
                        reward_label: reward.label || '',
                        reward_discount_value: reward.discount_value || 0,
                        reward_discount_type: reward.discount_type || '',
                        reward_gift_image: reward.gift_image || '',
                        reward_color: reward.color || '',
                        reward_won_at: reward.won_at || new Date().toISOString()
                    })
                });

                if (response.ok) {
                    saveLastRewardOrder(order);
                    savePendingPurchaseOrder(null);
                    return true;
                }
            } catch (error) {
                console.warn('Could not sync reward with order', error);
            }

            return false;
        }

        async function spinFrontOrderReward() {
            let order = pendingPurchaseOrder || loadPendingPurchaseOrder();
            if (!order?.order_id && pendingPurchaseOrderPromise) {
                order = await pendingPurchaseOrderPromise.catch(() => null);
            }

            const template = window.OZMAN_FRONT_CONFIG?.orderSpinRewardUrlTemplate;
            if (!order?.order_id || !template) {
                throw new Error('تعذر تحديد الطلب الخاص بالعجلة.');
            }

            const response = await fetch(template.replace('__ORDER__', encodeURIComponent(order.order_id)), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken()
                },
                body: JSON.stringify({})
            });

            const result = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(result?.message || 'تعذر اختيار الجائزة.');
            }

            saveLastRewardOrder(order);
            savePendingPurchaseOrder(null);
            return result;
        }

        function resetPurchaseWheelSession(storageKey) {
            if (!storageKey?.startsWith(PURCHASE_REWARD_STORAGE_PREFIX)) return;

            const wheelId = storageKey.replace(PURCHASE_REWARD_STORAGE_PREFIX, '');
            localStorage.removeItem(storageKey);
            if (wheelId) {
                localStorage.removeItem(`${PURCHASE_UNLOCK_STORAGE_PREFIX}${wheelId}`);
            }

            updatePurchaseWheelStates();
        }

        function renderCart() {
            syncCartPricesForVisitor();
            updateCartBadge();
            updatePurchaseWheelStates();

            const itemsEl = document.getElementById('cartItems');
            const countEl = document.getElementById('cartItemsCount');
            const totalEl = document.getElementById('cartTotal');
            const discountEl = document.getElementById('cartDiscount');
            const promoBox = document.getElementById('cartPromoBox');
            const promoText = document.getElementById('cartPromoText');
            if (!itemsEl) return;

            if (countEl) countEl.textContent = String(cartTotalQty());
            if (discountEl) discountEl.textContent = formatCartPrice(cartDiscountValue());
            if (totalEl) totalEl.textContent = formatCartPrice(cartFinalValue());

            if (ozmanCart.length === 0) {
                itemsEl.innerHTML = `<div class="cart-empty">${escapeCartHtml(frontLabel('cartEmpty', 'السلة فارغة حاليا'))}</div>`;
                if (promoBox) promoBox.hidden = true;
                return;
            }

            if (promoBox && promoText) {
                const eligible = cartDiscountEligible();
                const reward = loadRewardDiscount();
                promoBox.hidden = false;
                promoBox.classList.toggle('active', eligible || Boolean(reward));
                if (reward && rewardDiscountValue() > 0) {
                    promoText.textContent = `تم تفعيل خصم العجلة: ${reward.label}`;
                } else {
                    promoText.textContent = eligible
                        ? frontLabel('discountApplied', 'تم تفعيل خصم 5% على المبلغ الإجمالي')
                        : frontLabel('addAnotherForDiscount', 'أضف منتج آخر إلى السلة واحصل على خصم 5% من المبلغ الإجمالي');
                }
            }

            itemsEl.innerHTML = ozmanCart.map((item, index) => `
                <div class="cart-item" data-cart-index="${index}">
                    <img src="${escapeCartHtml(item.img || 'images/logo.jpg')}" alt="${escapeCartHtml(item.name)}">
                    <div class="cart-item-main">
                        <div class="cart-item-name">${escapeCartHtml(item.name)}</div>
                        ${item.unit_label ? `<div class="cart-item-unit">${escapeCartHtml(item.unit_label)}</div>` : ''}
                        <div class="cart-item-price">${escapeCartHtml(item.price || frontLabel('noPrice', 'بدون سعر'))}</div>
                        ${item.campaign_offer ? `<div class="cart-item-unit">عرض الحملة: ${escapeCartHtml(campaignCartText(item.campaign_offer))}${cartItemCampaignSavings(item) > 0 ? ` - وفرت ${escapeCartHtml(formatCartPrice(cartItemCampaignSavings(item)))}` : ''}</div>` : ''}
                        <div class="cart-item-controls">
                            <div class="cart-qty-controls">
                                <button type="button" class="cart-qty-btn" data-cart-action="minus" aria-label="إنقاص الكمية"><i class="fas fa-minus"></i></button>
                                <span class="cart-qty-val">${escapeCartHtml(item.qty)}</span>
                                <button type="button" class="cart-qty-btn" data-cart-action="plus" aria-label="زيادة الكمية"><i class="fas fa-plus"></i></button>
                            </div>
                            <button type="button" class="cart-remove-btn" data-cart-action="remove" aria-label="حذف المنتج"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            `).join('');

            itemsEl.querySelectorAll('[data-cart-action]').forEach(button => {
                button.addEventListener('click', (event) => {
                    event.stopPropagation();

                    const row = button.closest('.cart-item');
                    const index = Number.parseInt(row?.dataset.cartIndex || '-1', 10);
                    const item = ozmanCart[index];
                    if (!item) return;

                    const action = button.dataset.cartAction;
                    if (action === 'plus') {
                        setCartItemQty(item.key, item.qty + 1);
                    } else if (action === 'minus') {
                        if (item.qty <= 1) {
                            removeCartItem(item.key);
                        } else {
                            setCartItemQty(item.key, item.qty - 1);
                        }
                    } else if (action === 'remove') {
                        removeCartItem(item.key);
                    }
                });
            });
        }

        function findProductByName(productName) {
            for (let dept in activeProductsDb) {
                const match = activeProductsDb[dept].find(product => product.name === productName);
                if (match) return match;
            }

            return carouselProductsDb[productName] || null;
        }

        function getProductsForDepartment(deptTitle) {
            if (activeProductsDb[deptTitle]) {
                return activeProductsDb[deptTitle];
            }
            return [];
        }

        function getRelatedProducts(productName) {
            for (let dept in activeProductsDb) {
                const list = activeProductsDb[dept];
                const found = list.find(p => p.name === productName);
                if (found) {
                    return list.filter(p => p.name !== productName);
                }
            }
            return [];
        }

        function scatterLayout(count, options = {}) {
            if (count <= 0) return [];

            const wrapper = document.getElementById('watchGridWrapper');
            const width = wrapper?.clientWidth || window.innerWidth || 900;
            const height = wrapper?.clientHeight || 650;
            const mobile = width < 720;
            const itemSize = options.itemSize || (mobile ? 82 : count > 24 ? 82 : count > 14 ? 90 : 104);
            const positions = [];
            const footprintWidth = itemSize * (options.footprintWidthScale || (mobile ? 1.65 : 1.82));
            const footprintHeight = itemSize + (options.footprintHeightExtra || (mobile ? 58 : 68));
            const maxX = Math.max(itemSize, width / 2 - footprintWidth);
            const maxY = Math.max(itemSize, height / 2 - footprintHeight);
            const ovalX = mobile ? .86 : 1.12;
            const ovalY = mobile ? 1.05 : .86;
            const ringGap = Math.max(footprintWidth, footprintHeight) * (options.ringGapScale || .72);
            const candidates = [];

            const overlaps = (candidate) => positions.some((pos) => {
                return Math.abs(pos.x - candidate.x) < (pos.footprintWidth + candidate.footprintWidth) / 2
                    && Math.abs(pos.y - candidate.y) < (pos.footprintHeight + candidate.footprintHeight) / 2;
            });

            const pushCandidate = (x, y) => {
                if (Math.abs(x) > maxX || Math.abs(y) > maxY) {
                    return;
                }

                candidates.push({
                    x,
                    y,
                    itemSize,
                    footprintWidth,
                    footprintHeight,
                    sort: Math.random(),
                });
            };

            if (count === 1) {
                pushCandidate(0, 0);
            }

            for (let radius = itemSize * 1.1; radius <= Math.max(maxX, maxY) + ringGap; radius += ringGap) {
                const slots = Math.max(6, Math.floor((Math.PI * 2 * radius) / (Math.max(footprintWidth, footprintHeight) * .9)));
                const offset = Math.random() * Math.PI * 2;

                for (let slot = 0; slot < slots; slot++) {
                    const angle = offset + (slot / slots) * Math.PI * 2 + (Math.random() - .5) * .18;
                    pushCandidate(
                        Math.cos(angle) * radius * ovalX,
                        Math.sin(angle) * radius * ovalY
                    );
                }
            }

            candidates.sort((a, b) => a.sort - b.sort);

            for (let i = 0; i < count; i++) {
                const candidateIndex = candidates.findIndex((candidate) => !overlaps(candidate));

                if (candidateIndex >= 0) {
                    positions.push(candidates.splice(candidateIndex, 1)[0]);
                    continue;
                }

                let bestCandidate = null;
                let bestScore = -Infinity;

                for (let attempt = 0; attempt < 600; attempt++) {
                    const angle = Math.random() * Math.PI * 2;
                    const radius = Math.sqrt(Math.random());
                    const candidate = {
                        x: Math.cos(angle) * maxX * radius,
                        y: Math.sin(angle) * maxY * radius,
                        itemSize,
                        footprintWidth,
                        footprintHeight,
                    };

                    const score = positions.reduce((closest, pos) => {
                        const dx = Math.abs(pos.x - candidate.x) / ((pos.footprintWidth + candidate.footprintWidth) / 2);
                        const dy = Math.abs(pos.y - candidate.y) / ((pos.footprintHeight + candidate.footprintHeight) / 2);
                        return Math.min(closest, Math.max(dx, dy));
                    }, Infinity);

                    if (score > bestScore) {
                        bestCandidate = candidate;
                        bestScore = score;
                    }
                }

                positions.push(bestCandidate || {
                    x: 0,
                    y: 0,
                    itemSize,
                    footprintWidth,
                    footprintHeight,
                });
            }

            return positions;
        }

        function renderProductsScatter(deptTitle) {
            activeDeptTitle = deptTitle;
            activeProductTitle = '';

            const track = document.getElementById('watchGridTrack');
            const header = document.getElementById('productsScatterHeader');
            const titleEl = document.getElementById('productsScatterTitle');
            const descEl = document.getElementById('productsScatterDesc');

            if (!track || !header || !titleEl) return;

            // Ø¥Ø®ÙØ§Ø¡ Ø§Ù„ÙˆØµÙ Ø§Ù„Ø³Ø±ÙŠØ¹ Ø¹Ù†Ø¯ Ø§Ø³ØªØ¹Ø±Ø§Ø¶ Ù‚Ø§Ø¦Ù…Ø© Ø§Ù„Ù…Ù†ØªØ¬Ø§Øª
            if (descEl) descEl.style.display = 'none';

            // Ø¥Ø¸Ù‡Ø§Ø± Ø¹Ù†ÙˆØ§Ù† Ø§Ù„Ù‚Ø³Ù… Ù…Ø¬Ø¯Ø¯Ø§Ù‹
            titleEl.style.display = 'block';

            // ØªØ¹ÙŠÙŠÙ† Ø¹Ù†ÙˆØ§Ù† Ø§Ù„Ù‚Ø³Ù… Ø§Ù„Ù…Ø®ØªØ§Ø±
            titleEl.innerText = deptTitle;

            // Ø¥Ø¸Ù‡Ø§Ø± Ø§Ù„Ù‡ÙŠØ¯Ø± Ø§Ù„Ø¹Ù„ÙˆÙŠ Ù„Ù„Ø£Ù‚Ø³Ø§Ù… ØªØ¯Ø±ÙŠØ¬ÙŠØ§Ù‹
            header.style.display = 'flex';
            header.style.opacity = '0';
            header.style.transform = 'translateY(-10px)';

            setTimeout(() => {
                header.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                header.style.opacity = '1';
                header.style.transform = 'translateY(0)';
            }, 50);

            // ØªÙØ±ÙŠØº Ø§Ù„ØªØ±Ø§Ùƒ ÙˆØ¨Ø¯Ø¡ Ø§Ù„ØªÙˆØ²ÙŠØ¹ Ø§Ù„Ø¹Ø´ÙˆØ§Ø¦ÙŠ Ù„Ù„Ù…Ù†ØªØ¬Ø§Øª
            track.innerHTML = '';
            watchItemsElements = [];

            const products = getProductsForDepartment(deptTitle);
            const targetCount = products.length;

            const positions = scatterLayout(targetCount);

            positions.forEach((pos, i) => {
                const prod = products[i];
                const el = document.createElement('div');
                el.className = 'watch-item product-scatter-item';

                el.style.setProperty('--pos-x', pos.x + 'px');
                el.style.setProperty('--pos-y', pos.y + 'px');
                el.style.setProperty('--scatter-item-size', pos.itemSize + 'px');
                el.style.animation = `bubblePop 0.5s ease forwards ${i * 0.06}s`;
                el.style.opacity = '0';

                // Ù‡ÙŠÙƒÙ„ ÙÙ‚Ø§Ø¹Ø© Ø§Ù„Ù…Ù†ØªØ¬: ØµÙˆØ±Ø© Ø§Ù„Ù…Ù†ØªØ¬ ÙÙŠ Ø§Ù„Ù…Ù†ØªØµÙ ÙˆØ§Ø³Ù… Ø§Ù„Ù…Ù†ØªØ¬ Ø§Ù„Ù…Ø®ØªØµØ± Ø£Ø³ÙÙ„ Ø§Ù„ÙÙ‚Ø§Ø¹Ø© Ù…Ø¨Ø§Ø´Ø±Ø© (Ø¨Ø¯ÙˆÙ† Ø³Ø¹Ø±)
                el.innerHTML = `
                    <button type="button" class="product-add-cart-btn" title="أضف للسلة" aria-label="أضف ${prod.name} إلى السلة">
                        <i class="fas fa-cart-plus"></i>
                    </button>
                    <img src="${prod.img}" alt="${prod.name}">
                    <span class="dept-title">
                        ${prod.name}
                    </span>
                `;
                el.style.transform = `translate(calc(-50% + ${pos.x}px), calc(-50% + ${pos.y}px))`;

                const addCartButton = el.querySelector('.product-add-cart-btn');
                if (addCartButton) {
                    addCartButton.addEventListener('click', (event) => {
                        event.stopPropagation();
                        addToCart(prod, 1, true);
                    });
                }

                el.addEventListener('click', () => {
                    document.querySelectorAll('.watch-item').forEach(item => item.classList.remove('active'));
                    el.classList.add('active');

                    // ÙØªØ­ Ù…Ø¹Ø±Ø¶ ØµÙˆØ± ÙˆØªÙØ§ØµÙŠÙ„ Ø§Ù„Ù…Ù†ØªØ¬ Ø§Ù„ÙØ§Ø®Ø±Ø© Ù…Ø¨Ø§Ø´Ø±Ø© Ù…Ø­Ù„ Ø§Ù„Ù…Ù†ØªØ¬Ø§Øª
                    setTimeout(() => {
                        openProductCampaignIntro(prod, () => renderProductGalleryScatter(prod.name));
                    }, 200);
                });

                track.appendChild(el);
                watchItemsElements.push(el);
            });

            if (watchItemsElements.length > 0) {
                setTimeout(() => {
                    watchItemsElements[0].classList.add('active');
                }, 100 + targetCount * 60);
            }
        }

        function getProductDetails(product) {
            let gallery = product.gallery || [product.img];
            const mediaItems = gallery.map(src => ({ type: 'image', src }));

            if (product.video) {
                mediaItems.push({ type: 'video', src: product.video });
            }

            return {
                name: product.name,
                price: productDisplayPrice(product),
                desc: product.description || 'منتج مميز من متجر Ozman.',
                images: gallery,
                mediaItems,
                features: []
            };
        }

        function getGalleryThumbs(track) {
            return Array.from(track.querySelectorAll('.gallery-thumb-item'));
        }

        function pauseGalleryVideo(item) {
            const video = item.querySelector('video');
            if (video) {
                video.pause();
            }
        }

        function playGalleryVideo(item) {
            const video = item.querySelector('video');
            if (!video) return;

            video.muted = false;
            video.play().catch(() => {
                video.muted = true;
                video.play().catch(() => {});
            });
        }

        function setGallerySwipeHint(thumbEl, visible) {
            let hint = thumbEl.querySelector('.gallery-swipe-hint');

            if (!visible) {
                if (hint) hint.remove();
                return;
            }

            if (!hint) {
                hint = document.createElement('div');
                hint.className = 'gallery-swipe-hint';
                hint.setAttribute('aria-hidden', 'true');
                hint.innerHTML = '<i class="fas fa-chevron-left"></i><i class="fas fa-chevron-left"></i><i class="fas fa-chevron-left"></i>';
                thumbEl.appendChild(hint);
            }
        }

        function setOpenFullHint(thumbEl, visible) {
            let hint = thumbEl.querySelector('.open-full-hint');

            if (!visible) {
                if (hint) hint.remove();
                return;
            }

            if (!hint) {
                hint = document.createElement('div');
                hint.className = 'open-full-hint';
                hint.innerHTML = '<i class="fas fa-expand"></i> انقر لملء الشاشة';
                thumbEl.appendChild(hint);
            }

            setTimeout(() => { hint.style.opacity = '1'; }, 120);
        }

        function activateGalleryItem(track, thumbEl) {
            const allItems = getGalleryThumbs(track);
            const otherItems = allItems.filter(t => t !== thumbEl);
            const availableWidth = track.closest('.watch-grid-wrapper')?.clientWidth || window.innerWidth;
            const mobile = availableWidth < 720;
            const sideGap = mobile ? 70 : 120;
            const sideX = mobile
                ? Math.max(80, Math.min(availableWidth * 0.42, 150))
                : 350;
            const largeSize = mobile
                ? Math.max(150, Math.min(260, availableWidth - 16))
                : 440;
            const startY = -((otherItems.length - 1) * sideGap) / 2;

            otherItems.forEach((t, sideIndex) => {
                const sideY = startY + (sideIndex * sideGap);

                t.dataset.isLarge = "";
                t.style.setProperty('opacity', '1', 'important');
                t.style.setProperty('pointer-events', 'auto', 'important');
                t.style.setProperty('width', mobile ? '58px' : '100px', 'important');
                t.style.setProperty('height', mobile ? '58px' : '100px', 'important');
                t.style.setProperty('transform', `translate(calc(-50% + ${sideX}px), calc(-50% + ${sideY}px)) scale(1)`, 'important');
                t.style.setProperty('z-index', '90', 'important');
                t.style.setProperty('border', '2px solid rgba(255, 255, 255, 0.3)', 'important');
                t.style.setProperty('box-shadow', '0 0 15px rgba(0, 229, 255, 0.3), 0 5px 15px rgba(0, 0, 0, 0.5)', 'important');
                setOpenFullHint(t, false);
                setGallerySwipeHint(t, false);
                pauseGalleryVideo(t);
            });

            thumbEl.dataset.isLarge = "true";
            thumbEl.style.setProperty('width', `${largeSize}px`, 'important');
            thumbEl.style.setProperty('height', `${largeSize}px`, 'important');
            thumbEl.style.setProperty('transform', 'translate(-50%, -50%) scale(1)', 'important');
            thumbEl.style.setProperty('z-index', '100', 'important');
            thumbEl.style.setProperty('border', '4px solid var(--primary-color)', 'important');
            thumbEl.style.setProperty('box-shadow', '0 0 60px rgba(0, 229, 255, 0.45), 0 20px 50px rgba(0, 0, 0, 0.9)', 'important');

            setOpenFullHint(thumbEl, true);
            setGallerySwipeHint(thumbEl, allItems.length > 1);
            playGalleryVideo(thumbEl);
        }

        function navigateGalleryFrom(track, currentEl, direction) {
            const items = getGalleryThumbs(track);
            if (items.length < 2) return;

            const currentIndex = items.indexOf(currentEl);
            const nextIndex = (currentIndex + direction + items.length) % items.length;
            const nextItem = items[nextIndex];

            if (nextItem) {
                activateGalleryItem(track, nextItem);
                nextItem.dataset.skipFullscreenUntil = String(Date.now() + 500);
            }
        }

        function bindGallerySwipe(thumbEl, track) {
            let startX = 0;
            let startY = 0;
            let pointerActive = false;

            thumbEl.addEventListener('pointerdown', (e) => {
                if (thumbEl.dataset.isLarge !== "true") return;
                pointerActive = true;
                startX = e.clientX;
                startY = e.clientY;
                thumbEl.style.cursor = 'grabbing';
            });

            thumbEl.addEventListener('pointerup', (e) => {
                if (!pointerActive) return;
                pointerActive = false;
                thumbEl.style.cursor = 'pointer';

                const diffX = e.clientX - startX;
                const diffY = e.clientY - startY;

                if (Math.abs(diffX) > 45 && Math.abs(diffX) > Math.abs(diffY) * 1.2) {
                    thumbEl.dataset.skipFullscreenUntil = String(Date.now() + 500);
                    navigateGalleryFrom(track, thumbEl, diffX < 0 ? 1 : -1);
                }
            });

            thumbEl.addEventListener('pointerleave', () => {
                if (!pointerActive) return;
                pointerActive = false;
                thumbEl.style.cursor = 'pointer';
            });
        }

        function openProductCampaignIntro(product, afterClose) {
            const campaigns = Array.isArray(product.campaigns)
                ? product.campaigns.filter(item => item && (item.src || item.title || item.offer_note || item.offer_price))
                : [];

            if (campaigns.length === 0) {
                afterClose();
                return;
            }

            const openCampaignAt = (index) => {
                const campaign = campaigns[index];
                const hasNextCampaign = index < campaigns.length - 1;

                openCampaignModal({
                    src: campaign.src,
                    type: campaign.type === 'video' ? 'video' : 'image',
                    title: campaignOfferText(campaign, product.name),
                    continueLabel: hasNextCampaign
                        ? `الحملة التالية (${index + 2}/${campaigns.length})`
                        : 'متابعة للصور',
                    onClose: hasNextCampaign
                        ? () => openCampaignAt(index + 1)
                        : afterClose,
                });
            };

            openCampaignAt(0);
        }

        function campaignOfferText(campaign, fallbackTitle = '') {
            if (!campaign) {
                return fallbackTitle || '';
            }

            if (campaign.title) {
                return campaign.title;
            }

            const locale = document.documentElement.lang || 'ar';
            const parts = [];

            if (campaign.offer_quantity && campaign.offer_price !== null && campaign.offer_price !== undefined) {
                const price = Number(campaign.offer_price).toFixed(2);
                const bundleText = locale === 'en'
                    ? `${campaign.offer_quantity} for ${price}`
                    : locale === 'he'
                        ? `${campaign.offer_quantity} במחיר ${price}`
                        : `${campaign.offer_quantity} بسعر ${price}`;

                parts.push(bundleText);
            }

            if (campaign.offer_note) {
                parts.push(campaign.offer_note);
            }

            return parts.join(' - ') || fallbackTitle || '';
        }

        function campaignVoiceText(title) {
            return title || '';
        }

        function preferredMarketingVoice() {
            if (!('speechSynthesis' in window)) {
                return null;
            }

            const voices = window.speechSynthesis.getVoices();
            const locale = document.documentElement.lang || 'ar';
            const preferredPrefixes = locale === 'en'
                ? ['en']
                : locale === 'he'
                    ? ['he', 'iw']
                    : ['ar'];

            const localeVoice = voices.find(voice => {
                const voiceLang = (voice.lang || '').toLowerCase();

                return preferredPrefixes.some(prefix => voiceLang.startsWith(prefix));
            });

            if (localeVoice || locale === 'he' || locale === 'en') {
                return localeVoice || null;
            }

            return voices.find(voice => voice.localService)
                || voices[0]
                || null;
        }

        function speechLanguage() {
            const locale = document.documentElement.lang || 'ar';

            if (locale === 'en') return 'en-US';
            if (locale === 'he') return 'he-IL';

            return 'ar';
        }

        function speakWithRemoteVoice(text, ttsUrl, onFallback = null) {
            const cleanText = String(text || '').trim();
            if (!cleanText) return Promise.resolve();

            const chunks = cleanText.match(/.{1,180}(\s|$)/g) || [cleanText];
            let index = 0;
            const audio = new Audio();
            window.ozmanActiveTtsAudio?.pause?.();
            window.ozmanActiveTtsAudio = audio;
            const localTtsUrl = ttsUrl;

            return new Promise((resolve) => {
                const playNext = () => {
                    const chunk = (chunks[index] || '').trim();
                    if (!chunk) {
                        resolve();
                        return;
                    }

                    audio.src = `${localTtsUrl}?text=${encodeURIComponent(chunk)}`;
                    audio.play().catch(() => {
                        if (typeof onFallback === 'function') {
                            Promise.resolve(onFallback()).finally(resolve);
                        } else {
                            resolve();
                        }
                    });
                };

                audio.addEventListener('ended', () => {
                    index++;
                    if (index < chunks.length) {
                        playNext();
                    } else {
                        if (window.ozmanActiveTtsAudio === audio) {
                            window.ozmanActiveTtsAudio = null;
                        }
                        resolve();
                    }
                });

                playNext();
            });
        }

        function speakHebrewWithRemoteVoice(text, onFallback = null) {
            return speakWithRemoteVoice(text, window.OZMAN_FRONT_CONFIG?.hebrewTtsUrl || '/tts/hebrew', onFallback);
        }

        function speakArabicWithRemoteVoice(text, onFallback = null) {
            return speakWithRemoteVoice(text, window.OZMAN_FRONT_CONFIG?.arabicTtsUrl || '/tts/arabic', onFallback);
        }

        function hasHebrewText(text) {
            return /[\u0590-\u05ff]/.test(String(text || ''));
        }

        function hasArabicText(text) {
            return /[\u0600-\u06ff]/.test(String(text || ''));
        }

        function speakCampaignTitle(title) {
            if (!title) {
                return Promise.resolve();
            }

            if ((document.documentElement.lang || 'ar') === 'he' || hasHebrewText(title)) {
                return speakHebrewWithRemoteVoice(campaignVoiceText(title), () => {
                    if (!('speechSynthesis' in window)) return Promise.resolve();

                    return new Promise((resolve) => {
                        const utterance = new SpeechSynthesisUtterance(campaignVoiceText(title));
                        utterance.lang = 'he-IL';
                        utterance.onend = resolve;
                        utterance.onerror = resolve;
                        window.speechSynthesis.cancel();
                        window.speechSynthesis.speak(utterance);
                    });
                });
            }

            if ((document.documentElement.lang || 'ar') === 'ar' || hasArabicText(title)) {
                return speakArabicWithRemoteVoice(campaignVoiceText(title), () => {
                    if (!('speechSynthesis' in window)) return Promise.resolve();

                    return new Promise((resolve) => {
                        const utterance = new SpeechSynthesisUtterance(campaignVoiceText(title));
                        utterance.lang = 'ar';
                        utterance.onend = resolve;
                        utterance.onerror = resolve;
                        window.speechSynthesis.cancel();
                        window.speechSynthesis.speak(utterance);
                    });
                });
            }

            if (!('speechSynthesis' in window)) {
                return Promise.resolve();
            }

            if (!window.speechSynthesis.getVoices().length) {
                return new Promise((resolve) => {
                    window.setTimeout(() => {
                        Promise.resolve(speakCampaignTitle(title)).finally(resolve);
                    }, 250);
                });
            }

            window.speechSynthesis.cancel();

            return new Promise((resolve) => {
                const voice = preferredMarketingVoice();
                const utterance = new SpeechSynthesisUtterance(campaignVoiceText(title));
                utterance.lang = voice?.lang || speechLanguage();
                utterance.rate = 0.86;
                utterance.pitch = 1.02;
                utterance.volume = 1;
                utterance.onend = resolve;
                utterance.onerror = resolve;

                if (voice) {
                    utterance.voice = voice;
                }

                window.speechSynthesis.speak(utterance);
            });
        }

        function rewardResultVoiceText(text) {
            return text || '';
        }

        function speakRewardResult(text) {
            if (!text || !('speechSynthesis' in window)) {
                return;
            }

            if ((document.documentElement.lang || 'ar') === 'he' || hasHebrewText(text)) {
                speakHebrewWithRemoteVoice(rewardResultVoiceText(text), () => {
                    const utterance = new SpeechSynthesisUtterance(rewardResultVoiceText(text));
                    utterance.lang = 'he-IL';
                    window.speechSynthesis.cancel();
                    window.speechSynthesis.speak(utterance);
                });

                return;
            }

            if (!window.speechSynthesis.getVoices().length) {
                window.setTimeout(() => speakRewardResult(text), 250);
                return;
            }

            window.speechSynthesis.cancel();

            const voice = preferredMarketingVoice();
            const utterance = new SpeechSynthesisUtterance(rewardResultVoiceText(text));
            utterance.lang = voice?.lang || speechLanguage();
            utterance.rate = 0.86;
            utterance.pitch = 1.02;
            utterance.volume = 1;

            if (voice) {
                utterance.voice = voice;
            }

            window.speechSynthesis.speak(utterance);
        }

        if ('speechSynthesis' in window) {
            window.speechSynthesis.onvoiceschanged = preferredMarketingVoice;
        }

        function openCampaignModal({ src, type, title, continueLabel = 'متابعة للصور', onClose = null }) {
            const safeTitle = String(title || 'حملة مميزة')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
            const safeContinueLabel = String(continueLabel)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            let overlay = document.getElementById('campaignVoiceModal');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'campaignVoiceModal';
                overlay.style.cssText = 'position:fixed;inset:0;z-index:999998;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,0.86);backdrop-filter:blur(12px);opacity:0;transition:opacity .25s ease;cursor:default;';
                document.body.appendChild(overlay);
            }

            const isVideo = type === 'video';
            const hasMedia = Boolean(src);
            overlay.innerHTML = `
                <div class="campaign-circle-modal" style="width:min(92vw,640px);display:flex;flex-direction:column;align-items:center;gap:20px;cursor:auto;">
                    <div style="position:relative;width:min(76vw,430px);aspect-ratio:1;border-radius:50%;border:4px solid var(--primary-color);background:#000;box-shadow:0 0 58px rgba(0,229,255,.48),0 0 105px rgba(112,0,255,.18);">
                        <button type="button" id="closeCampaignVoiceModal" aria-label="إغلاق" style="position:absolute;top:2%;left:4%;z-index:5;width:42px;height:42px;border-radius:50%;border:1px solid rgba(255,255,255,.18);background:rgba(0,0,0,.78);color:#fff;font-size:25px;cursor:pointer;">&times;</button>
                        <div style="position:absolute;inset:14px;border-radius:50%;overflow:hidden;background:#050505;">
                        ${hasMedia && isVideo
                            ? `<video src="${src}" autoplay muted loop playsinline controls style="width:100%;height:100%;object-fit:cover;"></video>`
                            : hasMedia
                                ? `<img src="${src}" alt="${safeTitle}" style="width:100%;height:100%;object-fit:cover;">`
                                : `<div style="width:100%;height:100%;display:grid;place-items:center;padding:34px;text-align:center;color:var(--primary-color);font-family:Cairo,sans-serif;font-weight:900;font-size:clamp(24px,4vw,44px);line-height:1.35;text-shadow:0 0 18px rgba(0,229,255,.7);background:radial-gradient(circle at 50% 35%, rgba(0,229,255,.18), transparent 58%), #050505;">${safeTitle}</div>`
                        }
                        </div>
                    </div>
                    <div style="max-width:min(88vw,620px);padding:14px 28px;border-radius:24px;border:1px solid rgba(0,229,255,.62);background:rgba(0,0,0,.72);color:var(--primary-color);font-weight:900;font-size:clamp(22px,3vw,38px);line-height:1.35;text-align:center;text-shadow:0 0 16px rgba(0,229,255,.65);box-shadow:0 0 26px rgba(0,229,255,.22);">
                        ${safeTitle}
                    </div>
                    <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center;">
                        <button type="button" id="replayCampaignVoice" style="min-height:42px;padding:0 18px;border-radius:999px;border:1px solid rgba(0,229,255,.55);background:rgba(0,0,0,.58);color:var(--primary-color);font-family:Cairo,sans-serif;font-weight:900;cursor:pointer;">
                            <i class="fas fa-volume-up"></i> إعادة الصوت
                        </button>
                        <button type="button" id="continueCampaignVoiceModal" style="min-height:42px;padding:0 22px;border-radius:999px;border:0;background:var(--primary-color);color:#001014;font-family:Cairo,sans-serif;font-weight:900;cursor:pointer;box-shadow:0 0 24px rgba(0,229,255,.35);">
                            ${safeContinueLabel}
                        </button>
                    </div>
                </div>
            `;

            let didClose = false;
            let autoAdvanceTimer = null;
            let voiceFinished = false;
            let minimumDisplayFinished = false;
            const maybeAutoClose = () => {
                if (voiceFinished && minimumDisplayFinished && !didClose) {
                    close();
                }
            };
            const close = () => {
                if (didClose) {
                    return;
                }

                didClose = true;
                if (autoAdvanceTimer) {
                    clearTimeout(autoAdvanceTimer);
                    autoAdvanceTimer = null;
                }
                const activeVideo = overlay.querySelector('video');
                if (activeVideo) {
                    activeVideo.pause();
                }
                if ('speechSynthesis' in window) {
                    window.speechSynthesis.cancel();
                }
                if (window.ozmanActiveTtsAudio) {
                    window.ozmanActiveTtsAudio.pause();
                    window.ozmanActiveTtsAudio = null;
                }
                overlay.style.opacity = '0';
                setTimeout(() => {
                    overlay.style.display = 'none';
                    overlay.innerHTML = '';
                    if (typeof onClose === 'function') {
                        onClose();
                    }
                }, 250);
            };

            overlay.onclick = (event) => {
                if (event.target === overlay) {
                    close();
                }
            };

            overlay.querySelector('#closeCampaignVoiceModal').addEventListener('click', close);
            overlay.querySelector('#continueCampaignVoiceModal').addEventListener('click', close);
            const startVoicePlayback = () => {
                voiceFinished = false;
                minimumDisplayFinished = false;
                if (autoAdvanceTimer) {
                    clearTimeout(autoAdvanceTimer);
                }
                window.setTimeout(() => {
                    minimumDisplayFinished = true;
                    maybeAutoClose();
                }, isVideo ? 4500 : 2500);

                return Promise.resolve(speakCampaignTitle(title)).finally(() => {
                    voiceFinished = true;
                    maybeAutoClose();
                });
            };

            overlay.querySelector('#replayCampaignVoice').addEventListener('click', startVoicePlayback);
            overlay.style.display = 'flex';
            startVoicePlayback();
            setTimeout(() => {
                overlay.style.opacity = '1';
            }, 20);
        }

        function openFullscreenMedia(src, type = 'image', mediaItems = null, startIndex = 0) {
            let overlay = document.getElementById('globalFullscreenOverlay');
            const normalizeMediaItems = () => {
                const items = Array.isArray(mediaItems) && mediaItems.length
                    ? mediaItems
                    : [{ src, type }];

                return items
                    .filter(item => item && item.src)
                    .map(item => ({
                        src: item.src,
                        type: item.type === 'video' ? 'video' : 'image'
                    }));
            };

            const fullscreenItems = normalizeMediaItems();
            const fallbackIndex = fullscreenItems.findIndex(item => item.src === src && item.type === (type === 'video' ? 'video' : 'image'));
            const initialIndex = Number.isInteger(startIndex) && startIndex >= 0
                ? startIndex
                : fallbackIndex;

            window.ozmanFullscreenState = {
                items: fullscreenItems,
                index: (initialIndex >= 0 && initialIndex < fullscreenItems.length) ? initialIndex : 0
            };

            const closeOverlay = () => {
                if (!overlay) return;
                const activeMedia = overlay.querySelector('video');
                if (activeMedia) {
                    activeMedia.pause();
                }
                overlay.style.opacity = '0';
                setTimeout(() => {
                    overlay.style.display = 'none';
                    overlay.innerHTML = '';
                    window.ozmanFullscreenState = null;
                }, 250);
            };

            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'globalFullscreenOverlay';
                overlay.style.cssText = 'position:fixed; inset:0; width:100vw; height:100vh; background:rgba(0,0,0,0.96); z-index:999999; display:none; justify-content:center; align-items:center; cursor:zoom-out; opacity:0; transition:opacity 0.25s ease;';
                document.body.appendChild(overlay);

                overlay.addEventListener('click', (event) => {
                    if (event.target === overlay) {
                        closeOverlay();
                    }
                });

                if (!window.ozmanFullscreenEscapeBound) {
                    window.ozmanFullscreenEscapeBound = true;
                    document.addEventListener('keydown', (event) => {
                        const activeOverlay = document.getElementById('globalFullscreenOverlay');
                        if (!activeOverlay || activeOverlay.style.display === 'none') return;

                        if (event.key === 'Escape') {
                            const activeVideo = activeOverlay.querySelector('video');
                            if (activeVideo) {
                                activeVideo.pause();
                            }
                            activeOverlay.style.opacity = '0';
                            setTimeout(() => {
                                activeOverlay.style.display = 'none';
                                activeOverlay.innerHTML = '';
                                window.ozmanFullscreenState = null;
                            }, 250);
                            return;
                        }

                        if (event.key === 'ArrowLeft') {
                            event.preventDefault();
                            window.ozmanFullscreenNavigate?.(1);
                        }

                        if (event.key === 'ArrowRight') {
                            event.preventDefault();
                            window.ozmanFullscreenNavigate?.(-1);
                        }
                    });
                }
            }

            const renderFullscreenItem = () => {
                const state = window.ozmanFullscreenState;
                if (!state || !state.items.length) return;

                const current = state.items[state.index];
                overlay.innerHTML = '';

                const closeButton = document.createElement('button');
                closeButton.type = 'button';
                closeButton.innerHTML = '<i class="fas fa-chevron-right"></i> رجوع للصور';
                closeButton.style.cssText = 'position:fixed; top:24px; right:24px; z-index:1000000; display:inline-flex; align-items:center; gap:10px; border:1px solid rgba(0,229,255,0.7); background:rgba(0,0,0,0.78); color:#00e5ff; padding:12px 18px; border-radius:999px; font-family:Cairo, sans-serif; font-weight:900; font-size:16px; box-shadow:0 0 20px rgba(0,229,255,0.35); cursor:pointer;';
                closeButton.addEventListener('click', (event) => {
                    event.stopPropagation();
                    closeOverlay();
                });

                const media = document.createElement(current.type === 'video' ? 'video' : 'img');
                media.src = current.src;
                media.style.cssText = 'width:100vw; height:100vh; object-fit:contain; display:block;';
                media.addEventListener('click', (event) => event.stopPropagation());

                if (current.type === 'video') {
                    media.controls = true;
                    media.autoplay = true;
                    media.playsInline = true;
                }

                overlay.appendChild(closeButton);
                overlay.appendChild(media);

                if (state.items.length > 1) {
                    const counter = document.createElement('div');
                    counter.innerText = `${state.index + 1} / ${state.items.length}`;
                    counter.style.cssText = 'position:fixed; bottom:26px; left:50%; transform:translateX(-50%); z-index:1000000; min-width:74px; text-align:center; padding:8px 14px; border-radius:999px; border:1px solid rgba(0,229,255,.45); background:rgba(0,0,0,.72); color:#fff; font-family:Cairo,sans-serif; font-weight:900; box-shadow:0 0 18px rgba(0,229,255,.18);';

                    const makeNavButton = (dir, icon, side) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.innerHTML = `<i class="${icon}"></i>`;
                        button.setAttribute('aria-label', dir > 0 ? 'الصورة التالية' : 'الصورة السابقة');
                        button.style.cssText = `position:fixed; top:50%; ${side}:28px; transform:translateY(-50%); z-index:1000000; width:58px; height:58px; border-radius:50%; border:1px solid rgba(0,229,255,.72); background:rgba(0,0,0,.72); color:#00e5ff; font-size:24px; cursor:pointer; box-shadow:0 0 22px rgba(0,229,255,.28);`;
                        button.addEventListener('click', (event) => {
                            event.stopPropagation();
                            window.ozmanFullscreenNavigate?.(dir);
                        });
                        return button;
                    };

                    overlay.appendChild(makeNavButton(-1, 'fas fa-chevron-right', 'right'));
                    overlay.appendChild(makeNavButton(1, 'fas fa-chevron-left', 'left'));
                    overlay.appendChild(counter);
                }
            };

            window.ozmanFullscreenRender = renderFullscreenItem;
            window.ozmanFullscreenNavigate = (direction) => {
                const state = window.ozmanFullscreenState;
                if (!state || state.items.length < 2) return;
                const activeMedia = overlay.querySelector('video');
                if (activeMedia) {
                    activeMedia.pause();
                }
                state.index = (state.index + direction + state.items.length) % state.items.length;
                renderFullscreenItem();
            };

            let startX = 0;
            overlay.onpointerdown = (event) => {
                startX = event.clientX;
            };
            overlay.onpointerup = (event) => {
                const state = window.ozmanFullscreenState;
                if (!state || state.items.length < 2 || event.target !== overlay) return;
                const diffX = event.clientX - startX;
                if (Math.abs(diffX) > 55) {
                    window.ozmanFullscreenNavigate?.(diffX < 0 ? 1 : -1);
                }
            };

            renderFullscreenItem();
            overlay.style.display = 'flex';

            setTimeout(() => {
                overlay.style.opacity = '1';
            }, 10);
        }

        function openFullProductModal(productName) {
            const pModal = document.getElementById('productGalleryModal');
            if (!pModal) return;

            let foundProduct = null;
            if (carouselProductsDb[productName]) {
                foundProduct = carouselProductsDb[productName];
            } else {
                for (let d in activeProductsDb) {
                    const match = activeProductsDb[d].find(p => p.name === productName);
                    if (match) {
                        foundProduct = match;
                        break;
                    }
                }
            }

            if (!foundProduct) return;

            // ØªØ¹Ø¨Ø¦Ø© Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ù„Ù…ÙˆØ¯Ø§Ù„
            const titleEl = document.getElementById('modalProductTitle');
            const priceEl = document.getElementById('modalProductPrice');
            const packagingPricesEl = document.getElementById('modalPackagingPrices');
            const mainImgEl = document.getElementById('modalMainImg');
            const descEl = document.getElementById('modalProductDesc');
            const qtyVal = document.getElementById('qtyVal');

            if (titleEl) titleEl.innerText = foundProduct.name;
            if (priceEl) priceEl.innerText = productDisplayPrice(foundProduct);
            if (packagingPricesEl) {
                const packagingPrices = [
                    ['سعر العبوة', foundProduct.package_price],
                    ['سعر المشطاح', foundProduct.pallet_price],
                    ['سعر الكرتونة', foundProduct.carton_price],
                ].filter((item) => item[1]);

                packagingPricesEl.hidden = packagingPrices.length === 0;
                packagingPricesEl.innerHTML = packagingPrices.map(([label, value]) => `
                    <div class="product-packaging-price">
                        ${escapeCartHtml(label)}
                        <strong>${escapeCartHtml(value)}</strong>
                    </div>
                `).join('');
            }
            if (mainImgEl) mainImgEl.src = foundProduct.img;
            if (descEl) descEl.innerText = foundProduct.description || 'منتج مميز من متجر Ozman.';
            if (qtyVal) qtyVal.innerText = '1';

            // ØªØ¹Ø¨Ø¦Ø© Ø§Ù„ØµÙˆØ± Ø§Ù„Ù…ØµØºØ±Ø© ÙÙŠ Ø§Ù„Ù…ÙˆØ¯Ø§Ù„
            const thumbsRow = document.getElementById('modalThumbnailsRow');
            if (thumbsRow) {
                thumbsRow.innerHTML = '';
                const galleryImages = foundProduct.gallery || [foundProduct.img];
                galleryImages.forEach((imgSrc, idx) => {
                    const thumb = document.createElement('div');
                    thumb.className = `thumbnail-item ${idx === 0 ? 'active' : ''}`;
                    thumb.innerHTML = `<img src="${imgSrc}" alt="thumbnail">`;
                    thumb.addEventListener('click', () => {
                        document.querySelectorAll('#modalThumbnailsRow .thumbnail-item').forEach(t => t.classList.remove('active'));
                        thumb.classList.add('active');
                        if (mainImgEl) mainImgEl.src = imgSrc;
                    });
                    thumbsRow.appendChild(thumb);
                });

                if (mainImgEl) {
                    mainImgEl.style.cursor = 'zoom-in';
                    mainImgEl.onclick = () => {
                        const activeIndex = Array.from(thumbsRow.querySelectorAll('.thumbnail-item'))
                            .findIndex(item => item.classList.contains('active'));
                        openFullscreenMedia(
                            mainImgEl.src,
                            'image',
                            galleryImages.map(imageSrc => ({ src: imageSrc, type: 'image' })),
                            activeIndex >= 0 ? activeIndex : 0
                        );
                    };
                }
            }

            // ØªÙØ¹ÙŠÙ„ Ø§Ù„Ù…ÙˆØ¯Ø§Ù„
            pModal.classList.add('active');
        }

        function renderProductGalleryScatter(productName) {
            activeProductTitle = productName;

            const track = document.getElementById('watchGridTrack');
            const header = document.getElementById('productsScatterHeader');
            const titleEl = document.getElementById('productsScatterTitle');
            const backBtn = document.getElementById('backToDeptsBtn');

            if (!track || !header || !titleEl || !backBtn) return;

            let foundProduct = null;
            let deptName = '';

            // ØªØ­Ù‚Ù‚ Ø£ÙˆÙ„Ø§Ù‹ Ø¥Ø°Ø§ ÙƒØ§Ù† Ø§Ù„Ù…Ù†ØªØ¬ Ù…Ù† Ø§Ù„Ù…Ù†ØªØ¬Ø§Øª Ø§Ù„Ù…Ø³ØªÙ‚Ù„Ø© Ù„Ù„ÙƒØ§Ø±ÙˆØ³ÙŠÙ„ Ø§Ù„Ø¹Ù„ÙˆÙŠ
            if (carouselProductsDb[productName]) {
                foundProduct = carouselProductsDb[productName];
                deptName = ''; // Ù…Ù†ØªØ¬ Ù…Ø³ØªÙ‚Ù„ Ù„ÙŠØ³ Ù„Ù‡ ÙØ¦Ø© Ø³ÙÙ„ÙŠØ©
            } else {
                // ÙˆØ¥Ù„Ø§ Ø§Ø¨Ø­Ø« ÙÙŠ Ù‚Ø§Ø¹Ø¯Ø© ÙØ¦Ø§Øª Ø§Ù„Ù…Ù†ØªØ¬Ø§Øª Ø§Ù„Ø³ÙÙ„ÙŠØ© Ø§Ù„Ù…Ø¹ØªØ§Ø¯Ø©
                for (let d in activeProductsDb) {
                    const match = activeProductsDb[d].find(p => p.name === productName);
                    if (match) {
                        foundProduct = match;
                        deptName = d;
                        break;
                    }
                }
            }

            if (!foundProduct) return;

            const details = getProductDetails(foundProduct);
            activeDeptTitle = deptName;

            // ØªÙØ¹ÙŠÙ„ Ø§Ù„Ù‡ÙŠØ¯Ø± ÙˆØ¥Ø®ÙØ§Ø¡ Ø§Ø³Ù… Ø§Ù„Ù…Ù†ØªØ¬ Ø¨Ù†Ø§Ø¡Ù‹ Ø¹Ù„Ù‰ Ø·Ù„Ø¨ Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù…
            header.style.display = 'flex';
            header.style.opacity = '1';
            header.style.transform = 'translateY(0)';

            titleEl.style.display = 'none';

            // Ø¥Ø®ÙØ§Ø¡ Ø§Ù„ÙˆØµÙ Ø§Ù„Ø³Ø±ÙŠØ¹ Ù„Ù„Ù…Ù†ØªØ¬ Ø¨Ù†Ø§Ø¡Ù‹ Ø¹Ù„Ù‰ Ø·Ù„Ø¨ Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù… Ù„Ø¹Ø¯Ù… Ø§Ù„Ø­Ø§Ø¬Ø© Ù„Ù‡ Ù‡Ù†Ø§
            const descEl = document.getElementById('productsScatterDesc');
            if (descEl) {
                descEl.style.display = 'none';
            }

            backBtn.innerHTML = `<i class="fas fa-chevron-right"></i> ${escapeCartHtml(frontLabel('backToProducts', 'عودة للمنتجات'))}`;

            track.innerHTML = '';
            watchItemsElements = [];

            const mediaItems = details.mediaItems || details.images.map(src => ({ type: 'image', src }));
            if (mediaItems.length === 0) return;

            const uniqueMediaItems = mediaItems.filter((item, index, self) => {
                return self.findIndex(candidate => candidate.type === item.type && candidate.src === item.src) === index;
            });
            const targetCount = uniqueMediaItems.length;

            const positions = [];
            const availableWidth = track.closest('.watch-grid-wrapper')?.clientWidth || window.innerWidth;
            const mobile = availableWidth < 720;
            const spreadRadius = mobile
                ? Math.max(45, Math.min(availableWidth * 0.32, 95))
                : 250;
            const minSpacing = mobile ? 70 : 160;

            for (let i = 0; i < targetCount; i++) {
                let x, y;
                let overlap;
                let attempts = 0;
                let currentMinSpacing = minSpacing;
                do {
                    overlap = false;
                    const r = Math.sqrt(Math.random()) * spreadRadius;
                    const theta = Math.random() * 2 * Math.PI;
                    x = r * Math.cos(theta);
                    y = r * Math.sin(theta);

                    for (let p of positions) {
                        const dx = p.x - x;
                        const dy = p.y - y;
                        if (Math.sqrt(dx * dx + dy * dy) < currentMinSpacing) {
                            overlap = true;
                            break;
                        }
                    }
                    attempts++;
                    if (attempts > 50) {
                        currentMinSpacing = minSpacing * 0.85;
                    }
                    if (attempts > 100) {
                        currentMinSpacing = minSpacing * 0.7;
                    }
                } while (overlap && attempts < 200);
                positions.push({ x, y });
            }

            uniqueMediaItems.forEach((mediaItem, i) => {
                const pos = positions[i];
                const thumbEl = document.createElement('div');
                thumbEl.className = 'watch-item gallery-thumb-item';
                
                thumbEl.dataset.origX = pos.x;
                thumbEl.dataset.origY = pos.y;
                thumbEl.dataset.mediaSrc = mediaItem.src;
                thumbEl.dataset.mediaType = mediaItem.type;
                thumbEl.dataset.mediaTitle = mediaItem.title || '';
                thumbEl.dataset.isCampaign = mediaItem.isCampaign ? 'true' : '';
                thumbEl.style.transform = `translate(calc(-50% + ${pos.x}px), calc(-50% + ${pos.y}px)) scale(0)`;
                thumbEl.style.opacity = '0';
                thumbEl.style.transition = 'all 0.85s cubic-bezier(0.175, 0.885, 0.32, 1.35)';

                thumbEl.innerHTML = mediaItem.type === 'video'
                    ? `<video src="${mediaItem.src}" preload="metadata" playsinline loop style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;"></video><span class="open-full-hint" style="opacity:1"><i class="fas fa-play"></i> فيديو</span>`
                    : `<img src="${mediaItem.src}" alt="thumbnail" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;

                thumbEl.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (Number(thumbEl.dataset.skipFullscreenUntil || 0) > Date.now()) {
                        return;
                    }

                    if (thumbEl.dataset.isCampaign === 'true' && thumbEl.dataset.mediaType !== 'video') {
                        openCampaignModal({
                            src: thumbEl.dataset.mediaSrc,
                            type: thumbEl.dataset.mediaType,
                            title: thumbEl.dataset.mediaTitle
                        });
                        return;
                    }
                    
                    if (!thumbEl.dataset.isLarge) {
                        activateGalleryItem(track, thumbEl);
                    } else {
                        const activeVideo = thumbEl.querySelector('video');
                        if (activeVideo) {
                            activeVideo.pause();
                        }
                        const galleryMediaItems = getGalleryThumbs(track).map(item => ({
                            src: item.dataset.mediaSrc,
                            type: item.dataset.mediaType
                        }));
                        const activeIndex = galleryMediaItems.findIndex(item => item.src === thumbEl.dataset.mediaSrc && item.type === thumbEl.dataset.mediaType);
                        openFullscreenMedia(
                            thumbEl.dataset.mediaSrc,
                            thumbEl.dataset.mediaType,
                            galleryMediaItems,
                            activeIndex >= 0 ? activeIndex : 0
                        );
                        return;
                        // Ø¹Ù†Ø¯ Ø§Ù„Ø¶ØºØ· Ø§Ù„Ù…Ø²Ø¯ÙˆØ¬ Ø¹Ù„Ù‰ Ø§Ù„Ø¯Ø§Ø¦Ø±Ø© Ø§Ù„ÙƒØ¨ÙŠØ±Ø© ØªÙØªØ­ Ù…Ù„Ø¡ Ø§Ù„Ø´Ø§Ø´Ø©
                        let overlay = document.getElementById('globalFullscreenOverlay');
                        if (!overlay) {
                            overlay = document.createElement('div');
                            overlay.id = 'globalFullscreenOverlay';
                            overlay.style.cssText = 'position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.98); z-index:999999; display:none; justify-content:center; align-items:center; cursor:zoom-out; opacity:0; transition:opacity 0.3s ease;';
                            
                            const img = document.createElement('img');
                            img.id = 'globalFullscreenImg';
                            img.style.cssText = 'max-width:95vw; max-height:95vh; object-fit:contain; border-radius:15px; box-shadow: 0 0 50px rgba(0,229,255,0.3); transition: transform 0.3s ease; transform: scale(0.9);';
                            overlay.appendChild(img);
                            
                            overlay.addEventListener('click', () => {
                                overlay.style.opacity = '0';
                                img.style.transform = 'scale(0.9)';
                                setTimeout(() => {
                                    overlay.style.display = 'none';
                                }, 300);
                            });
                            
                            document.body.appendChild(overlay);
                        }
                        
                        const overlayImg = overlay.querySelector('#globalFullscreenImg');
                        if (overlayImg) {
                            overlayImg.src = imgSrc;
                            overlay.style.display = 'flex';
                            setTimeout(() => {
                                overlay.style.opacity = '1';
                                overlayImg.style.transform = 'scale(1)';
                            }, 10);
                        }
                    }
                });

                bindGallerySwipe(thumbEl, track);

                track.appendChild(thumbEl);

                setTimeout(() => {
                    thumbEl.style.setProperty('opacity', '1', 'important');
                    thumbEl.style.setProperty('transform', `translate(calc(-50% + ${pos.x}px), calc(-50% + ${pos.y}px)) scale(1)`, 'important');
                }, 100 + i * 80);
            });

            track.addEventListener('click', (e) => {
                if (e.target === track) {
                    track.querySelectorAll('.gallery-thumb-item').forEach(t => {
                        t.dataset.isLarge = "";
                        t.style.setProperty('width', '110px', 'important');
                        t.style.setProperty('height', '110px', 'important');
                        t.style.setProperty('transform', `translate(calc(-50% + ${t.dataset.origX}px), calc(-50% + ${t.dataset.origY}px)) scale(1)`, 'important');
                        t.style.setProperty('z-index', '5', 'important');
                        t.style.setProperty('border', '2px solid rgba(255, 255, 255, 0.3)', 'important');
                        t.style.setProperty('box-shadow', '0 0 15px rgba(0, 229, 255, 0.3), 0 5px 15px rgba(0, 0, 0, 0.5)', 'important');
                        t.style.setProperty('opacity', '1', 'important');
                        t.style.setProperty('pointer-events', 'auto', 'important');
                        
                        const hint = t.querySelector('.open-full-hint');
                        if (hint) {
                            hint.remove();
                        }

                        const swipeHint = t.querySelector('.gallery-swipe-hint');
                        if (swipeHint) {
                            swipeHint.remove();
                        }
                    });
                }
            });
        }

        function renderProductVariations(parentProductName) {
            activeProductTitle = parentProductName;

            const track = document.getElementById('watchGridTrack');
            const header = document.getElementById('productsScatterHeader');
            const titleEl = document.getElementById('productsScatterTitle');

            if (!track || !header || !titleEl) return;

            // ØªØºÙŠÙŠØ± Ø§Ù„Ø¹Ù†ÙˆØ§Ù† Ù„ÙŠØ¹ÙƒØ³ Ø§Ù„Ù…Ù†ØªØ¬ Ø§Ù„Ø£Ø¨
            titleEl.innerText = `المنتجات التابعة لـ: ${parentProductName}`;

            track.innerHTML = '';
            watchItemsElements = [];

            const variations = getRelatedProducts(parentProductName);
            const targetCount = variations.length;

            const positions = [];
            const spreadRadius = 250;
            const minSpacing = 160;

            for (let i = 0; i < targetCount; i++) {
                let x, y;
                let overlap;
                let attempts = 0;
                let currentMinSpacing = minSpacing;
                do {
                    overlap = false;
                    const r = Math.sqrt(Math.random()) * spreadRadius;
                    const theta = Math.random() * 2 * Math.PI;
                    x = r * Math.cos(theta);
                    y = r * Math.sin(theta);

                    for (let p of positions) {
                        const dx = p.x - x;
                        const dy = p.y - y;
                        if (Math.sqrt(dx * dx + dy * dy) < currentMinSpacing) {
                            overlap = true;
                            break;
                        }
                    }
                    attempts++;
                    if (attempts > 50) {
                        currentMinSpacing = minSpacing * 0.85;
                    }
                    if (attempts > 100) {
                        currentMinSpacing = minSpacing * 0.7;
                    }
                } while (overlap && attempts < 200);
                positions.push({ x, y });
            }

            positions.forEach((pos, i) => {
                const prod = variations[i];
                const el = document.createElement('div');
                el.className = 'watch-item product-variation-item';

                el.style.setProperty('--pos-x', pos.x + 'px');
                el.style.setProperty('--pos-y', pos.y + 'px');
                el.style.animation = `bubblePop 0.5s ease forwards ${i * 0.06}s`;
                el.style.opacity = '0';

                el.innerHTML = `
                    <img src="${prod.img}" alt="${prod.name}">
                    <span class="dept-title" style="bottom: -25px;">
                        ${prod.name}
                    </span>
                `;
                el.style.transform = `translate(calc(-50% + ${pos.x}px), calc(-50% + ${pos.y}px))`;

                el.addEventListener('click', () => {
                    document.querySelectorAll('.watch-item').forEach(item => item.classList.remove('active'));
                    el.classList.add('active');
                });

                track.appendChild(el);
                watchItemsElements.push(el);
            });

            if (watchItemsElements.length > 0) {
                setTimeout(() => {
                    watchItemsElements[0].classList.add('active');
                }, 100 + targetCount * 60);
            }
        }

        function backToDepartments() {
            const track = document.getElementById('watchGridTrack');
            const header = document.getElementById('productsScatterHeader');
            const descEl = document.getElementById('productsScatterDesc');

            if (!track || !header) return;

            // Ø¥Ø®ÙØ§Ø¡ Ø§Ù„ÙˆØµÙ Ø§Ù„Ø³Ø±ÙŠØ¹ Ø¹Ù†Ø¯ Ø§Ù„Ø¹ÙˆØ¯Ø© Ù„Ù„Ø£Ù‚Ø³Ø§Ù…
            if (descEl) descEl.style.display = 'none';

            // Ø¥Ø®ÙØ§Ø¡ Ø§Ù„Ù‡ÙŠØ¯Ø± ÙˆØ§Ù„ØªØ±Ø§Ùƒ Ø¨Ø­Ø±ÙƒØ© Ù†Ø§Ø¹Ù…Ø©
            header.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            header.style.opacity = '0';
            header.style.transform = 'translateY(-10px)';

            track.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            track.style.opacity = '0';
            track.style.transform = 'scale(0.8)';

            setTimeout(() => {
                header.style.display = 'none';
                renderDepartmentsForCenter(activeCenterIndex, activePersonContext);
                track.style.opacity = '1';
                track.style.transform = 'scale(1)';
            }, 300);
        }

        function handleBackClick() {
            const track = document.getElementById('watchGridTrack');
            if (!track) return;

            if (activeProductTitle !== '') {
                if (activeDeptTitle) {
                    // Ø¥Ø°Ø§ ÙƒÙ†Ø§ Ù†Ø¹Ø±Ø¶ Ù…Ù†ØªØ¬Ø§Ù‹ Ù…Ù† ÙØ¦Ø© Ù…Ø¹ÙŠÙ†Ø©ØŒ Ù†Ø¹ÙˆØ¯ Ù„ØªÙ„Ùƒ Ø§Ù„ÙØ¦Ø©
                    track.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    track.style.opacity = '0';
                    track.style.transform = 'scale(0.8)';

                    setTimeout(() => {
                        renderProductsScatter(activeDeptTitle);
                        track.style.opacity = '1';
                        track.style.transform = 'scale(1)';
                    }, 300);
                } else {
                    // Ø¥Ø°Ø§ ÙƒÙ†Ø§ Ù†Ø¹Ø±Ø¶ Ù…Ù†ØªØ¬Ø§Ù‹ Ù…Ø³ØªÙ‚Ù„Ø§Ù‹ (Ù…Ù† Ø§Ù„ÙƒØ§Ø±ÙˆØ³ÙŠÙ„ Ø§Ù„Ø¹Ù„ÙˆÙŠ)ØŒ Ù†Ø¹ÙˆØ¯ Ù„Ù„Ø£Ù‚Ø³Ø§Ù… Ø§Ù„Ø±Ø¦ÙŠØ³ÙŠØ© Ù…Ø¨Ø§Ø´Ø±Ø©
                    backToDepartments();
                }
            } else {
                // Ø¥Ø°Ø§ ÙƒÙ†Ø§ ÙÙŠ Ù‚Ø§Ø¦Ù…Ø© Ù…Ù†ØªØ¬Ø§Øª Ø§Ù„Ù‚Ø³Ù…ØŒ Ù†Ø¹ÙˆØ¯ Ù„Ù„Ø£Ù‚Ø³Ø§Ù… Ø§Ù„Ø±Ø¦ÙŠØ³ÙŠØ©
                backToDepartments();
            }
        }

        function renderDepartmentsForCenter(centerIndex, person = null) {
            const track = document.getElementById('watchGridTrack');
            const header = document.getElementById('productsScatterHeader');
            if (!track) return;

            // Ø¥Ø®ÙØ§Ø¡ Ø§Ù„Ù‡ÙŠØ¯Ø± Ø§Ù„Ø®Ø§Øµ Ø¨Ø§Ù„Ù…Ù†ØªØ¬Ø§Øª Ø¹Ù†Ø¯ ØªØ¨Ø¯ÙŠÙ„ Ø§Ù„Ù…Ø±Ø§ÙƒØ²
            if (header) {
                header.style.display = 'none';
            }

            track.innerHTML = '';
            watchItemsElements = [];
            activeDeptTitle = '';
            activeProductTitle = '';

            const center = centersData[centerIndex % centersData.length];
            // No more duplication! Render each department exactly once.
            const depsToRender = person?.departments || center.departments || [];

            const positions = scatterLayout(depsToRender.length, {
                footprintWidthScale: 2.75,
                footprintHeightExtra: 104,
                ringGapScale: 1.05,
            });

            positions.forEach((pos, i) => {
                const prod = depsToRender[i];
                const el = document.createElement('div');
                el.className = 'watch-item department-scatter-item';

                el.style.setProperty('--pos-x', pos.x + 'px');
                el.style.setProperty('--pos-y', pos.y + 'px');
                el.style.setProperty('--scatter-item-size', pos.itemSize + 'px');
                el.style.animation = `bubblePop 0.5s ease forwards ${i * 0.05}s`;
                el.style.opacity = '0';

                // Ø¹Ø±Ø¶ Ø§Ù„ØµÙˆØ±Ø© ÙˆØ§Ø³Ù… Ø§Ù„Ù‚Ø³Ù… Ø¨Ø´ÙƒÙ„ Ù…ØªÙ†Ø§Ø³Ù‚ ÙˆÙ…Ù…ÙŠØ² ØªØ­Øª Ø§Ù„Ù„ÙˆØºÙˆ
                el.innerHTML = `
                    <img src="${prod.img}" alt="${prod.title}">
                    <span class="dept-title">${prod.title}</span>
                `;
                el.style.transform = `translate(calc(-50% + ${pos.x}px), calc(-50% + ${pos.y}px))`;

                el.addEventListener('click', () => {
                    watchItemsElements.forEach(item => item.classList.remove('active'));
                    el.classList.add('active');

                    // Ø§Ù†ØªÙ‚Ø§Ù„ Ù†Ø§Ø¹Ù… ÙˆÙ…Ø¨Ù‡Ø± Ù„Ø¹Ø±Ø¶ Ø§Ù„Ù…Ù†ØªØ¬Ø§Øª Ø§Ù„ØªØ§Ø¨Ø¹Ø© Ù„Ù‡Ø°Ø§ Ø§Ù„Ù‚Ø³Ù… Ø¨Ù†Ø¸Ø§Ù… Ø¹Ø´ÙˆØ§Ø¦ÙŠ Ù…Ø¨Ø¹Ø«Ø±
                    setTimeout(() => {
                        track.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        track.style.opacity = '0';
                        track.style.transform = 'scale(0.8)';

                        setTimeout(() => {
                            if (prod.kind === 'shop' && prod.shop_id) {
                                const shopIndex = centersData.findIndex((shop) => String(shop.id || '') === String(prod.shop_id));
                                if (shopIndex >= 0) {
                                    selectCategory(shopIndex);
                                }
                            } else {
                                renderProductsScatter(prod.title);
                            }
                            track.style.opacity = '1';
                            track.style.transform = 'scale(1)';
                        }, 300);
                    }, 300);
                });

                track.appendChild(el);
                watchItemsElements.push(el);
            });

            if (watchItemsElements.length > 0) {
                setTimeout(() => {
                    watchItemsElements[0].classList.add('active');
                }, 100 + depsToRender.length * 50);
            }
        }

        function initRadial() {
            renderActiveShopHeader(centersData[0]);
            activeProductsDb = centersData[0]?.products_db || productsDb;
            activePersonContext = null;
            renderDepartmentsForCenter(0);
        }

        function selectCategory(index) {
            activeCenterIndex = index;
            activeProductsDb = centersData[index % centersData.length]?.products_db || productsDb;
            activePersonContext = null;
            renderActiveShopHeader(centersData[index % centersData.length]);
            renderDepartmentsForCenter(index);
        }

        window.addEventListener('DOMContentLoaded', () => {
            initRadial();
            applyInitialPersonContext();

            // Ø±Ø¨Ø· Ø²Ø± Ø§Ù„Ø¹ÙˆØ¯Ø© Ù„Ù„Ø£Ù‚Ø³Ø§Ù… Ø¨Ø­Ø¯Ø« Ø§Ù„Ù†Ù‚Ø±
            const backBtn = document.getElementById('backToDeptsBtn');
            if (backBtn) {
                backBtn.addEventListener('click', handleBackClick);
            }

            // Logo Dropdown Logic
            const logos = document.querySelectorAll('.logo');
            logos.forEach(logo => {
                const img = logo.querySelector('.logo-img');
                const dropdown = logo.querySelector('.logo-dropdown');
                if (img && dropdown) {
                    img.addEventListener('click', (e) => {
                        e.stopPropagation();
                        // Close other dropdowns
                        document.querySelectorAll('.logo-dropdown.show').forEach(d => {
                            if (d !== dropdown) d.classList.remove('show');
                        });
                        dropdown.classList.toggle('show');
                    });
                }
            });

            document.addEventListener('click', (e) => {
                document.querySelectorAll('.logo-dropdown.show').forEach(dropdown => {
                    const logo = dropdown.closest('.logo');
                    if (logo && !logo.contains(e.target)) {
                        dropdown.classList.remove('show');
                    }
                });
            });
        });

        // --- 3D Carousel Logic ---
        function update3DCarousel(items, currentIndex) {
            items.forEach((item, i) => {
                item.className = 'carousel-item-3d prod-item';
                let diff = i - currentIndex;
                const len = items.length;
                if (diff > len / 2) diff -= len;
                if (diff < -len / 2) diff += len;

                if (diff === 0) {
                    item.classList.add('active');
                } else if (diff === -1) {
                    item.classList.add('left1');
                } else if (diff === -2) {
                    item.classList.add('left2');
                } else if (diff === 1) {
                    item.classList.add('right1');
                } else if (diff === 2) {
                    item.classList.add('right2');
                } else {
                    item.classList.add('hidden');
                }
            });

        }

        const prodItems = document.querySelectorAll('.prod-item');
        let currentProdIdx = 2;
        prodItems.forEach((item, i) => {
            item.addEventListener('click', () => {
                const isActive = item.classList.contains('active');

                if (!isActive) {
                    // Ø¥Ø°Ø§ Ù„Ù… ÙŠÙƒÙ† Ø§Ù„Ø¹Ù†ØµØ± Ù†Ø´Ø·Ø§Ù‹ØŒ ÙŠØªÙ… ØªØ¯ÙˆÙŠØ±Ù‡ Ù„Ù„Ù…Ù†ØªØµÙ ÙÙ‚Ø·
                    currentProdIdx = i;
                    update3DCarousel(prodItems, currentProdIdx);
                } else {
                    // Ø¥Ø°Ø§ ÙƒØ§Ù† ÙÙŠ Ø§Ù„Ù…Ù†ØªØµÙ (Ø§Ù„Ø¨Ù†Øµ)ØŒ ÙŠÙØªØ­ ÙÙŠ Ø§Ù„Ø£Ø³ÙÙ„ ÙƒÙ…Ø§ Ù‡Ùˆ Ù…ØµÙ…Ù…
                    const ozmanCategory = item.getAttribute('data-ozman-category');
                    const prodName = item.getAttribute('data-product-name');
                    if (prodName) {
                        // Ø§Ù„ØªÙ…Ø±ÙŠØ± Ø§Ù„Ø³Ù„Ø³ Ù„Ù‚Ø³Ù… Ø§Ù„Ù…Ø¹Ø±Ø¶ Ø§Ù„Ø¯Ø§Ø¦Ø±ÙŠ Ø§Ù„Ù…Ø¨Ø¹Ø«Ø± ÙÙŠ Ø§Ù„Ø£Ø³ÙÙ„ Ù„Ø³Ù‡ÙˆÙ„Ø© Ø§Ù„Ù…Ø´Ø§Ù‡Ø¯Ø©
                        const radialSection = document.querySelector('.radial-section');
                        if (radialSection) {
                            radialSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }

                        // Ø¹Ø±Ø¶ ØµÙˆØ± Ø§Ù„Ù…Ù†ØªØ¬ ÙˆØªÙØ§ØµÙŠÙ„Ù‡ Ø§Ù„Ù…Ø¨Ø¹Ø«Ø±Ø© Ù…Ø¨Ø§Ø´Ø±Ø©
                        setTimeout(() => {
                            if (ozmanCategory) {
                                renderProductsScatter(ozmanCategory);
                            } else {
                                renderProductGalleryScatter(prodName);
                            }
                        }, 500);
                    }
                }
            });
        });

        window.addEventListener('load', () => {
            if (prodItems.length > 0) {
                update3DCarousel(prodItems, currentProdIdx);
            }
        });

        // --- Infinite Vertical Logic (Manual Scroll) ---
        function setupInfiniteVertical(container, track, data, isMain = false) {
            if (!container || !track) return;
            const itemHeight = isMain ? 180 : 120; // approximate height + gap
            const totalCount = data.length;
            if (totalCount === 0) return;
            const repeatCount = isMain ? 3 : Math.max(5, Math.ceil(18 / totalCount));

            track.innerHTML = '';

            Array.from({ length: repeatCount }, () => data).flat().forEach((itemData, index) => {
                const item = document.createElement('div');
                item.className = isMain ? 'v-item' : 'side-circle v-item';
                if (isMain) {
                    item.innerHTML = `<div class="v-card"><img src="${itemData.img}" alt="${itemData.title}"></div>`;
                    item.dataset.title = itemData.title;
                    item.dataset.desc = itemData.desc;
                } else {
                    item.innerHTML = `<img src="${itemData.img}" alt="${itemData.title}">`;
                    item.onclick = () => selectCategory(index % totalCount);
                }
                track.appendChild(item);
            });

            if (!isMain) {
                const updateStoreCarousel = () => {
                    const items = Array.from(track.querySelectorAll('.v-item'));
                    const centerY = container.offsetHeight / 2;
                    const containerRect = container.getBoundingClientRect();

                    items.forEach((item) => {
                        const rect = item.getBoundingClientRect();
                        const itemCenter = rect.top + rect.height / 2 - containerRect.top;
                        const dist = Math.abs(itemCenter - centerY);
                        const scale = Math.max(0.84, Math.min(1.28, (1 - dist / 260) * 1.38));

                        item.style.opacity = Math.max(0.72, 1 - dist / 520);
                        item.style.transform = `scale(${scale})`;
                        item.classList.toggle('active', dist < 72);
                    });
                };

                requestAnimationFrame(() => {
                    const items = track.querySelectorAll('.v-item');
                    const cycleStart = items[0]?.offsetTop || 0;
                    const nextCycleStart = items[totalCount]?.offsetTop || 0;
                    const cycleSpan = Math.max(1, nextCycleStart - cycleStart);

                    container.scrollTop = cycleSpan * 2;
                    let storeUpdateFrame = 0;

                    container.onscroll = () => {
                        const currentScroll = container.scrollTop;

                        if (currentScroll >= cycleSpan * 3) {
                            container.scrollTop = currentScroll - cycleSpan;
                        } else if (currentScroll <= cycleSpan) {
                            container.scrollTop = currentScroll + cycleSpan;
                        }

                        if (storeUpdateFrame) return;
                        storeUpdateFrame = requestAnimationFrame(() => {
                            storeUpdateFrame = 0;
                            updateStoreCarousel();
                        });
                    };

                    updateStoreCarousel();
                });

                return;
            }

            // Initial Scroll Position (Middle set)
            const middleOffset = totalCount * itemHeight;
            container.scrollTop = middleOffset;
            let verticalUpdateFrame = 0;

            container.onscroll = () => {
                const currentScroll = container.scrollTop;
                const maxScroll = (totalCount * 2) * itemHeight;
                const minScroll = totalCount * itemHeight;

                // Infinite Jump Logic
                if (currentScroll >= maxScroll) {
                    container.scrollTop = currentScroll - (totalCount * itemHeight);
                } else if (currentScroll <= (totalCount * 0.5) * itemHeight) {
                    container.scrollTop = currentScroll + (totalCount * itemHeight);
                }

                if (verticalUpdateFrame) return;
                verticalUpdateFrame = requestAnimationFrame(() => {
                    verticalUpdateFrame = 0;

                // Effect Logic (Scaling and Active State)
                const items = track.querySelectorAll('.v-item');
                const centerY = container.offsetHeight / 2;
                const containerRect = container.getBoundingClientRect();

                items.forEach(item => {
                    const rect = item.getBoundingClientRect();
                    const itemCenter = rect.top + rect.height / 2 - containerRect.top;
                    const dist = Math.abs(itemCenter - centerY);
                    const maxDist = isMain ? 300 : 200;

                    let scale = Math.max(isMain ? 0.6 : 0.7, Math.min(isMain ? 1.4 : 1.2, (1 - dist / maxDist) * (isMain ? 1.5 : 1.3)));
                    item.style.opacity = Math.max(isMain ? 0.2 : 0.3, 1 - dist / (isMain ? 360 : 220));
                    item.style.transform = `scale(${scale})`;

                    if (dist < (isMain ? 60 : 40)) {
                        item.classList.add('active');
                        if (isMain) {
                            document.getElementById('vItemTitle').innerText = item.dataset.title;
                            document.getElementById('vItemDesc').innerText = item.dataset.desc;
                        }
                    } else {
                        item.classList.remove('active');
                    }
                    if (isMain) item.style.filter = `blur(${Math.min(5, (dist / 100) * 2)}px)`;
                });
                });
            };

            // Trigger initial effects
            setTimeout(() => {
                container.dispatchEvent(new Event('scroll'));
            }, 100);
        }

        // Initialize Main Carousel
        const vItemsData = [
            { img: 'images/1.jpg', title: 'منتج مميز', desc: 'منتج مختار من Ozman' },
            { img: 'images/22.jpg', title: 'عرض خاص', desc: 'تفاصيل المنتج تظهر هنا' },
            { img: 'images/4.jpg', title: 'منتج جديد', desc: 'منتج مضاف حديثا' },
            { img: 'images/71.jpg', title: 'الأكثر طلبا', desc: 'منتجات مميزة للعملاء' },
            { img: 'images/9.jpg', title: 'منتج Ozman', desc: 'تجربة عرض نظيفة' },
            { img: 'images/cb.jpg', title: 'اختيارنا لك', desc: 'وصف مختصر للمنتج' }
        ];

        window.addEventListener('load', () => {
            setupInfiniteVertical(document.getElementById('vCarousel'), document.getElementById('vTrack'), vItemsData, true);
            setupInfiniteVertical(document.getElementById('sideVCarousel'), document.getElementById('sideVTrack'), centersData, false);
            if (!pendingPurchaseOrder) {
                if (hasPurchaseWheelSession()) {
                    clearCartState();
                }
                resetAllPurchaseWheelSessions();
            }
            setupPurchaseWheelsVertical();
            // Location Modal Logic
            const locationBtns = document.querySelectorAll('.location-btn-trigger');
            const locationModal = document.getElementById('locationModal');
            const closeLocationModal = document.getElementById('closeLocationModal');
            const confirmLocationBtn = document.getElementById('confirmLocationBtn');
            const nearestLocationStatus = document.getElementById('nearestLocationStatus');
            const nearestShopsList = document.getElementById('nearestShopsList');
            const nearestMapFrame = document.getElementById('nearestMapFrame');
            const nearestSelectedShopTitle = document.getElementById('nearestSelectedShopTitle');
            const nearestSelectedShopMeta = document.getElementById('nearestSelectedShopMeta');
            const nearestGpsLink = document.getElementById('nearestGpsLink');
            const nearestShowShopBtn = document.getElementById('nearestShowShopBtn');
            const nearestRouteOverlay = document.getElementById('nearestRouteOverlay');
            const nearestRouteBadge = document.getElementById('nearestRouteBadge');
            let nearestCurrentLocation = loadCustomerLocation();
            let nearestSelectedIndex = activeCenterIndex;

            const updateNearestPreview = (index = nearestSelectedIndex, location = nearestCurrentLocation) => {
                const shop = centersData[index] || centersData[0];
                if (!shop) return;

                nearestSelectedIndex = index;
                nearestCurrentLocation = location;
                const distance = location ? distanceBetweenKm(location, shop) : null;

                if (nearestMapFrame) {
                    nearestMapFrame.src = shopMapEmbedUrl(shop, location);
                }

                if (nearestSelectedShopTitle) {
                    nearestSelectedShopTitle.textContent = shop.title || 'المحل';
                }

                if (nearestSelectedShopMeta) {
                    const accuracyText = formatLocationAccuracy(location);
                    nearestSelectedShopMeta.textContent = [
                        shopDistanceLabel(shop, distance),
                        shop.address || `${(shop.departments || []).length} ${frontLabel('departments', 'أقسام')}`,
                        accuracyText
                    ].filter(Boolean).join(' - ');
                }

                if (nearestGpsLink) {
                    nearestGpsLink.href = shopMapsUrl(shop);
                }

                if (nearestRouteOverlay) {
                    nearestRouteOverlay.hidden = !location;
                }

                if (nearestRouteBadge) {
                    nearestRouteBadge.textContent = 'موقعك';
                }

                nearestShowShopBtn?.classList.add('is-visible');
            };

            const renderNearestShops = (location = loadCustomerLocation(), preferNearest = false) => {
                if (!nearestShopsList) return;

                nearestCurrentLocation = location;
                const sortedShops = shopsSortedByDistance(location);
                if ((preferNearest || !centersData[nearestSelectedIndex]) && sortedShops[0]) {
                    nearestSelectedIndex = sortedShops[0].index;
                }

                nearestShopsList.innerHTML = sortedShops.map(({ shop, index, distance }) => `
                    <button type="button" class="nearest-shop-card ${index === nearestSelectedIndex ? 'active' : ''}" data-nearest-shop-index="${index}">
                        <img src="${escapeCartHtml(shop.img || shop.logo || 'images/logo.jpg')}" alt="${escapeCartHtml(shop.title || 'المحل')}">
                        <span>
                            <strong>${escapeCartHtml(shop.title || 'المحل')}</strong>
                            <span>${escapeCartHtml(shop.address || `${(shop.departments || []).length} ${frontLabel('departments', 'أقسام')}`)}</span>
                        </span>
                        <em class="nearest-shop-distance">${escapeCartHtml(shopDistanceLabel(shop, distance))}</em>
                    </button>
                `).join('');

                nearestShopsList.querySelectorAll('[data-nearest-shop-index]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const index = Number(button.dataset.nearestShopIndex);
                        if (!Number.isFinite(index)) return;

                        updateNearestPreview(index, location);
                    renderNearestShops(location);
                });
                });

                updateNearestPreview(nearestSelectedIndex, location);
            };

            const updateNearestStatus = (message) => {
                if (nearestLocationStatus) nearestLocationStatus.textContent = message;
            };

            const detectAndRenderNearestShops = () => {
                if (!navigator.geolocation) {
                    updateNearestStatus(frontLabel('locationUnsupported', 'المتصفح لا يدعم تحديد الموقع تلقائيا.'));
                    renderNearestShops(null);
                    return;
                }

                updateNearestStatus('جاري تحديد موقعك بدقة عالية... خليك فاتح إذن الموقع.');
                confirmLocationBtn?.setAttribute('disabled', 'disabled');
                clearCustomerLocation();

                requestPreciseCustomerLocation((location) => {
                    const details = [formatLocationAccuracy(location), formatLocationCoordinates(location)].filter(Boolean).join(' - ');
                    updateNearestStatus(details
                        ? `وصلت قراءة GPS، نحاول نجيب أدق منها... ${details}`
                        : frontLabel('locationLoading', 'جاري تحديد موقعك...'));
                }).then((location) => {
                    saveCustomerLocation(location);
                    const details = [formatLocationAccuracy(location), formatLocationCoordinates(location)].filter(Boolean).join(' - ');
                    const shopsWithCoordinatesCount = centersData.filter(hasShopCoordinates).length;
                    const locationMessage = shopsWithCoordinatesCount > 0
                        ? `تم تحديد موقعك الآن. ${details} اختر المتجر الأقرب لك لعرض أقسامه.`
                        : `تم تحديد موقعك الآن. ${details} لكن المتاجر لا تحتوي إحداثيات، لذلك لا يمكن ترتيبها من الأقرب للأبعد.`;
                    updateNearestStatus(locationMessage.trim());
                    renderNearestShops(location, true);
                    confirmLocationBtn?.removeAttribute('disabled');
                }).catch(() => {
                    updateNearestStatus(frontLabel('locationDenied', 'لم نقدر نحدد الموقع. تأكد من السماح للموقع بالوصول للّوكيشن.'));
                    renderNearestShops(null);
                    confirmLocationBtn?.removeAttribute('disabled');
                });
            };

            locationBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    if (!locationModal) return;
                    locationModal.classList.add('show');
                    const savedLocation = loadCustomerLocation();
                    nearestSelectedIndex = activeCenterIndex;
                    updateNearestStatus(savedLocation
                        ? `${frontLabel('savedLocationLoaded', 'تم تحميل الموقع المحفوظ مسبقا.')} ${[formatLocationAccuracy(savedLocation), formatLocationCoordinates(savedLocation)].filter(Boolean).join(' - ')} اضغط الزر لتحديث موقعك الآن.`
                        : 'اضغط على الزر لتحديد موقعك وعرض المتاجر من الأقرب للأبعد.');
                    renderNearestShops(savedLocation, Boolean(savedLocation));
                });
            });

            if (closeLocationModal) {
                closeLocationModal.addEventListener('click', () => {
                    locationModal.classList.remove('show');
                });
            }
            if (confirmLocationBtn) {
                confirmLocationBtn.addEventListener('click', () => {
                    clearCustomerLocation();
                    sessionStorage.setItem(REFRESH_LOCATION_REQUEST_KEY, '1');
                    window.location.reload();
                });
            }
            if (nearestShowShopBtn) {
                nearestShowShopBtn.addEventListener('click', () => {
                    if (!centersData[nearestSelectedIndex]) return;

                    selectCategory(nearestSelectedIndex);
                    locationModal?.classList.remove('show');
                    scrollToDepartments();
                });
            }

            if (sessionStorage.getItem(REFRESH_LOCATION_REQUEST_KEY) === '1') {
                sessionStorage.removeItem(REFRESH_LOCATION_REQUEST_KEY);
                locationModal?.classList.add('show');
                nearestSelectedIndex = activeCenterIndex;
                renderNearestShops(null, false);
                window.setTimeout(detectAndRenderNearestShops, 350);
            }

            if (locationModal) {
                locationModal.addEventListener('click', (e) => {
                    if (e.target === locationModal) {
                        locationModal.classList.remove('show');
                    }
                });
            }

            const visitorRegistrationModal = document.getElementById('visitorRegistrationModal');
            const visitorRegistrationForm = document.getElementById('visitorRegistrationForm');
            const visitorTypeInput = document.getElementById('visitorTypeInput');
            const visitorMerchantFields = document.getElementById('visitorMerchantFields');
            const visitorRegistrationMessage = document.getElementById('visitorRegistrationMessage');
            const visitorCustomerLocationField = document.getElementById('visitorCustomerLocationField');
            const detectVisitorCustomerLocationBtn = document.getElementById('detectVisitorCustomerLocationBtn');
            const visitorCustomerLocationStatus = document.getElementById('visitorCustomerLocationStatus');
            const visitorCustomerMapFrame = document.getElementById('visitorCustomerMapFrame');
            const detectVisitorBusinessLocationBtn = document.getElementById('detectVisitorBusinessLocationBtn');
            const visitorBusinessLocation = document.getElementById('visitorBusinessLocation');
            const visitorBusinessLocationStatus = document.getElementById('visitorBusinessLocationStatus');
            const visitorBusinessMapFrame = document.getElementById('visitorBusinessMapFrame');
            const visitorLatitude = document.getElementById('visitorLatitude');
            const visitorLongitude = document.getElementById('visitorLongitude');
            const visitorMapLink = document.getElementById('visitorMapLink');
            const visitorTypeButtons = document.querySelectorAll('[data-visitor-type]');
            const rewardWheelModal = document.getElementById('rewardWheelModal');
            const rewardWheelSpinBtn = document.getElementById('rewardWheelSpinBtn');
            const rewardWheelLabels = document.getElementById('rewardWheelLabels');
            const rewardWheelTitle = document.getElementById('rewardWheelTitle');
            const rewardResultModal = document.getElementById('rewardResultModal');
            const rewardResultText = document.getElementById('rewardResultText');
            const rewardResultImage = document.getElementById('rewardResultImage');
            const closeRewardResultBtn = document.getElementById('closeRewardResultBtn');
            const sendRewardGiftBtn = document.getElementById('sendRewardGiftBtn');
            let rewardWheelReady = false;
            let activeRewardWheel = null;
            let activeRewardStorageKey = REWARD_DISCOUNT_STORAGE_KEY;

            function setVisitorMessage(message, isError = false) {
                if (!visitorRegistrationMessage) return;
                visitorRegistrationMessage.textContent = message || '';
                visitorRegistrationMessage.classList.toggle('error', Boolean(isError));
            }

            function hideVisitorRegistrationModal() {
                if (!visitorRegistrationModal) return;
                visitorRegistrationModal.classList.remove('show');
                visitorRegistrationModal.setAttribute('aria-hidden', 'true');
            }

            function openModal(modal) {
                if (!modal) return;
                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
            }

            function closeModal(modal) {
                if (!modal) return;
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
            }

            function saveStoredReward(storageKey, reward) {
                localStorage.setItem(storageKey, JSON.stringify(reward || {}));
            }

            function setupRewardWheel(wheel = rewardWheelConfig()) {
                if (!wheel || !rewardWheelSpinBtn || !rewardWheelLabels) return;
                activeRewardWheel = wheel;

                if (rewardWheelTitle) rewardWheelTitle.textContent = wheel.title;

                const step = 360 / wheel.segments.length;
                const gradient = wheel.segments.map((segment, index) => {
                    const start = Math.round(index * step);
                    const end = Math.round((index + 1) * step);
                    return `${segment.color || '#00e5ff'} ${start}deg ${end}deg`;
                }).join(', ');

                rewardWheelSpinBtn.style.setProperty('--reward-wheel-gradient', gradient);
                rewardWheelLabels.innerHTML = wheel.segments.map((segment, index) => {
                    const angle = (index * step) + (step / 2);
                    const image = segment.gift_image
                        ? `<img src="${escapeCartHtml(segment.gift_image)}" alt="${escapeCartHtml(segment.label || 'هدية')}">`
                        : '';
                    return `<span class="reward-wheel-label" style="--angle:${angle}deg">${image}${escapeCartHtml(segment.label || 'خصم')}</span>`;
                }).join('');

                rewardWheelReady = true;
            }

            function openRewardWheelModal(wheel = rewardWheelConfig(), storageKey = REWARD_DISCOUNT_STORAGE_KEY) {
                if (!wheel || storedReward(storageKey)) return;
                activeRewardStorageKey = storageKey;
                setupRewardWheel(wheel);
                if (!rewardWheelReady) return;
                openModal(rewardWheelModal);
            }

            function openPurchaseRewardWheelForTotal(total) {
                if (!visitorCanUsePurchaseWheels()) return;

                const wheel = eligiblePurchaseRewardWheel(total);
                if (!wheel) return;

                unlockPurchaseWheel(wheel, total);
                openRewardWheelModal(wheel, purchaseWheelStorageKey(wheel));
            }

            window.ozmanOpenPurchaseRewardWheel = (wheel) => {
                if (!visitorCanUsePurchaseWheels()) return;
                if (!wheel) return;
                openRewardWheelModal(wheel, purchaseWheelStorageKey(wheel));
            };

            function rewardResultLabel(reward) {
                return reward?.label || 'خصمك الأول';
            }

            function rewardOrderContext(reward) {
                if (reward?.order_number || reward?.order_id) {
                    return {
                        order_id: reward.order_id || null,
                        order_number: reward.order_number || '',
                    };
                }

                return lastRewardOrder || loadLastRewardOrder() || loadPendingPurchaseOrder() || {};
            }

            function rewardCanSendGift(reward) {
                return Boolean(reward?.label && rewardOrderContext(reward).order_number);
            }

            function rewardGiftWhatsappMessage(reward) {
                const order = rewardOrderContext(reward);
                const profile = loadCustomerProfile();
                const lines = [
                    'مرحبا، ربحت جائزة من عجلة الطلب.',
                    order.order_number ? `رقم الطلب: ${order.order_number}` : '',
                    `الجائزة: ${rewardResultLabel(reward)}`,
                    profile.name ? `اسم العميل: ${profile.name}` : '',
                    profile.whatsapp ? `واتساب العميل: ${profile.whatsapp}` : '',
                    '',
                    'أرجو تثبيت الجائزة مع طلبي للحصول عليها عند استلام الطلب.'
                ].filter((line) => line !== '');

                return lines.join('\n');
            }

            function showRewardResult(reward) {
                let resultText = '';
                saveLastRewardPayload(reward || null);

                if (rewardResultText) {
                    resultText = `حصلت على ${rewardResultLabel(reward)}`;
                    rewardResultText.textContent = resultText;
                }
                if (rewardResultImage) {
                    if (reward?.gift_image) {
                        rewardResultImage.src = reward.gift_image;
                        rewardResultImage.alt = reward.label || 'هدية';
                        rewardResultImage.hidden = false;
                    } else {
                        rewardResultImage.hidden = true;
                        rewardResultImage.removeAttribute('src');
                    }
                }
                if (sendRewardGiftBtn) {
                    sendRewardGiftBtn.hidden = !rewardCanSendGift(reward);
                }
                closeModal(rewardWheelModal);
                openModal(rewardResultModal);
                window.setTimeout(() => {
                    speakRewardResult(resultText || rewardResultText?.textContent || rewardResultLabel(reward));
                }, 350);
                if (activeRewardStorageKey?.startsWith(PURCHASE_REWARD_STORAGE_PREFIX)) {
                    const purchaseRewardStorageKey = activeRewardStorageKey;
                    resetPurchaseWheelSession(purchaseRewardStorageKey);
                }
                renderCart();
            }

            function setVisitorType(type) {
                const isMerchant = type === 'merchant';
                if (visitorTypeInput) visitorTypeInput.value = isMerchant ? 'merchant' : 'customer';
                if (visitorMerchantFields) visitorMerchantFields.hidden = !isMerchant;
                if (visitorCustomerLocationField) visitorCustomerLocationField.hidden = isMerchant;

                visitorTypeButtons.forEach(button => {
                    button.classList.toggle('active', button.dataset.visitorType === (isMerchant ? 'merchant' : 'customer'));
                });

                visitorMerchantFields?.querySelectorAll('input, textarea').forEach(field => {
                    if (field.type !== 'hidden') field.required = isMerchant;
                    if (!isMerchant) field.value = '';
                });

                if (isMerchant) {
                    if (visitorCustomerMapFrame) {
                        visitorCustomerMapFrame.hidden = true;
                        visitorCustomerMapFrame.removeAttribute('src');
                    }
                    if (visitorCustomerLocationStatus) {
                        visitorCustomerLocationStatus.textContent = 'اضغط لتحديد لوكيشنك من الخريطة';
                    }
                    if (visitorLatitude) visitorLatitude.value = '';
                    if (visitorLongitude) visitorLongitude.value = '';
                    if (visitorMapLink) visitorMapLink.value = '';
                } else {
                    if (visitorLatitude) visitorLatitude.value = '';
                    if (visitorLongitude) visitorLongitude.value = '';
                    if (visitorMapLink) visitorMapLink.value = '';
                    if (visitorBusinessLocation) visitorBusinessLocation.value = '';
                    if (visitorBusinessMapFrame) {
                        visitorBusinessMapFrame.hidden = true;
                        visitorBusinessMapFrame.removeAttribute('src');
                    }
                    if (visitorBusinessLocationStatus) {
                        visitorBusinessLocationStatus.textContent = 'اضغط لتحديد لوكيشن المحل من الخريطة';
                    }
                }
            }

            function detectVisitorLocation({ statusEl, mapFrame, businessLocationInput = null }) {
                if (!navigator.geolocation) {
                    if (statusEl) statusEl.textContent = frontLabel('locationUnsupported', 'المتصفح لا يدعم تحديد الموقع تلقائيا.');
                    return;
                }

                if (statusEl) statusEl.textContent = frontLabel('locationLoading', 'جاري تحديد موقعك...');
                navigator.geolocation.getCurrentPosition((position) => {
                    const latitude = position.coords.latitude.toFixed(7);
                    const longitude = position.coords.longitude.toFixed(7);
                    const mapLink = `https://www.google.com/maps?q=${latitude},${longitude}`;

                    if (visitorLatitude) visitorLatitude.value = latitude;
                    if (visitorLongitude) visitorLongitude.value = longitude;
                    if (visitorMapLink) visitorMapLink.value = mapLink;
                    if (businessLocationInput) businessLocationInput.value = mapLink;
                    if (mapFrame) {
                        mapFrame.src = `https://www.google.com/maps?q=${latitude},${longitude}&z=16&output=embed`;
                        mapFrame.hidden = false;
                    }
                    if (statusEl) statusEl.textContent = frontLabel('locationDetected', 'تم تحديد موقعك على الخريطة.');
                }, () => {
                    if (statusEl) statusEl.textContent = frontLabel('locationDenied', 'لم نقدر نحدد الموقع. تأكد من السماح للموقع بالوصول للّوكيشن.');
                }, {
                    enableHighAccuracy: true,
                    timeout: 12000,
                    maximumAge: 60000
                });
            }

            function showVisitorRegistrationModal() {
                visitorRegistrationModal.classList.add('show');
                visitorRegistrationModal.setAttribute('aria-hidden', 'false');
                setVisitorType(visitorTypeInput?.value || 'customer');
            }

            function setupVisitorRegistrationModal() {
                if (!visitorRegistrationModal || !window.OZMAN_FRONT_CONFIG?.showVisitorRegistration) {
                    hideVisitorRegistrationModal();
                    return;
                }

                setVisitorType(window.OZMAN_FRONT_CONFIG?.initialVisitorType || 'customer');

                if (!window.OZMAN_FRONT_CONFIG?.forceVisitorRegistration && localStorage.getItem(VISITOR_REGISTRATION_STORAGE_KEY) === '1') {
                    hideVisitorRegistrationModal();
                    return;
                }

                showVisitorRegistrationModal();
            }

            visitorTypeButtons.forEach(button => {
                button.addEventListener('click', () => setVisitorType(button.dataset.visitorType));
            });

            if (detectVisitorCustomerLocationBtn) {
                detectVisitorCustomerLocationBtn.addEventListener('click', () => {
                    detectVisitorLocation({
                        statusEl: visitorCustomerLocationStatus,
                        mapFrame: visitorCustomerMapFrame
                    });
                });
            }

            if (detectVisitorBusinessLocationBtn) {
                detectVisitorBusinessLocationBtn.addEventListener('click', () => {
                    detectVisitorLocation({
                        statusEl: visitorBusinessLocationStatus,
                        mapFrame: visitorBusinessMapFrame,
                        businessLocationInput: visitorBusinessLocation
                    });
                });
            }

            if (visitorRegistrationForm) {
                visitorRegistrationForm.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    if (!visitorRegistrationForm.reportValidity()) return;
                    if (visitorTypeInput?.value === 'merchant' && !visitorBusinessLocation?.value) {
                        setVisitorMessage('لازم تحدد لوكيشن المحل قبل الحفظ.', true);
                        detectVisitorBusinessLocationBtn?.focus();
                        return;
                    }
                    if (visitorTypeInput?.value === 'customer' && !visitorMapLink?.value) {
                        setVisitorMessage('لازم تحدد لوكيشنك قبل الحفظ.', true);
                        detectVisitorCustomerLocationBtn?.focus();
                        return;
                    }

                    const submitButton = visitorRegistrationForm.querySelector('button[type="submit"]');
                    const previousButtonHtml = submitButton?.innerHTML;
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الحفظ';
                    }
                    setVisitorMessage('');

                    try {
                        const registrationType = visitorTypeInput?.value || 'customer';
                        const response = await fetch(window.OZMAN_FRONT_CONFIG?.visitorRegistrationUrl || '/visitor-registrations', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            },
                            body: new FormData(visitorRegistrationForm)
                        });

                        const result = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            const firstError = result?.errors ? Object.values(result.errors).flat()[0] : null;
                            throw new Error(firstError || result?.message || 'تعذر حفظ البيانات، حاول مرة ثانية.');
                        }

                        localStorage.setItem(VISITOR_REGISTRATION_STORAGE_KEY, '1');
                        localStorage.setItem(VISITOR_TYPE_STORAGE_KEY, registrationType);
                        if (registrationType === 'customer') {
                            localStorage.removeItem(REWARD_DISCOUNT_STORAGE_KEY);
                        }
                        syncCartPricesForVisitor();
                        renderCart();
                        setVisitorMessage(result?.message || 'تم حفظ بياناتك بنجاح.');
                        showCartToast(result?.message || 'تم حفظ بياناتك بنجاح.');
                        window.setTimeout(() => {
                            hideVisitorRegistrationModal();
                            if (registrationType === 'customer') {
                                openRewardWheelModal();
                            }
                        }, 450);
                    } catch (error) {
                        setVisitorMessage(error.message || 'تعذر حفظ البيانات، حاول مرة ثانية.', true);
                    } finally {
                        if (submitButton) {
                            submitButton.disabled = false;
                            submitButton.innerHTML = previousButtonHtml;
                        }
                    }
                });
            }

            setupRewardWheel();

            if (rewardWheelSpinBtn) {
                rewardWheelSpinBtn.addEventListener('click', async () => {
                    const wheel = activeRewardWheel || rewardWheelConfig();
                    if (!wheel || storedReward(activeRewardStorageKey)) {
                        closeModal(rewardWheelModal);
                        return;
                    }

                    const segments = wheel.segments;
                    const isPurchaseReward = activeRewardStorageKey?.startsWith(PURCHASE_REWARD_STORAGE_PREFIX);
                    let selectedIndex = Math.floor(Math.random() * segments.length);
                    let selected = segments[selectedIndex];

                    rewardWheelSpinBtn.disabled = true;

                    if (isPurchaseReward) {
                        try {
                            const result = await spinFrontOrderReward();
                            selectedIndex = Number.isInteger(result?.segment_index) ? result.segment_index : 0;
                            selected = result?.reward || segments[selectedIndex] || selected;
                        } catch (error) {
                            rewardWheelSpinBtn.disabled = false;
                            showCartToast(error.message || 'تعذر اختيار الجائزة.');
                            return;
                        }
                    }

                    const step = 360 / segments.length;
                    const selectedCenter = (selectedIndex * step) + (step / 2);
                    const spins = 5 + Math.floor(Math.random() * 2);
                    const finalRotation = (spins * 360) - selectedCenter;

                    rewardWheelSpinBtn.style.transform = `rotate(${finalRotation}deg)`;

                    window.setTimeout(() => {
                        const rewardOrder = isPurchaseReward
                            ? (lastRewardOrder || loadLastRewardOrder() || loadPendingPurchaseOrder() || {})
                            : {};
                        const reward = {
                            label: selected.label || 'خصمك الأول',
                            discount_value: selected.discount_value || 0,
                            discount_type: selected.discount_type || 'percent',
                            gift_image: selected.gift_image || null,
                            color: selected.color || '#00e5ff',
                            won_at: new Date().toISOString(),
                            order_id: rewardOrder.order_id || null,
                            order_number: rewardOrder.order_number || ''
                        };

                        saveStoredReward(activeRewardStorageKey, reward);
                        showRewardResult(reward);
                        rewardWheelSpinBtn.disabled = false;
                    }, 4300);
                });
            }

            if (closeRewardResultBtn) {
                closeRewardResultBtn.addEventListener('click', () => {
                    if ('speechSynthesis' in window) {
                        window.speechSynthesis.cancel();
                    }
                    closeModal(rewardResultModal);
                });
            }

            if (sendRewardGiftBtn) {
                sendRewardGiftBtn.addEventListener('click', () => {
                    const reward = lastRewardPayload || loadLastRewardPayload();
                    if (!rewardCanSendGift(reward)) return;

                    openShopWhatsappMessage(rewardGiftWhatsappMessage(reward));
                });
            }

            setupVisitorRegistrationModal();

            const customerLoginModal = document.getElementById('customerLoginModal');
            const closeCustomerLoginModal = document.getElementById('closeCustomerLoginModal');
            const customerLoginForm = document.getElementById('customerLoginForm');
            const customerLoginOpenBtn = document.getElementById('customerLoginOpenBtn');
            const saveCustomerOnlyBtn = document.getElementById('saveCustomerOnlyBtn');
            const instantPaymentModal = document.getElementById('instantPaymentModal');
            const closeInstantPaymentModal = document.getElementById('closeInstantPaymentModal');
            const instantPaymentForm = document.getElementById('instantPaymentForm');
            const instantPaymentTotal = document.getElementById('instantPaymentTotal');
            const instantPaymentCount = document.getElementById('instantPaymentCount');
            const backToCustomerDataBtn = document.getElementById('backToCustomerDataBtn');
            const paymentCardFields = document.getElementById('paymentCardFields');
            const paymentPaypalNote = document.getElementById('paymentPaypalNote');
            const paymentMethodInputs = document.querySelectorAll('input[name="payment_method"]');
            const detectCustomerLocationBtn = document.getElementById('detectCustomerLocationBtn');
            const customerMapFrame = document.getElementById('customerMapFrame');
            const customerLocationStatus = document.getElementById('customerLocationStatus');
            const customerFields = {
                name: document.getElementById('customerName'),
                phone: document.getElementById('customerPhone'),
                whatsapp: document.getElementById('customerWhatsapp'),
                address: document.getElementById('customerAddress'),
                latitude: document.getElementById('customerLatitude'),
                longitude: document.getElementById('customerLongitude'),
                mapLink: document.getElementById('customerMapLink')
            };

            function fillCustomerForm() {
                const profile = loadCustomerProfile();
                Object.keys(customerFields).forEach(key => {
                    if (customerFields[key]) customerFields[key].value = profile[key] || '';
                });

                if (profile.latitude && profile.longitude && customerMapFrame) {
                    const mapLink = `https://www.google.com/maps?q=${profile.latitude},${profile.longitude}`;
                    customerMapFrame.src = `https://www.google.com/maps?q=${profile.latitude},${profile.longitude}&z=16&output=embed`;
                    if (customerFields.mapLink && !customerFields.mapLink.value) customerFields.mapLink.value = mapLink;
                    if (customerLocationStatus) customerLocationStatus.textContent = frontLabel('savedLocationLoaded', 'تم تحميل الموقع المحفوظ مسبقا.');
                }
            }

            function mapLinkFromFrame() {
                if (!customerMapFrame?.src) return '';

                try {
                    const url = new URL(customerMapFrame.src);
                    const queryLocation = url.searchParams.get('q');
                    return queryLocation ? `https://www.google.com/maps?q=${queryLocation}` : '';
                } catch (error) {
                    const match = customerMapFrame.src.match(/[?&]q=([^&]+)/);
                    return match ? `https://www.google.com/maps?q=${decodeURIComponent(match[1])}` : '';
                }
            }

            function collectCustomerProfile() {
                const latitude = customerFields.latitude?.value || '';
                const longitude = customerFields.longitude?.value || '';
                const mapLink = customerFields.mapLink?.value || (latitude && longitude ? `https://www.google.com/maps?q=${latitude},${longitude}` : mapLinkFromFrame());

                return {
                    name: customerFields.name?.value.trim() || '',
                    phone: customerFields.phone?.value.trim() || '',
                    whatsapp: customerFields.whatsapp?.value.trim() || '',
                    address: customerFields.address?.value.trim() || '',
                    latitude,
                    longitude,
                    mapLink
                };
            }

            function openCustomerLoginModal(product = null) {
                if (!customerLoginModal) return;
                pendingSingleProduct = product ? productForCurrentVisitor(product) : null;
                closeCartPanel();
                fillCustomerForm();
                customerLoginModal.classList.add('show');
                customerLoginModal.setAttribute('aria-hidden', 'false');
                setTimeout(() => customerFields.name?.focus(), 80);
            }

            function closeCustomerLoginModalPanel(clearPendingProduct = true) {
                if (!customerLoginModal) return;
                if (clearPendingProduct) pendingSingleProduct = null;
                customerLoginModal.classList.remove('show');
                customerLoginModal.setAttribute('aria-hidden', 'true');
            }

            function selectedPaymentMethod() {
                return document.querySelector('input[name="payment_method"]:checked')?.value || window.OZMAN_FRONT_CONFIG?.payment?.method || 'shop_account';
            }

            function paymentTotalValue() {
                if (pendingSingleProduct) {
                    return cartItemLineTotal(pendingSingleProduct);
                }

                return cartFinalValue();
            }

            function paymentItemsCount() {
                if (pendingSingleProduct) return Number(pendingSingleProduct.qty || 1);

                return cartTotalQty();
            }

            function updatePaymentSummary() {
                if (instantPaymentTotal) instantPaymentTotal.textContent = formatCartPrice(paymentTotalValue());
                if (instantPaymentCount) instantPaymentCount.textContent = String(paymentItemsCount());
            }

            function updatePaymentMethodView() {
                const method = selectedPaymentMethod();
                document.querySelectorAll('.payment-method').forEach(label => {
                    label.classList.toggle('active', label.querySelector('input')?.value === method);
                });

                const isCard = method === 'visa' || method === 'mastercard';
                if (paymentCardFields) paymentCardFields.hidden = !isCard;
                if (paymentPaypalNote) paymentPaypalNote.hidden = isCard;
            }

            function openInstantPaymentModal(profile) {
                if (!instantPaymentModal) return;
                saveCustomerProfile(profile);
                closeCustomerLoginModalPanel(false);
                updatePaymentSummary();
                updatePaymentMethodView();
                instantPaymentModal.classList.add('show');
                instantPaymentModal.setAttribute('aria-hidden', 'false');
            }

            function closeInstantPaymentModalPanel() {
                if (!instantPaymentModal) return;
                instantPaymentModal.classList.remove('show');
                instantPaymentModal.setAttribute('aria-hidden', 'true');
                pendingSingleProduct = null;
            }

            if (customerLoginOpenBtn) {
                customerLoginOpenBtn.addEventListener('click', () => openCustomerLoginModal());
            }

            if (closeCustomerLoginModal) {
                closeCustomerLoginModal.addEventListener('click', closeCustomerLoginModalPanel);
            }

            if (customerLoginModal) {
                customerLoginModal.addEventListener('click', (event) => {
                    if (event.target === customerLoginModal) {
                        closeCustomerLoginModalPanel();
                    }
                });
            }

            if (detectCustomerLocationBtn) {
                detectCustomerLocationBtn.addEventListener('click', () => {
                    if (!navigator.geolocation) {
                        if (customerLocationStatus) customerLocationStatus.textContent = frontLabel('locationUnsupported', 'المتصفح لا يدعم تحديد الموقع تلقائيا.');
                        return;
                    }

                    if (customerLocationStatus) customerLocationStatus.textContent = frontLabel('locationLoading', 'جاري تحديد موقعك...');
                    navigator.geolocation.getCurrentPosition((position) => {
                        const latitude = position.coords.latitude.toFixed(7);
                        const longitude = position.coords.longitude.toFixed(7);
                        const mapLink = `https://www.google.com/maps?q=${latitude},${longitude}`;

                        if (customerFields.latitude) customerFields.latitude.value = latitude;
                        if (customerFields.longitude) customerFields.longitude.value = longitude;
                        if (customerFields.mapLink) customerFields.mapLink.value = mapLink;
                        if (customerMapFrame) customerMapFrame.src = `https://www.google.com/maps?q=${latitude},${longitude}&z=16&output=embed`;
                        if (customerLocationStatus) customerLocationStatus.textContent = frontLabel('locationDetected', 'تم تحديد موقعك على الخريطة.');
                        saveCustomerProfile(collectCustomerProfile());
                    }, () => {
                        if (customerLocationStatus) customerLocationStatus.textContent = frontLabel('locationDenied', 'لم نقدر نحدد الموقع. تأكد من السماح للموقع بالوصول للّوكيشن.');
                    }, {
                        enableHighAccuracy: true,
                        timeout: 12000,
                        maximumAge: 60000
                    });
                });
            }

            if (saveCustomerOnlyBtn) {
                saveCustomerOnlyBtn.addEventListener('click', () => {
                    if (customerLoginForm && !customerLoginForm.reportValidity()) return;
                    if (!pendingSingleProduct && ozmanCart.length === 0) {
                        showCartToast(frontLabel('chooseProductsBeforePayment', 'اختار منتجاتك قبل الدفع'));
                        return;
                    }

                    const profile = collectCustomerProfile();
                    openInstantPaymentModal(profile);
                });
            }

            paymentMethodInputs.forEach(input => {
                input.addEventListener('change', updatePaymentMethodView);
            });

            if (closeInstantPaymentModal) {
                closeInstantPaymentModal.addEventListener('click', closeInstantPaymentModalPanel);
            }

            if (instantPaymentModal) {
                instantPaymentModal.addEventListener('click', (event) => {
                    if (event.target === instantPaymentModal) closeInstantPaymentModalPanel();
                });
            }

            if (backToCustomerDataBtn) {
                backToCustomerDataBtn.addEventListener('click', () => {
                    if (instantPaymentModal) {
                        instantPaymentModal.classList.remove('show');
                        instantPaymentModal.setAttribute('aria-hidden', 'true');
                    }
                    if (customerLoginModal) {
                        fillCustomerForm();
                        customerLoginModal.classList.add('show');
                        customerLoginModal.setAttribute('aria-hidden', 'false');
                    }
                });
            }

            if (instantPaymentForm) {
                instantPaymentForm.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const profile = loadCustomerProfile();
                    const method = selectedPaymentMethod();
                    const rewardTotal = paymentTotalValue();
                    const whatsappWindow = window.open('', '_blank');

                    const orderPromise = recordFrontOrder(profile, 'instant_payment', method);
                    trackPendingPurchaseOrderPromise(orderPromise);

                    try {
                        const order = await orderPromise;
                        openShopWhatsappMessage(customerPaymentMessage(profile, method, order), whatsappWindow);
                        clearSubmittedOrderItems();
                        closeInstantPaymentModalPanel();
                        openPurchaseRewardWheelForTotal(rewardTotal);
                    } catch (error) {
                        if (whatsappWindow && !whatsappWindow.closed) whatsappWindow.close();
                        showCartToast(error.message || 'تعذر حفظ الطلب في الداشبورد.');
                    }
                });
            }

            if (customerLoginForm) {
                customerLoginForm.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    if (!customerLoginForm.reportValidity()) return;

                    const profile = collectCustomerProfile();
                    saveCustomerProfile(profile);

                    if (!pendingSingleProduct && ozmanCart.length === 0) {
                        showCartToast('احفظنا بياناتك، اختار منتجاتك لإرسال الطلب');
                        closeCustomerLoginModalPanel();
                        return;
                    }

                    const rewardTotal = pendingSingleProduct
                        ? cartItemLineTotal(pendingSingleProduct)
                        : cartFinalValue();
                    const whatsappWindow = window.open('', '_blank');

                    const orderPromise = recordFrontOrder(profile, 'whatsapp', 'whatsapp');
                    trackPendingPurchaseOrderPromise(orderPromise);

                    try {
                        const order = await orderPromise;
                        openShopWhatsappMessage(customerOrderMessage(profile, order), whatsappWindow);
                        clearSubmittedOrderItems();
                        closeCustomerLoginModalPanel();
                        openPurchaseRewardWheelForTotal(rewardTotal);
                    } catch (error) {
                        if (whatsappWindow && !whatsappWindow.closed) whatsappWindow.close();
                        showCartToast(error.message || 'تعذر حفظ الطلب في الداشبورد.');
                    }
                });
            }

            // ØªÙØ¹ÙŠÙ„ Ø£Ø²Ø±Ø§Ø± Ø¥ØºÙ„Ø§Ù‚ ÙˆØªØ¹Ø¯ÙŠÙ„ ÙƒÙ…ÙŠØ© Ù…ÙˆØ¯Ø§Ù„ ØªÙØ§ØµÙŠÙ„ Ø§Ù„Ù…Ù†ØªØ¬
            const closeBtn = document.getElementById('closeProductModal');
            const pModal = document.getElementById('productGalleryModal');
            const qtyMinus = document.getElementById('qtyMinus');
            const qtyPlus = document.getElementById('qtyPlus');
            const qtyVal = document.getElementById('qtyVal');
            const addToCartBtn = document.getElementById('modalAddToCartBtn');
            const whatsappBtn = document.getElementById('modalWhatsappBtn');
            const unitChoiceModal = document.getElementById('unitChoiceModal');
            const unitChoiceClose = document.getElementById('unitChoiceClose');
            const unitChoiceCancel = document.getElementById('unitChoiceCancel');

            if (closeBtn && pModal) {
                closeBtn.addEventListener('click', () => {
                    pModal.classList.remove('active');
                });

                pModal.addEventListener('click', (e) => {
                    if (e.target === pModal) {
                        pModal.classList.remove('active');
                    }
                });
            }

            if (qtyMinus && qtyPlus && qtyVal) {
                qtyMinus.addEventListener('click', () => {
                    let val = parseInt(qtyVal.innerText);
                    if (val > 1) {
                        qtyVal.innerText = val - 1;
                    }
                });

                qtyPlus.addEventListener('click', () => {
                    let val = parseInt(qtyVal.innerText);
                    qtyVal.innerText = val + 1;
                });
            }

            [unitChoiceClose, unitChoiceCancel].forEach((button) => {
                button?.addEventListener('click', closeUnitChoiceModal);
            });

            unitChoiceModal?.addEventListener('click', (event) => {
                if (event.target === unitChoiceModal) {
                    closeUnitChoiceModal();
                }
            });

            if (addToCartBtn) {
                addToCartBtn.addEventListener('click', () => {
                    const name = document.getElementById('modalProductTitle').innerText;
                    const qty = Number.parseInt(qtyVal.innerText, 10) || 1;
                    const modalProduct = findProductByName(name) || {
                        name,
                        price: document.getElementById('modalProductPrice')?.innerText || '',
                        img: document.getElementById('modalMainImg')?.src || ''
                    };
                    addToCart(modalProduct, qty, true);
                    pModal.classList.remove('active');
                });
            }

            if (whatsappBtn) {
                whatsappBtn.addEventListener('click', () => {
                    const name = document.getElementById('modalProductTitle').innerText;
                    const qty = Number.parseInt(qtyVal.innerText, 10) || 1;
                    const price = document.getElementById('modalProductPrice').innerText;
                    const product = findProductByName(name) || {
                        name,
                        price,
                        img: document.getElementById('modalMainImg')?.src || '',
                    };
                    pModal.classList.remove('active');
                    openCustomerLoginModal({ ...product, qty });
                });
            }

            const navCartBtn = document.getElementById('navCartBtn');
            const cartPanel = document.getElementById('cartPanel');
            const cartCloseBtn = document.getElementById('cartCloseBtn');
            const cartClearBtn = document.getElementById('cartClearBtn');
            const cartCheckoutBtn = document.getElementById('cartCheckoutBtn');

            renderCart();

            if (navCartBtn && cartPanel) {
                navCartBtn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    if (cartPanel.classList.contains('active')) {
                        closeCartPanel();
                    } else {
                        openCartPanel();
                    }
                });
            }

            if (cartPanel) {
                cartPanel.addEventListener('click', (event) => {
                    event.stopPropagation();
                });
            }

            if (cartCloseBtn) {
                cartCloseBtn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    closeCartPanel();
                });
            }

            if (cartClearBtn) {
                cartClearBtn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    clearCart();
                    showCartToast('تم تفريغ السلة');
                });
            }

            if (cartCheckoutBtn) {
                cartCheckoutBtn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    if (ozmanCart.length === 0) {
                        showCartToast(frontLabel('cartIsEmpty', 'السلة فارغة'));
                        return;
                    }

                    openCustomerLoginModal();
                });
            }

            document.addEventListener('click', (event) => {
                if (!cartPanel || !cartPanel.classList.contains('active')) return;
                if (!cartPanel.contains(event.target) && navCartBtn && !navCartBtn.contains(event.target)) {
                    closeCartPanel();
                }
            });

            // --- Footer Search Logic ---
            const navSearchBtn = document.getElementById('navSearchBtn');
            const frontSearchPanel = document.getElementById('frontSearchPanel');
            const frontSearchInput = document.getElementById('frontSearchInput');
            const frontSearchResults = document.getElementById('frontSearchResults');
            const frontSearchClose = document.getElementById('frontSearchClose');

            function normalizeSearchText(value) {
                return String(value || '')
                    .toLowerCase()
                    .replace(/[أإآ]/g, 'ا')
                    .replace(/ة/g, 'ه')
                    .replace(/ى/g, 'ي')
                    .replace(/\s+/g, ' ')
                    .trim();
            }

            function escapeHtml(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function buildFrontSearchItems() {
                const items = [];
                const seenProducts = new Set();

                centersData.forEach((shop, index) => {
                    const shopName = shop.title || 'المحل';
                    items.push({
                        type: 'shop',
                        title: shopName,
                        meta: `${(shop.departments || []).length} ${frontLabel('departments', 'أقسام')}`,
                        img: shop.img,
                        index,
                        searchText: normalizeSearchText(shopName)
                    });
                });

                Object.keys(productsDb).forEach(deptName => {
                    (productsDb[deptName] || []).forEach(product => {
                        const productName = product.name || '';
                        if (!productName || seenProducts.has(productName)) return;
                        seenProducts.add(productName);

                        items.push({
                            type: 'product',
                            title: productName,
                            meta: deptName,
                            price: productDisplayPrice(product),
                            img: product.img,
                            searchText: normalizeSearchText(`${productName} ${deptName} ${productDisplayPrice(product)}`)
                        });
                    });
                });

                return items;
            }

            let frontSearchItems = buildFrontSearchItems();

            function openFrontSearch() {
                if (!frontSearchPanel || !frontSearchInput || !navSearchBtn) return;
                frontSearchPanel.classList.add('active');
                frontSearchPanel.setAttribute('aria-hidden', 'false');
                navSearchBtn.classList.add('active');
                frontSearchItems = buildFrontSearchItems();
                setTimeout(() => frontSearchInput.focus(), 80);
                renderFrontSearchResults(frontSearchInput.value);
            }

            function closeFrontSearch() {
                if (!frontSearchPanel || !navSearchBtn) return;
                frontSearchPanel.classList.remove('active');
                frontSearchPanel.setAttribute('aria-hidden', 'true');
                navSearchBtn.classList.remove('active');
            }

            function renderFrontSearchResults(query) {
                if (!frontSearchResults) return;

                const normalizedQuery = normalizeSearchText(query);
                if (!normalizedQuery) {
                    frontSearchResults.innerHTML = '<div class="front-search-empty">اكتب اسم المنتج أو المحل لعرض النتائج</div>';
                    return;
                }

                const results = frontSearchItems
                    .filter(item => item.searchText.includes(normalizedQuery))
                    .slice(0, 12);

                if (results.length === 0) {
                    frontSearchResults.innerHTML = '<div class="front-search-empty">ما لقينا نتائج مطابقة</div>';
                    return;
                }

                frontSearchResults.innerHTML = results.map((item, index) => {
                    const tag = item.type === 'shop' ? 'محل' : 'منتج';
                    const meta = item.price ? `${item.meta} - ${item.price}` : item.meta;
                    const image = item.img
                        ? `<img src="${escapeHtml(item.img)}" alt="${escapeHtml(item.title)}">`
                        : `<span class="front-search-avatar">${escapeHtml(item.title.charAt(0))}</span>`;

                    return `
                        <button type="button" class="front-search-result" data-search-index="${index}">
                            ${image}
                            <span>
                                <span class="front-search-name">${escapeHtml(item.title)}</span>
                                <span class="front-search-meta">${escapeHtml(meta)}</span>
                            </span>
                            <span class="front-search-tag">${tag}</span>
                        </button>
                    `;
                }).join('');

                frontSearchResults.querySelectorAll('.front-search-result').forEach((button, index) => {
                    button.addEventListener('click', () => {
                        const item = results[index];
                        if (!item) return;

                        closeFrontSearch();

                        const radialSection = document.querySelector('.radial-section');
                        if (radialSection) {
                            radialSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }

                        setTimeout(() => {
                            if (item.type === 'shop') {
                                selectCategory(item.index);
                            } else {
                                renderProductGalleryScatter(item.title);
                            }
                        }, 350);
                    });
                });
            }

            if (navSearchBtn && frontSearchPanel) {
                navSearchBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (frontSearchPanel.classList.contains('active')) {
                        closeFrontSearch();
                    } else {
                        openFrontSearch();
                    }
                });
            }

            if (frontSearchClose) {
                frontSearchClose.addEventListener('click', closeFrontSearch);
            }

            if (frontSearchInput) {
                frontSearchInput.addEventListener('input', () => {
                    renderFrontSearchResults(frontSearchInput.value);
                });

                frontSearchInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        closeFrontSearch();
                    }
                });
            }

            document.addEventListener('click', (e) => {
                if (!frontSearchPanel || !frontSearchPanel.classList.contains('active')) return;
                if (!frontSearchPanel.contains(e.target) && navSearchBtn && !navSearchBtn.contains(e.target)) {
                    closeFrontSearch();
                }
            });

            // --- Chatbot Controller Logic ---
            const chatbotToggleBtn = document.getElementById('chatbotToggleBtn');
            const chatbotWidget = document.getElementById('chatbotWidget');
            const closeChatbotBtn = document.getElementById('closeChatbotBtn');
            const chatbotMessages = document.getElementById('chatbotMessages');
            const chatbotInput = document.getElementById('chatbotInput');
            const chatbotSendBtn = document.getElementById('chatbotSendBtn');
            chatbotWidget?.classList.remove('active');

            function positionChatbot() {
                if (!chatbotWidget || !chatbotToggleBtn) return;
                if (!chatbotWidget.classList.contains('active')) return;

                const btnRect = chatbotToggleBtn.getBoundingClientRect();
                const btnCenter = btnRect.left + btnRect.width / 2;
                const widgetWidth = chatbotWidget.offsetWidth || 380;

                let leftPos = btnCenter - widgetWidth / 2;

                const screenWidth = window.innerWidth;
                const padding = screenWidth <= 480 ? screenWidth * 0.05 : 20;

                if (leftPos < padding) {
                    leftPos = padding;
                } else if (leftPos + widgetWidth > screenWidth - padding) {
                    leftPos = screenWidth - widgetWidth - padding;
                }

                const bottomPos = window.innerHeight - btnRect.top + 15;

                chatbotWidget.style.left = `${leftPos}px`;
                chatbotWidget.style.bottom = `${bottomPos}px`;
            }

            if (chatbotToggleBtn && chatbotWidget) {
                chatbotToggleBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    chatbotWidget.classList.toggle('active');
                    if (chatbotWidget.classList.contains('active')) {
                        positionChatbot();
                    }
                    scrollToChatBottom();
                });
            }

            if (closeChatbotBtn) {
                closeChatbotBtn.addEventListener('click', () => {
                    chatbotWidget.classList.remove('active');
                });
            }

            // Close chatbot when clicking outside of it
            document.addEventListener('click', (e) => {
                if (chatbotWidget && chatbotWidget.classList.contains('active')) {
                    if (!chatbotWidget.contains(e.target) && !chatbotToggleBtn.contains(e.target)) {
                        chatbotWidget.classList.remove('active');
                    }
                }
            });

            // Listen for window resize and scroll events to keep position synced
            window.addEventListener('resize', positionChatbot);
            window.addEventListener('scroll', positionChatbot);

            // Handle initial position on load (since it is active by default in HTML)
            setTimeout(positionChatbot, 150);

            function scrollToChatBottom() {
                if (chatbotMessages) {
                    chatbotMessages.scrollTo({
                        top: chatbotMessages.scrollHeight,
                        behavior: 'smooth'
                    });
                }
            }

            // Bind click events on automated suggestions
            function bindOptionButtons() {
                const optionBtns = chatbotMessages.querySelectorAll('.chat-option-btn');
                optionBtns.forEach(btn => {
                    btn.addEventListener('click', () => {
                        const text = btn.innerText;
                        const replyType = btn.getAttribute('data-reply');

                        // Add User Message
                        appendChatMessage(text, 'user');

                        // Remove suggestion options container
                        const container = btn.closest('.chat-options-container');
                        if (container) container.remove();

                        // Generate Bot Reply with typing delay
                        showTypingIndicator();
                        setTimeout(() => {
                            removeTypingIndicator();
                            generateBotReply(replyType);
                        }, 800);
                    });
                });
            }

            bindOptionButtons();

            // Handle sending custom text
            if (chatbotSendBtn && chatbotInput) {
                chatbotSendBtn.addEventListener('click', () => {
                    sendUserMessage();
                });

                chatbotInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        sendUserMessage();
                    }
                });
            }

            function sendUserMessage() {
                const messageText = chatbotInput.value.trim();
                if (messageText === '') return;

                appendChatMessage(messageText, 'user');
                chatbotInput.value = '';

                // Generate Bot Reply
                showTypingIndicator();
                setTimeout(() => {
                    removeTypingIndicator();
                    generateCustomBotReply(messageText);
                }, 800);
            }

            function appendChatMessage(text, sender) {
                const msg = document.createElement('div');
                msg.className = `chat-message ${sender}`;
                msg.innerHTML = text.replace(/\n/g, '<br>');
                chatbotMessages.appendChild(msg);
                scrollToChatBottom();
            }

            function showTypingIndicator() {
                const indicator = document.createElement('div');
                indicator.className = 'chat-message bot typing-indicator-msg';
                indicator.id = 'typingIndicator';
                indicator.innerHTML = `<span style="display:inline-block;animation:pulse 1s infinite;">جاري الكتابة...</span>`;
                chatbotMessages.appendChild(indicator);
                scrollToChatBottom();
            }

            function removeTypingIndicator() {
                const ind = document.getElementById('typingIndicator');
                if (ind) ind.remove();
            }

            function generateBotReply(type) {
                const replies = {
                    'جسم': 'منتجات العناية بالجسم متوفرة داخل الأقسام. اختار المنتج المناسب واضغط عليه لعرض الصور والتفاصيل.',
                    'شعر': 'منتجات العناية بالشعر تظهر حسب الفئة المختارة من لوحة التحكم.',
                    'وجه': 'منتجات العناية بالوجه تظهر هنا عند إضافتها من لوحة التحكم.',
                    'طلب': 'للطلب اختار المنتج ثم اضغط زر واتساب، وسيتم تجهيز رسالة الطلب تلقائيا.',
                    'دعم': '<a href="https://wa.me/970599000000" target="_blank" style="background:#25d366;color:#fff;border-radius:10px;padding:8px 15px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;font-weight:bold;margin-top:5px;"><i class="fab fa-whatsapp"></i> تواصل عبر واتساب مباشرة</a>'
                };

                appendChatMessage(replies[type] || 'كيف أقدر أساعدك؟', 'bot');
                showStandardOptions();
                return;

                let replyText = '';

                if (type === 'Ø¬Ø³Ù…') {
                    replyText = `ðŸ§´ <strong>Ù…Ù†ØªØ¬Ø§Øª Ø§Ù„Ø¹Ù†Ø§ÙŠØ© Ø¨Ø§Ù„Ø¬Ø³Ù… Ø§Ù„ÙØ±Ù…ÙˆÙ†ÙŠØ©:</strong><br><br>` +
                        `â€¢ <strong>Ø³Ø¨Ù„Ø§Ø´ Ø³ÙŠÙƒØ³ÙŠ Ø¨ÙŠÙ†Ùƒ (75 ₪):</strong> Ø«Ø¨Ø§Øª ÙˆÙ†Ø¹ÙˆÙ…Ø© ÙˆØ¬Ø§Ø°Ø¨ÙŠØ© Ù„Ø§ ØªÙ‚Ø§ÙˆÙ… âœ¨.<br>` +
                        `â€¢ <strong>Ø³Ø¨Ù„Ø§Ø´ Ù…Ø³Ùƒ Ø£Ø¨ÙŠØ¶ (75 ₪):</strong> Ø±Ø§Ø¦Ø­Ø© Ø§Ù„Ù†Ø¸Ø§ÙØ© ÙˆØ§Ù„Ø§Ù†ØªØ¹Ø§Ø´ Ø§Ù„ÙØ§Ø®Ø±Ø© ðŸ¤.<br>` +
                        `â€¢ <strong>Ù…Ø±Ø·Ø¨ Ø²Ø¨Ø¯Ø© Ø§Ù„Ø´ÙŠØ§ (60 ₪):</strong> ØªØ±Ø·ÙŠØ¨ Ù…Ø®Ù…Ù„ÙŠ ÙˆÙ†Ø¹ÙˆÙ…Ø© ØªØ¯ÙˆÙ… Ø·ÙˆÙŠÙ„Ø§Ù‹.<br><br>` +
                        `ÙŠÙ…ÙƒÙ†Ùƒ Ø§Ø³ØªØ¹Ø±Ø§Ø¶ Ù‡Ø°Ù‡ Ø§Ù„Ù…Ù†ØªØ¬Ø§Øª Ø§Ù„Ø±Ø§Ø¦Ø¹Ø© ÙÙŠ Ù‚Ø³Ù… Ø§Ù„Ø¹Ù†Ø§ÙŠØ© Ø¨Ø§Ù„Ø¬Ø³Ù… ÙÙŠ Ø§Ù„Ø£Ø³ÙÙ„! Ù‡Ù„ ØªÙˆØ¯ Ø·Ù„Ø¨ Ø£Ø­Ø¯Ù‡Ø§ØŸ`;
                } else if (type === 'Ø´Ø¹Ø±') {
                    replyText = `ðŸ’‡â€â™€ï¸ <strong>Ù…Ù†ØªØ¬Ø§Øª Ø§Ù„Ø¹Ù†Ø§ÙŠØ© Ø¨Ø§Ù„Ø´Ø¹Ø± Ø§Ù„ÙØ§Ø®Ø±Ø©:</strong><br><br>` +
                        `â€¢ <strong>Ø³ÙŠØ±ÙˆÙ… ÙƒÙŠØ±Ø§ØªÙŠÙ† Ù…Ø¹Ø§Ù„Ø¬ (90 ₪):</strong> Ø¥ØµÙ„Ø§Ø­ ÙÙˆØ±ÙŠ ÙˆØªÙ†Ø¹ÙŠÙ… Ø¹Ù…ÙŠÙ‚ Ù„Ù„Ø´Ø¹Ø± Ø§Ù„ØªØ§Ù„Ù ðŸ˜.<br>` +
                        `â€¢ <strong>Ø´Ø§Ù…Ø¨Ùˆ ÙƒÙŠØ±Ø§ØªÙŠÙ† (60 ₪) & Ø¨Ù„Ø³Ù… (65 ₪):</strong> Ù„Ø­ÙŠÙˆÙŠØ© ÙˆÙ‚ÙˆØ© ÙˆÙ„Ù…Ø¹Ø§Ù† Ù„Ø´Ø¹Ø±Ùƒ.<br>` +
                        `â€¢ <strong>Ø¹Ø·Ø± Ø´Ø¹Ø± Ø³ÙˆÙŠØª (85 ₪):</strong> Ø±Ø§Ø¦Ø­Ø© ÙØ±Ù…ÙˆÙ†ÙŠØ© Ø³Ø§Ø­Ø±Ø© ØªØ±Ø§ÙÙ‚Ùƒ Ø·ÙˆØ§Ù„ Ø§Ù„ÙŠÙˆÙ… ðŸŒ¸.<br><br>` +
                        `Ù‡Ù„ ØªØ±ØºØ¨ ÙÙŠ ØªØ­Ø³ÙŠÙ† ØµØ­Ø© Ø´Ø¹Ø±Ùƒ ÙˆØªØ¬Ø±Ø¨Ø© Ø£Ø­Ø¯ Ù‡Ø°Ù‡ Ø§Ù„Ù…Ù†ØªØ¬Ø§Øª Ø§Ù„ÙØ§Ø®Ø±Ø©ØŸ`;
                } else if (type === 'ÙˆØ¬Ù‡') {
                    replyText = `âœ¨ <strong>Ù…Ù†ØªØ¬Ø§Øª Ø§Ù„Ù†Ø¶Ø§Ø±Ø© ÙˆØ­Ù…Ø§ÙŠØ© Ø§Ù„ÙˆØ¬Ù‡:</strong><br><br>` +
                        `â€¢ <strong>Ø³ÙŠØ±ÙˆÙ… ÙÙŠØªØ§Ù…ÙŠÙ† C Ø§Ù„ÙØ§Ø®Ø± (110 ₪):</strong> ØªÙØªÙŠØ­ ÙˆÙ†Ø¶Ø§Ø±Ø© ÙˆÙ…ÙƒØ§ÙØ­Ø© Ù„Ø¹Ù„Ø§Ù…Ø§Øª Ø§Ù„ØªØ¹Ø¨ ðŸ§¡.<br>` +
                        `â€¢ <strong>ÙˆØ§Ù‚ÙŠ Ø´Ù…Ø³ SPF50 (80 ₪):</strong> Ø­Ù…Ø§ÙŠØ© ÙØ§Ø¦Ù‚Ø© Ù…Ù† Ø§Ù„Ø´Ù…Ø³ ÙˆØªØ±Ø·ÙŠØ¨ Ø¹Ù…ÙŠÙ‚ Ø¨Ø¯ÙˆÙ† Ø£Ø«Ø± Ø¯Ù‡Ù†ÙŠ â˜€ï¸.<br>` +
                        `â€¢ <strong>Ø³ÙŠØ±ÙˆÙ… Ù‡ÙŠØ§Ù„ÙˆØ±ÙˆÙ†ÙŠÙƒ (120 ₪):</strong> Ù†Ø¶Ø§Ø±Ø© ÙˆØ§Ù…ØªÙ„Ø§Ø¡ ÙÙˆØ±ÙŠ Ù„Ù„Ø¨Ø´Ø±Ø©.<br><br>` +
                        `Ù…Ù†ØªØ¬Ø§ØªÙ†Ø§ Ù…Ø±Ø®ØµØ© ÙˆØ¢Ù…Ù†Ø© ÙˆØªÙ…Ù†Ø­ Ø¨Ø´Ø±ØªÙƒ Ø§Ù„ØªØ£Ù„Ù‚ Ø§Ù„Ø°ÙŠ ØªØ³ØªØ­Ù‚Ù‡!`;
                } else if (type === 'Ø·Ù„Ø¨') {
                    replyText = `ðŸ›’ <strong>Ø·Ø±ÙŠÙ‚Ø© Ø§Ù„Ø·Ù„Ø¨ ÙˆØ§Ù„ØªÙˆØµÙŠÙ„ Ø³Ù‡Ù„Ø© Ø¬Ø¯Ø§Ù‹:</strong><br><br>` +
                        `1ï¸âƒ£ ØªØµÙØ­ Ø§Ù„Ù…Ù†ØªØ¬Ø§Øª ÙÙŠ Ø§Ù„Ù…ØªØ¬Ø±.<br>` +
                        `2ï¸âƒ£ Ø§Ø¶ØºØ· Ø¹Ù„Ù‰ Ø²Ø± <strong>Ø§Ø·Ù„Ø¨ Ø¹Ø¨Ø± ÙˆØ§ØªØ³Ø§Ø¨</strong> Ø¨Ø¬Ø§Ù†Ø¨ Ø£ÙŠ Ù…Ù†ØªØ¬ ØªØ±ÙŠØ¯Ù‡.<br>` +
                        `3ï¸âƒ£ Ø³ÙŠÙ‚ÙˆÙ… Ø§Ù„Ù†Ø¸Ø§Ù… Ø¨ØªØ­ÙˆÙŠÙ„Ùƒ Ù…Ø¨Ø§Ø´Ø±Ø© Ø¥Ù„Ù‰ Ø®Ø¯Ù…Ø© Ø§Ù„Ø¹Ù…Ù„Ø§Ø¡ Ù…Ø¹ ØªÙØ§ØµÙŠÙ„ Ø§Ù„Ù…Ù†ØªØ¬ ÙˆØ§Ù„ÙƒÙ…ÙŠØ© Ù„ØªØ£ÙƒÙŠØ¯ Ø§Ù„Ø·Ù„Ø¨ Ø§Ù„ØªÙˆØµÙŠÙ„ ðŸš—.<br><br>` +
                        `Ø´Ø­Ù†Ù†Ø§ Ø³Ø±ÙŠØ¹ ÙˆÙŠÙˆØµÙ„ Ù„Ø¨Ø§Ø¨ Ø¨ÙŠØªÙƒ Ø¨ÙƒÙ„ Ø£Ù…Ø§Ù†!`;
                } else if (type === 'Ø¯Ø¹Ù…') {
                    replyText = `ðŸ“ž <strong>Ø®Ø¯Ù…Ø© Ø§Ù„Ø¹Ù…Ù„Ø§Ø¡ Ø§Ù„Ù…Ø¨Ø§Ø´Ø±Ø©:</strong><br><br>` +
                        `ÙŠÙ…ÙƒÙ†Ùƒ Ø§Ù„ØªÙˆØ§ØµÙ„ Ù…Ø¹Ù†Ø§ ÙÙˆØ±Ø§Ù‹ Ø¹Ø¨Ø± Ø±Ù‚Ù… Ø§Ù„ÙˆØ§ØªØ³Ø§Ø¨ Ø§Ù„Ù…Ø¨Ø§Ø´Ø± Ù„Ù„Ø±Ø¯ Ø¹Ù„Ù‰ Ø£ÙŠ Ø§Ø³ØªÙØ³Ø§Ø±Ø§Øª Ø®Ø§ØµØ© Ø£Ùˆ Ø·Ù„Ø¨Ø§Øª Ù…Ø®ØµØµØ©:<br><br>` +
                        `<a href="https://wa.me/970599000000" target="_blank" style="background:#25d366;color:#fff;border-radius:10px;padding:8px 15px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;font-weight:bold;margin-top:5px;box-shadow:0 0 10px rgba(37,211,102,0.3);"><i class="fab fa-whatsapp"></i> Ø§Ø¶ØºØ· Ù„Ù„Ø¯Ø±Ø¯Ø´Ø© Ø§Ù„Ù…Ø¨Ø§Ø´Ø±Ø©</a>`;
                }

                appendChatMessage(replyText, 'bot');
                showStandardOptions();
            }

            function generateCustomBotReply(message) {
                const replyText = 'وصلت رسالتك. يمكنك تصفح المنتجات من الأقسام أو التواصل معنا مباشرة عبر واتساب لتأكيد الطلب.';
                appendChatMessage(replyText, 'bot');
                showStandardOptions();
                return;

                const msg = message.toLowerCase();
                let legacyReplyText = '';

                if (msg.includes('Ø³ÙŠØ±ÙˆÙ…') || msg.includes('ÙÙŠØªØ§Ù…ÙŠÙ†') || msg.includes('ÙƒÙŠØ±Ø§ØªÙŠÙ†')) {
                    replyText = `ÙŠØªÙˆÙØ± Ù„Ø¯ÙŠÙ†Ø§ Ø£ÙØ¶Ù„ Ø£Ù†ÙˆØ§Ø¹ Ø§Ù„Ø³ÙŠØ±ÙˆÙ…Ø§Øª Ø§Ù„Ø¹Ù„Ø§Ø¬ÙŠØ© Ø§Ù„ÙØ§Ø®Ø±Ø©:<br>` +
                        `â€¢ <strong>Ø³ÙŠØ±ÙˆÙ… ÙÙŠØªØ§Ù…ÙŠÙ† C Ù„Ù„ÙˆØ¬Ù‡</strong> (110 ₪) Ù„Ù„Ù†Ø¶Ø§Ø±Ø© ÙˆØ§Ù„ØªÙØªÙŠØ­.<br>` +
                        `â€¢ <strong>Ø³ÙŠØ±ÙˆÙ… ÙƒÙŠØ±Ø§ØªÙŠÙ† Ù…Ø¹Ø§Ù„Ø¬ Ù„Ù„Ø´Ø¹Ø±</strong> (90 ₪) Ù„Ø¥ØµÙ„Ø§Ø­ Ø§Ù„ØªÙ„Ù ÙˆØªÙ†Ø¹ÙŠÙ… Ø§Ù„Ø´Ø¹Ø±.<br><br>` +
                        `ØªÙˆØ¯ Ø·Ù„Ø¨ Ø£ÙŠÙ‡Ù…Ø§ Ù„ØªÙˆØµÙŠÙ„Ù‡ Ù„ÙƒØŸ ðŸ˜Š`;
                } else if (msg.includes('Ø³Ø¨Ù„Ø§Ø´') || msg.includes('Ø¹Ø·Ø±') || msg.includes('Ø±Ø§Ø¦Ø­Ø©') || msg.includes('ÙØ±Ù…ÙˆÙ†')) {
                    replyText = `Ø¹Ø§Ù„Ù… Ø§Ù„ÙØ±Ù…ÙˆÙ†Ø§Øª ÙˆØ§Ù„Ø¬Ø§Ø°Ø¨ÙŠØ© ÙÙŠ Ù‡ÙŠÙ„Ø«ÙŠ Ø´ÙˆØ¨ Ù…ØªÙ…ÙŠØ² Ø¬Ø¯Ø§Ù‹! âœ¨<br>` +
                        `Ù†Ù†ØµØ­Ùƒ Ø¨Ù€ <strong>Ø³Ø¨Ù„Ø§Ø´ Ø³ÙŠÙƒØ³ÙŠ Ø¨ÙŠÙ†Ùƒ</strong> Ø§Ù„ÙØ±Ù…ÙˆÙ†ÙŠ (75 ₪) Ø£Ùˆ <strong>Ø¹Ø·Ø± Ø§Ù„Ø´Ø¹Ø± Ø³ÙˆÙŠØª</strong> (85 ₪) Ù„Ø±Ø§Ø¦Ø­Ø© Ø³Ø§Ø­Ø±Ø© ØªØ¯ÙˆÙ… Ø·ÙˆÙŠÙ„Ø§Ù‹.`;
                } else if (msg.includes('Ø³Ø¹Ø±') || msg.includes('Ø¨ÙƒÙ…') || msg.includes('Ø§Ù„Ø£Ø³Ø¹Ø§Ø±') || msg.includes('Ø´ÙŠÙƒÙ„')) {
                    replyText = `Ø£Ø³Ø¹Ø§Ø± Ù…Ù†ØªØ¬Ø§ØªÙ†Ø§ Ù…Ù…ÙŠØ²Ø© ÙˆØªÙ†Ø§ÙØ³ÙŠØ© Ø¬Ø¯Ø§Ù‹ Ù…Ù‚Ø§Ø±Ù†Ø© Ø¨Ø§Ù„Ø¬ÙˆØ¯Ø© Ø§Ù„ÙØ§Ø®Ø±Ø©:<br>` +
                        `â€¢ Ø³Ø¨Ù„Ø§Ø´ Ø§Ù„Ø¬Ø³Ù…: 75 ₪<br>` +
                        `â€¢ ÙˆØ§Ù‚ÙŠ Ø§Ù„Ø´Ù…Ø³: 80 ₪<br>` +
                        `â€¢ Ø¹Ø·ÙˆØ± Ø§Ù„Ø´Ø¹Ø±: 85 ₪<br>` +
                        `â€¢ Ø³ÙŠØ±ÙˆÙ… Ø§Ù„Ø´Ø¹Ø±: 90 ₪<br>` +
                        `â€¢ Ø³ÙŠØ±ÙˆÙ… Ø§Ù„ÙˆØ¬Ù‡: 110 ₪<br><br>` +
                        `Ù‡Ù„ ØªØ±ØºØ¨ ÙÙŠ ØªÙØ§ØµÙŠÙ„ Ù…Ù†ØªØ¬ Ù…Ø¹ÙŠÙ†ØŸ`;
                } else if (msg.includes('ØªÙˆØµÙŠÙ„') || msg.includes('Ø´Ø­Ù†') || msg.includes('Ø·Ù„Ø¨') || msg.includes('ÙƒÙŠÙ')) {
                    replyText = `ØªÙˆØµÙŠÙ„Ù†Ø§ Ø³Ø±ÙŠØ¹ Ø¬Ø¯Ø§Ù‹ ÙˆÙŠØºØ·ÙŠ ÙƒØ§ÙØ© Ø§Ù„Ù…Ù†Ø§Ø·Ù‚! ðŸš—<br>` +
                        `Ù„Ù„Ø·Ù„Ø¨ØŒ ÙÙ‚Ø· Ø§Ø¶ØºØ· Ø¹Ù„Ù‰ Ø²Ø± <strong>Ø§Ø·Ù„Ø¨ Ø¹Ø¨Ø± ÙˆØ§ØªØ³Ø§Ø¨</strong> Ø§Ù„Ø£Ø®Ø¶Ø± Ø§Ù„Ù…Ø¶ÙŠØ¡ ØªØ­Øª Ø§Ù„Ù…Ù†ØªØ¬ØŒ Ø£Ùˆ ØªÙˆØ§ØµÙ„ Ù…Ø¹Ù†Ø§ Ù…Ø¨Ø§Ø´Ø±Ø© Ø¹Ø¨Ø± Ø§Ù„Ø±Ø§Ø¨Ø· Ø§Ù„Ø³Ø±ÙŠØ¹ Ù„Ù„ÙˆØ§ØªØ³Ø§Ø¨ Ù„Ø·Ù„Ø¨ Ø£ÙŠ Ù…Ù†ØªØ¬!`;
                } else {
                    replyText = `Ø´ÙƒØ±Ø§Ù‹ Ù„Ø§Ø³ØªÙØ³Ø§Ø±Ùƒ! ðŸŒ¸ Ø¨ØµÙØªÙŠ Ù…Ø³Ø§Ø¹Ø¯ Ù‡ÙŠÙ„Ø«ÙŠ Ø´ÙˆØ¨ Ø§Ù„Ø°ÙƒÙŠØŒ ÙŠÙ…ÙƒÙ†Ùƒ Ø§Ø³ØªÙƒØ´Ø§Ù Ù…Ù†ØªØ¬Ø§ØªÙ†Ø§ Ø§Ù„ÙØ±Ù…ÙˆÙ†ÙŠØ© Ø§Ù„Ø±Ø§Ø¦Ø¹Ø© Ù…Ù† Ø§Ù„Ø£Ù‚Ø³Ø§Ù… Ø¨Ø§Ù„Ø£Ø³ÙÙ„. Ø£Ùˆ Ù„Ù„ØªØ­Ø¯Ø« Ù…Ø¨Ø§Ø´Ø±Ø© Ù…Ø¹ Ø§Ù„Ù…Ø¨ÙŠØ¹Ø§Øª ÙˆØªØ£ÙƒÙŠØ¯ Ø·Ù„Ø¨ÙƒØŒ Ø§Ø¶ØºØ· Ø¹Ù„Ù‰ Ø§Ù„Ø±Ø§Ø¨Ø· Ø¨Ø§Ù„Ø£Ø³ÙÙ„ Ù„Ù„Ø¯Ø±Ø¯Ø´Ø© Ø§Ù„ÙÙˆØ±ÙŠØ©:<br><br>` +
                        `<a href="https://wa.me/970599000000" target="_blank" style="background:#25d366;color:#fff;border-radius:10px;padding:8px 15px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;font-weight:bold;margin-top:5px;"><i class="fab fa-whatsapp"></i> ØªÙˆØ§ØµÙ„ Ø¹Ø¨Ø± ÙˆØ§ØªØ³Ø§Ø¨ Ù…Ø¨Ø§Ø´Ø±Ø©</a>`;
                }

                appendChatMessage(replyText, 'bot');
                showStandardOptions();
            }

            function showStandardOptions() {
                const existing = chatbotMessages.querySelector('.chat-options-container');
                if (existing) existing.remove();

                const container = document.createElement('div');
                container.className = 'chat-options-container';
                container.style.marginTop = '10px';
                container.innerHTML = `
                    <button class="chat-option-btn" data-reply="جسم">منتجات العناية بالجسم</button>
                    <button class="chat-option-btn" data-reply="شعر">منتجات العناية بالشعر</button>
                    <button class="chat-option-btn" data-reply="وجه">منتجات العناية بالوجه</button>
                    <button class="chat-option-btn" data-reply="طلب">كيف أقوم بالطلب والتوصيل؟</button>
                    <button class="chat-option-btn" data-reply="دعم">التحدث مباشرة مع الدعم</button>
                `;

                chatbotMessages.appendChild(container);
                bindOptionButtons();
                scrollToChatBottom();
            }

            // Scroll to bottom initially since chat is active by default
            setTimeout(scrollToChatBottom, 300);
        });
    

