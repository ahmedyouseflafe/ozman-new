(() => {
    const cart = [],
        money = (value) => Number(value || 0).toFixed(2) + " ₪";
    const tabs = [...document.querySelectorAll("[data-tab]")],
        panes = [...document.querySelectorAll("[data-pane]")];
    tabs.forEach(
        (tab) =>
            (tab.onclick = () => {
                tabs.forEach((item) => item.classList.toggle("active", item === tab));
                panes.forEach((pane) => (pane.hidden = pane.dataset.pane !== tab.dataset.tab));
            }),
    );
    const count = document.getElementById("cartCount"),
        total = document.getElementById("cartTotal"),
        sheetTotal = document.getElementById("sheetTotal"),
        items = document.getElementById("cartItems");
    function renderCart() {
        const sum = cart.reduce((value, item) => value + item.price * item.quantity, 0);
        count.textContent = cart.reduce((value, item) => value + item.quantity, 0);
        total.textContent = money(sum);
        sheetTotal.textContent = money(sum);
        items.innerHTML = cart.length
            ? cart
                  .map(
                      (item) =>
                          '<div class="row"><span>' +
                          item.quantity +
                          "× " +
                          item.name +
                          "</span><span>" +
                          money(item.price * item.quantity) +
                          ' <button class="remove" type="button" data-remove="' +
                          item.id +
                          '">×</button></span></div>',
                  )
                  .join("")
            : '<div class="empty" style="min-height:120px">السلة فارغة، اختار صنفًا لذيذًا أولًا 🍩</div>';
    }
    let cartPopTimer;
    const popCart = () => {
        const cartButton = document.getElementById("cartButton");
        clearTimeout(cartPopTimer);
        cartButton.classList.remove("cart-flight-pop");
        void cartButton.offsetWidth;
        cartButton.classList.add("cart-flight-pop");
        cartPopTimer = setTimeout(() => cartButton.classList.remove("cart-flight-pop"), 950);
    };
    const flyToSweetCart = (button) => {
        const cartButton = document.getElementById("cartButton");
        const source = button.closest(".card")?.querySelector(".picture");
        if (window.matchMedia?.("(prefers-reduced-motion: reduce)").matches || !source) {
            popCart();
            return;
        }
        const from = source.getBoundingClientRect(),
            to = cartButton.getBoundingClientRect();
        const size = Math.min(68, Math.max(46, Math.min(from.width, from.height) * 0.34));
        const flight = document.createElement("div");
        flight.className = "shopping-cart-flight";
        flight.style.width = size + "px";
        flight.style.height = size + "px";
        flight.style.left = from.left + (from.width - size) / 2 + "px";
        flight.style.top = from.top + (from.height - size) / 2 + "px";
        const media = document.createElement("span");
        media.className = "shopping-cart-flight-media";
        const image = source.querySelector("img");
        if (image?.currentSrc || image?.src) {
            const clone = document.createElement("img");
            clone.src = image.currentSrc || image.src;
            clone.alt = "";
            media.append(clone);
        } else media.innerHTML = '<i class="ti ti-shopping-bag"></i>';
        const stars = document.createElement("span");
        stars.className = "shopping-cart-flight-stars";
        stars.innerHTML = "<b>✦</b><b>✧</b><b>✦</b>";
        flight.append(media, stars);
        document.body.append(flight);
        const x = to.left + to.width / 2 - (from.left + from.width / 2),
            y = to.top + to.height / 2 - (from.top + from.height / 2);
        const distance = Math.hypot(x, y) || 1;
        stars.style.setProperty("--trail-x", (-x / distance) * 56 + "px");
        stars.style.setProperty("--trail-y", (-y / distance) * 56 + "px");
        flight
            .animate(
                [
                    { transform: "translate(0,0) scale(1)", opacity: 1 },
                    {
                        transform: "translate(" + x * 0.35 + "px," + (y * 0.18 - 74) + "px) scale(.82) rotate(-9deg)",
                        opacity: 1,
                        offset: 0.42,
                    },
                    { transform: "translate(" + x + "px," + y + "px) scale(.16) rotate(12deg)", opacity: 0.22 },
                ],
                { duration: 5000, easing: "cubic-bezier(.16,.8,.24,1)", fill: "forwards" },
            )
            .finished.catch(() => {})
            .finally(() => {
                flight.remove();
                popCart();
            });
    };
    document.addEventListener("click", (event) => {
        const button = event.target.closest("[data-add]");
        if (!button) return;
        const found = cart.find((item) => item.id === button.dataset.add);
        if (found) found.quantity += 1;
        else
            cart.push({
                id: button.dataset.add,
                name: button.dataset.name,
                price: Number(button.dataset.price),
                quantity: 1,
            });
        renderCart();
        flyToSweetCart(button);
    });
    items.onclick = (event) => {
        const button = event.target.closest("[data-remove]");
        if (!button) return;
        const index = cart.findIndex((item) => item.id === button.dataset.remove);
        if (index >= 0) cart.splice(index, 1);
        renderCart();
    };
    const layer = document.getElementById("cartLayer");
    document.getElementById("cartButton").onclick = () => {
        layer.classList.add("open");
        renderCart();
    };
    document.getElementById("cartClose").onclick = () => layer.classList.remove("open");
    layer.onclick = (event) => {
        if (event.target === layer) layer.classList.remove("open");
    };
    document.getElementById("sendOrder").onclick = () => {
        if (!cart.length) return;
        const name = document.getElementById("customerName").value.trim(),
            phone = document.getElementById("customerPhone").value.trim();
        if (!name || !phone) {
            alert("اكتب الاسم ورقم الجوال أولًا.");
            return;
        }
        window.OzmanSaveCatalogOrder(cart, name, phone, document.getElementById("sendOrder"));
    };
    const lightbox = document.getElementById("lightbox"),
        image = lightbox.querySelector("img");
    document.querySelectorAll("[data-image]").forEach(
        (button) =>
            (button.onclick = () => {
                image.src = button.dataset.image;
                lightbox.classList.add("open");
            }),
    );
    lightbox.onclick = (event) => {
        if (event.target === lightbox || event.target.tagName === "BUTTON") lightbox.classList.remove("open");
    };
    const slides = [...document.querySelectorAll("[data-slide]")];
    let current = 0;
    if (slides.length > 1)
        setInterval(() => {
            slides[current].classList.remove("active");
            current = (current + 1) % slides.length;
            slides[current].classList.add("active");
        }, 6500);
    renderCart();
})();
