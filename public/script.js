
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

        function getProductsForDepartment(deptTitle) {
            if (productsDb[deptTitle]) {
                return productsDb[deptTitle];
            }
            return [];
        }

        function getRelatedProducts(productName) {
            for (let dept in productsDb) {
                const list = productsDb[dept];
                const found = list.find(p => p.name === productName);
                if (found) {
                    return list.filter(p => p.name !== productName);
                }
            }
            return [];
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

            const positions = [];
            const spreadRadius = 250;
            const minSpacing = 160; // Ù…Ø³Ø§ÙØ© Ø£ÙˆØ³Ø¹ Ù„ØªÙØ§Ø¯ÙŠ Ø§Ù„ØªØ¯Ø§Ø®Ù„ Ø¨Ø³Ø¨Ø¨ ØªØ³Ù…ÙŠØ§Øª Ø§Ù„Ù…Ù†ØªØ¬Ø§Øª

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
                const prod = products[i];
                const el = document.createElement('div');
                el.className = 'watch-item product-scatter-item';

                el.style.setProperty('--pos-x', pos.x + 'px');
                el.style.setProperty('--pos-y', pos.y + 'px');
                el.style.animation = `bubblePop 0.5s ease forwards ${i * 0.06}s`;
                el.style.opacity = '0';

                // Ù‡ÙŠÙƒÙ„ ÙÙ‚Ø§Ø¹Ø© Ø§Ù„Ù…Ù†ØªØ¬: ØµÙˆØ±Ø© Ø§Ù„Ù…Ù†ØªØ¬ ÙÙŠ Ø§Ù„Ù…Ù†ØªØµÙ ÙˆØ§Ø³Ù… Ø§Ù„Ù…Ù†ØªØ¬ Ø§Ù„Ù…Ø®ØªØµØ± Ø£Ø³ÙÙ„ Ø§Ù„ÙÙ‚Ø§Ø¹Ø© Ù…Ø¨Ø§Ø´Ø±Ø© (Ø¨Ø¯ÙˆÙ† Ø³Ø¹Ø±)
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
                price: product.price,
                desc: product.description || 'منتج مميز من متجر Ozman.',
                images: gallery,
                mediaItems,
                features: []
            };
        }

        function openProductCampaignIntro(product, afterClose) {
            const campaign = Array.isArray(product.campaigns)
                ? product.campaigns.find(item => item && item.src)
                : null;

            if (!campaign) {
                afterClose();
                return;
            }

            openCampaignModal({
                src: campaign.src,
                type: campaign.type === 'video' ? 'video' : 'image',
                title: campaign.title || product.name,
                onClose: afterClose,
            });
        }

        function campaignVoiceText(title) {
            return title || '';
        }

        function preferredMarketingVoice() {
            if (!('speechSynthesis' in window)) {
                return null;
            }

            const voices = window.speechSynthesis.getVoices();

            return voices.find(voice => voice.lang && voice.lang.toLowerCase().startsWith('ar'))
                || voices.find(voice => voice.lang && voice.lang.toLowerCase().startsWith('he'))
                || voices.find(voice => voice.localService)
                || voices[0]
                || null;
        }

        function speakCampaignTitle(title) {
            if (!title || !('speechSynthesis' in window)) {
                return;
            }

            window.speechSynthesis.cancel();

            const utterance = new SpeechSynthesisUtterance(campaignVoiceText(title));
            utterance.lang = document.documentElement.lang === 'he' ? 'he-IL' : 'ar';
            utterance.rate = 0.86;
            utterance.pitch = 1.02;
            utterance.volume = 1;

            const voice = preferredMarketingVoice();
            if (voice) {
                utterance.voice = voice;
            }

            window.speechSynthesis.speak(utterance);
        }

        if ('speechSynthesis' in window) {
            window.speechSynthesis.onvoiceschanged = preferredMarketingVoice;
        }

        function openCampaignModal({ src, type, title, onClose = null }) {
            if (!src) {
                return;
            }

            const safeTitle = String(title || 'حملة مميزة')
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
            overlay.innerHTML = `
                <div class="campaign-circle-modal" style="width:min(92vw,640px);display:flex;flex-direction:column;align-items:center;gap:20px;cursor:auto;">
                    <div style="position:relative;width:min(76vw,430px);aspect-ratio:1;border-radius:50%;border:4px solid var(--primary-color);background:#000;box-shadow:0 0 58px rgba(0,229,255,.48),0 0 105px rgba(112,0,255,.18);">
                        <button type="button" id="closeCampaignVoiceModal" aria-label="إغلاق" style="position:absolute;top:2%;left:4%;z-index:5;width:42px;height:42px;border-radius:50%;border:1px solid rgba(255,255,255,.18);background:rgba(0,0,0,.78);color:#fff;font-size:25px;cursor:pointer;">&times;</button>
                        <div style="position:absolute;inset:14px;border-radius:50%;overflow:hidden;background:#050505;">
                        ${isVideo
                            ? `<video src="${src}" autoplay muted loop playsinline controls style="width:100%;height:100%;object-fit:cover;"></video>`
                            : `<img src="${src}" alt="${safeTitle}" style="width:100%;height:100%;object-fit:cover;">`
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
                            متابعة للصور
                        </button>
                    </div>
                </div>
            `;

            let didClose = false;
            const close = () => {
                if (didClose) {
                    return;
                }

                didClose = true;
                const activeVideo = overlay.querySelector('video');
                if (activeVideo) {
                    activeVideo.pause();
                }
                if ('speechSynthesis' in window) {
                    window.speechSynthesis.cancel();
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
            overlay.querySelector('#replayCampaignVoice').addEventListener('click', () => speakCampaignTitle(title));
            overlay.style.display = 'flex';
            setTimeout(() => {
                overlay.style.opacity = '1';
                speakCampaignTitle(title);
            }, 20);
        }

        function openFullscreenMedia(src, type = 'image') {
            let overlay = document.getElementById('globalFullscreenOverlay');

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
                        if (event.key !== 'Escape') return;
                        const activeOverlay = document.getElementById('globalFullscreenOverlay');
                        if (!activeOverlay || activeOverlay.style.display === 'none') return;
                        const activeVideo = activeOverlay.querySelector('video');
                        if (activeVideo) {
                            activeVideo.pause();
                        }
                        activeOverlay.style.opacity = '0';
                        setTimeout(() => {
                            activeOverlay.style.display = 'none';
                            activeOverlay.innerHTML = '';
                        }, 250);
                    });
                }
            }

            overlay.innerHTML = '';

            const closeButton = document.createElement('button');
            closeButton.type = 'button';
            closeButton.innerHTML = '<i class="fas fa-chevron-right"></i> رجوع للصور';
            closeButton.style.cssText = 'position:fixed; top:24px; right:24px; z-index:1000000; display:inline-flex; align-items:center; gap:10px; border:1px solid rgba(0,229,255,0.7); background:rgba(0,0,0,0.78); color:#00e5ff; padding:12px 18px; border-radius:999px; font-family:Cairo, sans-serif; font-weight:900; font-size:16px; box-shadow:0 0 20px rgba(0,229,255,0.35); cursor:pointer;';
            closeButton.addEventListener('click', (event) => {
                event.stopPropagation();
                closeOverlay();
            });

            const media = document.createElement(type === 'video' ? 'video' : 'img');
            media.src = src;
            media.style.cssText = 'width:100vw; height:100vh; object-fit:contain; display:block;';
            media.addEventListener('click', (event) => event.stopPropagation());

            if (type === 'video') {
                media.controls = true;
                media.autoplay = true;
                media.playsInline = true;
            }

            overlay.appendChild(closeButton);
            overlay.appendChild(media);
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
                for (let d in productsDb) {
                    const match = productsDb[d].find(p => p.name === productName);
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
            const mainImgEl = document.getElementById('modalMainImg');
            const descEl = document.getElementById('modalProductDesc');
            const qtyVal = document.getElementById('qtyVal');

            if (titleEl) titleEl.innerText = foundProduct.name;
            if (priceEl) priceEl.innerText = foundProduct.price;
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
                for (let d in productsDb) {
                    const match = productsDb[d].find(p => p.name === productName);
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

            backBtn.innerHTML = '<i class="fas fa-chevron-right" style="margin-left: 8px;"></i> عودة للمنتجات';

            track.innerHTML = '';
            watchItemsElements = [];

            const mediaItems = details.mediaItems || details.images.map(src => ({ type: 'image', src }));
            if (mediaItems.length === 0) return;

            const uniqueMediaItems = mediaItems.filter((item, index, self) => {
                return self.findIndex(candidate => candidate.type === item.type && candidate.src === item.src) === index;
            });
            const targetCount = uniqueMediaItems.length;

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
                    if (thumbEl.dataset.isCampaign === 'true' && thumbEl.dataset.mediaType !== 'video') {
                        openCampaignModal({
                            src: thumbEl.dataset.mediaSrc,
                            type: thumbEl.dataset.mediaType,
                            title: thumbEl.dataset.mediaTitle
                        });
                        return;
                    }
                    
                    if (!thumbEl.dataset.isLarge) {
                        thumbEl.dataset.isLarge = "true";
                        
                        // ØªØ±ØªÙŠØ¨ Ø§Ù„ÙÙ‚Ø§Ø¹Ø§Øª Ø§Ù„Ø£Ø®Ø±Ù‰ ÙƒÙ‚Ø§Ø¦Ù…Ø© Ø¬Ø§Ù†Ø¨ÙŠØ© Ø¨Ø¯Ù„Ø§Ù‹ Ù…Ù† Ø¥Ø®ÙØ§Ø¦Ù‡Ø§
                        let sideIndex = 0;
                        const otherItems = Array.from(track.querySelectorAll('.gallery-thumb-item')).filter(t => t !== thumbEl);
                        const totalSide = otherItems.length;
                        const startY = -((totalSide - 1) * 120) / 2; // Ù„ØªÙˆØ³ÙŠØ·Ù‡Ù… Ø¹Ù…ÙˆØ¯ÙŠØ§Ù‹

                        otherItems.forEach(t => {
                            const sideX = 350; // Ø¥Ø²Ø§Ø­Ø© Ù„Ù„ÙŠÙ…ÙŠÙ† Ø¨Ø¬Ø§Ù†Ø¨ Ø§Ù„ÙÙ‚Ø§Ø¹Ø© Ø§Ù„Ø±Ø¦ÙŠØ³ÙŠØ©
                            const sideY = startY + (sideIndex * 120);
                            
                            t.style.setProperty('opacity', '1', 'important');
                            t.style.setProperty('pointer-events', 'auto', 'important');
                            t.style.setProperty('width', '100px', 'important');
                            t.style.setProperty('height', '100px', 'important');
                            t.style.setProperty('transform', `translate(calc(-50% + ${sideX}px), calc(-50% + ${sideY}px)) scale(1)`, 'important');
                            t.style.setProperty('z-index', '90', 'important');
                            t.dataset.isLarge = "";
                            const sideVideo = t.querySelector('video');
                            if (sideVideo) {
                                sideVideo.pause();
                            }
                            sideIndex++;
                        });

                        // ØªÙƒØ¨ÙŠØ± Ø§Ù„ÙÙ‚Ø§Ø¹Ø© Ø§Ù„Ø­Ø§Ù„ÙŠØ© Ù„Ù„Ù…Ù†ØªØµÙ
                        thumbEl.style.setProperty('width', '440px', 'important');
                        thumbEl.style.setProperty('height', '440px', 'important');
                        thumbEl.style.setProperty('transform', 'translate(-50%, -50%) scale(1)', 'important');
                        thumbEl.style.setProperty('z-index', '100', 'important');
                        thumbEl.style.setProperty('border', '4px solid var(--primary-color)', 'important');
                        thumbEl.style.setProperty('box-shadow', '0 0 60px rgba(0, 229, 255, 0.45), 0 20px 50px rgba(0, 0, 0, 0.9)', 'important');

                        const activeVideo = thumbEl.querySelector('video');
                        if (activeVideo) {
                            activeVideo.muted = false;
                            activeVideo.play().catch(() => {
                                activeVideo.muted = true;
                                activeVideo.play().catch(() => {});
                            });
                        }
                        
                        // Ø¥Ø¶Ø§ÙØ© Ù†Øµ ØªÙˆØ¶ÙŠØ­ÙŠ Ø¯Ø§Ø®Ù„ÙŠ ÙƒÙ…Ø§ ÙƒØ§Ù† ÙÙŠ Ø§Ù„Ø¯Ø§Ø¦Ø±Ø© Ø§Ù„Ø³Ø§Ø¨Ù‚Ø©
                        let hint = thumbEl.querySelector('.open-full-hint');
                        if (!hint) {
                            hint = document.createElement('div');
                            hint.className = 'open-full-hint';
                            hint.style.cssText = 'position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); font-size: 0.8rem; padding: 6px 16px; pointer-events: none; background: rgba(0,0,0,0.7); color: #fff; border-radius: 20px; white-space: nowrap; transition: opacity 0.3s; opacity: 0;';
                            hint.innerHTML = '<i class="fas fa-expand"></i> انقر لملء الشاشة';
                            thumbEl.appendChild(hint);
                            setTimeout(() => { hint.style.opacity = '1'; }, 300);
                        }
                        
                    } else {
                        const activeVideo = thumbEl.querySelector('video');
                        if (activeVideo) {
                            activeVideo.pause();
                        }
                        openFullscreenMedia(thumbEl.dataset.mediaSrc, thumbEl.dataset.mediaType);
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
                renderDepartmentsForCenter(activeCenterIndex);
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

        function renderDepartmentsForCenter(centerIndex) {
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
            const depsToRender = center.departments;

            const positions = [];
            const spreadRadius = 260; // Slightly widened for better spacing
            const minSpacing = 170; // Increased spacing to prevent overlaps

            for (let i = 0; i < depsToRender.length; i++) {
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
                    // relaxation logic to avoid deadlock
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
                const prod = depsToRender[i];
                const el = document.createElement('div');
                el.className = 'watch-item department-scatter-item';

                el.style.setProperty('--pos-x', pos.x + 'px');
                el.style.setProperty('--pos-y', pos.y + 'px');
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
                            renderProductsScatter(prod.title);
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
            renderDepartmentsForCenter(0);
        }

        function selectCategory(index) {
            activeCenterIndex = index;
            renderDepartmentsForCenter(index);
        }

        window.addEventListener('DOMContentLoaded', () => {
            initRadial();

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

            // Clone items 3 times for infinity feel
            [...data, ...data, ...data].forEach((itemData, index) => {
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

            // Initial Scroll Position (Middle set)
            const middleOffset = totalCount * itemHeight;
            container.scrollTop = middleOffset;

            container.addEventListener('scroll', () => {
                const currentScroll = container.scrollTop;
                const maxScroll = (totalCount * 2) * itemHeight;
                const minScroll = totalCount * itemHeight;

                // Infinite Jump Logic
                if (currentScroll >= maxScroll) {
                    container.scrollTop = currentScroll - (totalCount * itemHeight);
                } else if (currentScroll <= (totalCount * 0.5) * itemHeight) {
                    container.scrollTop = currentScroll + (totalCount * itemHeight);
                }

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
            // Location Modal Logic
            const locationBtns = document.querySelectorAll('.location-btn-trigger');
            const locationModal = document.getElementById('locationModal');
            const closeLocationModal = document.getElementById('closeLocationModal');
            const confirmLocationBtn = document.getElementById('confirmLocationBtn');

            locationBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    locationModal.classList.add('show');
                });
            });

            if (closeLocationModal) {
                closeLocationModal.addEventListener('click', () => {
                    locationModal.classList.remove('show');
                });
            }
            if (confirmLocationBtn) {
                confirmLocationBtn.addEventListener('click', () => {
                    locationModal.classList.remove('show');
                    alert('تم تحديد الموقع بنجاح!');
                });
            }

            locationModal.addEventListener('click', (e) => {
                if (e.target === locationModal) {
                    locationModal.classList.remove('show');
                }
            });

            // ØªÙØ¹ÙŠÙ„ Ø£Ø²Ø±Ø§Ø± Ø¥ØºÙ„Ø§Ù‚ ÙˆØªØ¹Ø¯ÙŠÙ„ ÙƒÙ…ÙŠØ© Ù…ÙˆØ¯Ø§Ù„ ØªÙØ§ØµÙŠÙ„ Ø§Ù„Ù…Ù†ØªØ¬
            const closeBtn = document.getElementById('closeProductModal');
            const pModal = document.getElementById('productGalleryModal');
            const qtyMinus = document.getElementById('qtyMinus');
            const qtyPlus = document.getElementById('qtyPlus');
            const qtyVal = document.getElementById('qtyVal');
            const addToCartBtn = document.getElementById('modalAddToCartBtn');
            const whatsappBtn = document.getElementById('modalWhatsappBtn');

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

            if (addToCartBtn) {
                addToCartBtn.addEventListener('click', () => {
                    const name = document.getElementById('modalProductTitle').innerText;
                    const qty = qtyVal.innerText;
                    alert(`تمت إضافة ${qty} من "${name}" إلى السلة بنجاح!`);
                    pModal.classList.remove('active');
                });
            }

            if (whatsappBtn) {
                whatsappBtn.addEventListener('click', () => {
                    const name = document.getElementById('modalProductTitle').innerText;
                    const qty = qtyVal.innerText;
                    const price = document.getElementById('modalProductPrice').innerText;
                    const text = encodeURIComponent(`مرحبا، أود طلب منتج "${name}" (الكمية: ${qty}، السعر: ${price}) من موقعكم.`);
                    window.open(`https://wa.me/970599000000?text=${text}`, '_blank');
                });
            }

            // --- Chatbot Controller Logic ---
            const chatbotToggleBtn = document.getElementById('chatbotToggleBtn');
            const chatbotWidget = document.getElementById('chatbotWidget');
            const closeChatbotBtn = document.getElementById('closeChatbotBtn');
            const chatbotMessages = document.getElementById('chatbotMessages');
            const chatbotInput = document.getElementById('chatbotInput');
            const chatbotSendBtn = document.getElementById('chatbotSendBtn');

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
                        `â€¢ <strong>Ø³Ø¨Ù„Ø§Ø´ Ø³ÙŠÙƒØ³ÙŠ Ø¨ÙŠÙ†Ùƒ (75 â‚ª):</strong> Ø«Ø¨Ø§Øª ÙˆÙ†Ø¹ÙˆÙ…Ø© ÙˆØ¬Ø§Ø°Ø¨ÙŠØ© Ù„Ø§ ØªÙ‚Ø§ÙˆÙ… âœ¨.<br>` +
                        `â€¢ <strong>Ø³Ø¨Ù„Ø§Ø´ Ù…Ø³Ùƒ Ø£Ø¨ÙŠØ¶ (75 â‚ª):</strong> Ø±Ø§Ø¦Ø­Ø© Ø§Ù„Ù†Ø¸Ø§ÙØ© ÙˆØ§Ù„Ø§Ù†ØªØ¹Ø§Ø´ Ø§Ù„ÙØ§Ø®Ø±Ø© ðŸ¤.<br>` +
                        `â€¢ <strong>Ù…Ø±Ø·Ø¨ Ø²Ø¨Ø¯Ø© Ø§Ù„Ø´ÙŠØ§ (60 â‚ª):</strong> ØªØ±Ø·ÙŠØ¨ Ù…Ø®Ù…Ù„ÙŠ ÙˆÙ†Ø¹ÙˆÙ…Ø© ØªØ¯ÙˆÙ… Ø·ÙˆÙŠÙ„Ø§Ù‹.<br><br>` +
                        `ÙŠÙ…ÙƒÙ†Ùƒ Ø§Ø³ØªØ¹Ø±Ø§Ø¶ Ù‡Ø°Ù‡ Ø§Ù„Ù…Ù†ØªØ¬Ø§Øª Ø§Ù„Ø±Ø§Ø¦Ø¹Ø© ÙÙŠ Ù‚Ø³Ù… Ø§Ù„Ø¹Ù†Ø§ÙŠØ© Ø¨Ø§Ù„Ø¬Ø³Ù… ÙÙŠ Ø§Ù„Ø£Ø³ÙÙ„! Ù‡Ù„ ØªÙˆØ¯ Ø·Ù„Ø¨ Ø£Ø­Ø¯Ù‡Ø§ØŸ`;
                } else if (type === 'Ø´Ø¹Ø±') {
                    replyText = `ðŸ’‡â€â™€ï¸ <strong>Ù…Ù†ØªØ¬Ø§Øª Ø§Ù„Ø¹Ù†Ø§ÙŠØ© Ø¨Ø§Ù„Ø´Ø¹Ø± Ø§Ù„ÙØ§Ø®Ø±Ø©:</strong><br><br>` +
                        `â€¢ <strong>Ø³ÙŠØ±ÙˆÙ… ÙƒÙŠØ±Ø§ØªÙŠÙ† Ù…Ø¹Ø§Ù„Ø¬ (90 â‚ª):</strong> Ø¥ØµÙ„Ø§Ø­ ÙÙˆØ±ÙŠ ÙˆØªÙ†Ø¹ÙŠÙ… Ø¹Ù…ÙŠÙ‚ Ù„Ù„Ø´Ø¹Ø± Ø§Ù„ØªØ§Ù„Ù ðŸ˜.<br>` +
                        `â€¢ <strong>Ø´Ø§Ù…Ø¨Ùˆ ÙƒÙŠØ±Ø§ØªÙŠÙ† (60 â‚ª) & Ø¨Ù„Ø³Ù… (65 â‚ª):</strong> Ù„Ø­ÙŠÙˆÙŠØ© ÙˆÙ‚ÙˆØ© ÙˆÙ„Ù…Ø¹Ø§Ù† Ù„Ø´Ø¹Ø±Ùƒ.<br>` +
                        `â€¢ <strong>Ø¹Ø·Ø± Ø´Ø¹Ø± Ø³ÙˆÙŠØª (85 â‚ª):</strong> Ø±Ø§Ø¦Ø­Ø© ÙØ±Ù…ÙˆÙ†ÙŠØ© Ø³Ø§Ø­Ø±Ø© ØªØ±Ø§ÙÙ‚Ùƒ Ø·ÙˆØ§Ù„ Ø§Ù„ÙŠÙˆÙ… ðŸŒ¸.<br><br>` +
                        `Ù‡Ù„ ØªØ±ØºØ¨ ÙÙŠ ØªØ­Ø³ÙŠÙ† ØµØ­Ø© Ø´Ø¹Ø±Ùƒ ÙˆØªØ¬Ø±Ø¨Ø© Ø£Ø­Ø¯ Ù‡Ø°Ù‡ Ø§Ù„Ù…Ù†ØªØ¬Ø§Øª Ø§Ù„ÙØ§Ø®Ø±Ø©ØŸ`;
                } else if (type === 'ÙˆØ¬Ù‡') {
                    replyText = `âœ¨ <strong>Ù…Ù†ØªØ¬Ø§Øª Ø§Ù„Ù†Ø¶Ø§Ø±Ø© ÙˆØ­Ù…Ø§ÙŠØ© Ø§Ù„ÙˆØ¬Ù‡:</strong><br><br>` +
                        `â€¢ <strong>Ø³ÙŠØ±ÙˆÙ… ÙÙŠØªØ§Ù…ÙŠÙ† C Ø§Ù„ÙØ§Ø®Ø± (110 â‚ª):</strong> ØªÙØªÙŠØ­ ÙˆÙ†Ø¶Ø§Ø±Ø© ÙˆÙ…ÙƒØ§ÙØ­Ø© Ù„Ø¹Ù„Ø§Ù…Ø§Øª Ø§Ù„ØªØ¹Ø¨ ðŸ§¡.<br>` +
                        `â€¢ <strong>ÙˆØ§Ù‚ÙŠ Ø´Ù…Ø³ SPF50 (80 â‚ª):</strong> Ø­Ù…Ø§ÙŠØ© ÙØ§Ø¦Ù‚Ø© Ù…Ù† Ø§Ù„Ø´Ù…Ø³ ÙˆØªØ±Ø·ÙŠØ¨ Ø¹Ù…ÙŠÙ‚ Ø¨Ø¯ÙˆÙ† Ø£Ø«Ø± Ø¯Ù‡Ù†ÙŠ â˜€ï¸.<br>` +
                        `â€¢ <strong>Ø³ÙŠØ±ÙˆÙ… Ù‡ÙŠØ§Ù„ÙˆØ±ÙˆÙ†ÙŠÙƒ (120 â‚ª):</strong> Ù†Ø¶Ø§Ø±Ø© ÙˆØ§Ù…ØªÙ„Ø§Ø¡ ÙÙˆØ±ÙŠ Ù„Ù„Ø¨Ø´Ø±Ø©.<br><br>` +
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
                        `â€¢ <strong>Ø³ÙŠØ±ÙˆÙ… ÙÙŠØªØ§Ù…ÙŠÙ† C Ù„Ù„ÙˆØ¬Ù‡</strong> (110 â‚ª) Ù„Ù„Ù†Ø¶Ø§Ø±Ø© ÙˆØ§Ù„ØªÙØªÙŠØ­.<br>` +
                        `â€¢ <strong>Ø³ÙŠØ±ÙˆÙ… ÙƒÙŠØ±Ø§ØªÙŠÙ† Ù…Ø¹Ø§Ù„Ø¬ Ù„Ù„Ø´Ø¹Ø±</strong> (90 â‚ª) Ù„Ø¥ØµÙ„Ø§Ø­ Ø§Ù„ØªÙ„Ù ÙˆØªÙ†Ø¹ÙŠÙ… Ø§Ù„Ø´Ø¹Ø±.<br><br>` +
                        `ØªÙˆØ¯ Ø·Ù„Ø¨ Ø£ÙŠÙ‡Ù…Ø§ Ù„ØªÙˆØµÙŠÙ„Ù‡ Ù„ÙƒØŸ ðŸ˜Š`;
                } else if (msg.includes('Ø³Ø¨Ù„Ø§Ø´') || msg.includes('Ø¹Ø·Ø±') || msg.includes('Ø±Ø§Ø¦Ø­Ø©') || msg.includes('ÙØ±Ù…ÙˆÙ†')) {
                    replyText = `Ø¹Ø§Ù„Ù… Ø§Ù„ÙØ±Ù…ÙˆÙ†Ø§Øª ÙˆØ§Ù„Ø¬Ø§Ø°Ø¨ÙŠØ© ÙÙŠ Ù‡ÙŠÙ„Ø«ÙŠ Ø´ÙˆØ¨ Ù…ØªÙ…ÙŠØ² Ø¬Ø¯Ø§Ù‹! âœ¨<br>` +
                        `Ù†Ù†ØµØ­Ùƒ Ø¨Ù€ <strong>Ø³Ø¨Ù„Ø§Ø´ Ø³ÙŠÙƒØ³ÙŠ Ø¨ÙŠÙ†Ùƒ</strong> Ø§Ù„ÙØ±Ù…ÙˆÙ†ÙŠ (75 â‚ª) Ø£Ùˆ <strong>Ø¹Ø·Ø± Ø§Ù„Ø´Ø¹Ø± Ø³ÙˆÙŠØª</strong> (85 â‚ª) Ù„Ø±Ø§Ø¦Ø­Ø© Ø³Ø§Ø­Ø±Ø© ØªØ¯ÙˆÙ… Ø·ÙˆÙŠÙ„Ø§Ù‹.`;
                } else if (msg.includes('Ø³Ø¹Ø±') || msg.includes('Ø¨ÙƒÙ…') || msg.includes('Ø§Ù„Ø£Ø³Ø¹Ø§Ø±') || msg.includes('Ø´ÙŠÙƒÙ„')) {
                    replyText = `Ø£Ø³Ø¹Ø§Ø± Ù…Ù†ØªØ¬Ø§ØªÙ†Ø§ Ù…Ù…ÙŠØ²Ø© ÙˆØªÙ†Ø§ÙØ³ÙŠØ© Ø¬Ø¯Ø§Ù‹ Ù…Ù‚Ø§Ø±Ù†Ø© Ø¨Ø§Ù„Ø¬ÙˆØ¯Ø© Ø§Ù„ÙØ§Ø®Ø±Ø©:<br>` +
                        `â€¢ Ø³Ø¨Ù„Ø§Ø´ Ø§Ù„Ø¬Ø³Ù…: 75 â‚ª<br>` +
                        `â€¢ ÙˆØ§Ù‚ÙŠ Ø§Ù„Ø´Ù…Ø³: 80 â‚ª<br>` +
                        `â€¢ Ø¹Ø·ÙˆØ± Ø§Ù„Ø´Ø¹Ø±: 85 â‚ª<br>` +
                        `â€¢ Ø³ÙŠØ±ÙˆÙ… Ø§Ù„Ø´Ø¹Ø±: 90 â‚ª<br>` +
                        `â€¢ Ø³ÙŠØ±ÙˆÙ… Ø§Ù„ÙˆØ¬Ù‡: 110 â‚ª<br><br>` +
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
    

