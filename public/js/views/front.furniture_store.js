(() => {
    const cart = [],
        money = (v) => Number(v || 0).toFixed(2) + " ₪",
        tabs = [...document.querySelectorAll("[data-tab]")],
        panes = [...document.querySelectorAll("[data-pane]")],
        $ = (id) => document.getElementById(id);
    tabs.forEach(
        (tab) =>
            (tab.onclick = () => {
                tabs.forEach((x) => x.classList.toggle("active", x === tab));
                panes.forEach((p) => (p.hidden = p.dataset.pane !== tab.dataset.tab));
            }),
    );
    const render = () => {
        const total = cart.reduce((s, x) => s + x.price * x.qty, 0);
        $("cartCount").textContent = cart.reduce((s, x) => s + x.qty, 0);
        $("cartTotal").textContent = $("sheetTotal").textContent = money(total);
        $("cartItems").innerHTML = cart.length
            ? cart
                  .map(
                      (x) =>
                          '<div class="row"><span>' +
                          x.qty +
                          "× " +
                          x.name +
                          "</span><span>" +
                          money(x.price * x.qty) +
                          ' <button class="remove" data-remove="' +
                          x.id +
                          '">×</button></span></div>',
                  )
                  .join("")
            : '<div class="empty" style="min-height:120px">السلة فاضية… اختار قطعة تحبها لبيتك.</div>';
    };
    let popTimer;
    const pop = () => {
        const target = $("cartButton");
        clearTimeout(popTimer);
        target.classList.remove("cart-pop");
        void target.offsetWidth;
        target.classList.add("cart-pop");
        popTimer = setTimeout(() => target.classList.remove("cart-pop"), 950);
    };
    const fly = (button) => {
        const target = $("cartButton"),
            source = button.closest(".product,.look-item")?.querySelector("img");
        if (!source || window.matchMedia?.("(prefers-reduced-motion: reduce)").matches) {
            pop();
            return;
        }
        const from = source.getBoundingClientRect(),
            to = target.getBoundingClientRect(),
            size = Math.min(68, Math.max(46, Math.min(from.width, from.height) * 0.34)),
            flight = document.createElement("div");
        flight.className = "cart-flight";
        flight.style.cssText =
            "width:" +
            size +
            "px;height:" +
            size +
            "px;left:" +
            (from.left + (from.width - size) / 2) +
            "px;top:" +
            (from.top + (from.height - size) / 2) +
            "px";
        const media = document.createElement("span");
        media.className = "cart-flight-media";
        const image = source.cloneNode(true);
        image.alt = "";
        media.append(image);
        const stars = document.createElement("span");
        stars.className = "cart-flight-stars";
        stars.textContent = "✦  ✧  ✦";
        flight.append(media, stars);
        document.body.append(flight);
        const x = to.left + to.width / 2 - (from.left + from.width / 2),
            y = to.top + to.height / 2 - (from.top + from.height / 2),
            d = Math.hypot(x, y) || 1;
        stars.style.setProperty("--trail-x", (-x / d) * 56 + "px");
        stars.style.setProperty("--trail-y", (-y / d) * 56 + "px");
        flight
            .animate(
                [
                    { transform: "translate(0,0) scale(1)", opacity: 1 },
                    {
                        transform: "translate(" + x * 0.35 + "px," + (y * 0.18 - 74) + "px) scale(.82) rotate(-9deg)",
                        opacity: 1,
                        offset: 0.42,
                    },
                    { transform: "translate(" + x + "px," + y + "px) scale(.16) rotate(12deg)", opacity: 0.2 },
                ],
                { duration: 5000, easing: "cubic-bezier(.16,.8,.24,1)", fill: "forwards" },
            )
            .finished.catch(() => {})
            .finally(() => {
                flight.remove();
                pop();
            });
    };
    const add = (button) => {
        const old = cart.find((x) => String(x.id) === button.dataset.id);
        if (old) old.qty++;
        else
            cart.push({
                id: button.dataset.id,
                name: button.dataset.name,
                price: Number(button.dataset.price),
                qty: 1,
            });
        render();
        fly(button);
    };
    document.addEventListener("click", (event) => {
        const button = event.target.closest("[data-add]");
        if (button) add(button);
    });
    $("addRoomLook").onclick = () => {
        document.querySelectorAll(".look-item [data-add]").forEach((button) => {
            const old = cart.find((x) => String(x.id) === button.dataset.id);
            if (old) old.qty++;
            else
                cart.push({
                    id: button.dataset.id,
                    name: button.dataset.name,
                    price: Number(button.dataset.price),
                    qty: 1,
                });
        });
        render();
        pop();
    };
    $("cartItems").onclick = (e) => {
        const button = e.target.closest("[data-remove]");
        if (!button) return;
        const index = cart.findIndex((x) => String(x.id) === button.dataset.remove);
        if (index > -1) cart.splice(index, 1);
        render();
    };
    const layer = $("cartLayer");
    $("cartButton").onclick = () => {
        layer.classList.add("open");
        render();
    };
    $("cartClose").onclick = () => layer.classList.remove("open");
    layer.onclick = (e) => {
        if (e.target === layer) layer.classList.remove("open");
    };
    $("sendOrder").onclick = () => {
        if (!cart.length) return;
        const name = $("customerName").value.trim(),
            phone = $("customerPhone").value.trim();
        if (!name || !phone) {
            alert("اكتب الاسم ورقم الجوال أولًا.");
            return;
        }
        window.OzmanSaveCatalogOrder(cart, name, phone, $("sendOrder"));
    };
    const lightbox = $("lightbox"),
        image = lightbox.querySelector("img");
    document.querySelectorAll("[data-image]").forEach(
        (button) =>
            (button.onclick = () => {
                image.src = button.dataset.image;
                lightbox.classList.add("open");
            }),
    );
    lightbox.onclick = (e) => {
        if (e.target === lightbox || e.target.tagName === "BUTTON") lightbox.classList.remove("open");
    };
    const slides = [...document.querySelectorAll("[data-slide]")];
    let current = 0;
    if (slides.length > 1)
        setInterval(() => {
            slides[current].classList.remove("active");
            current = (current + 1) % slides.length;
            slides[current].classList.add("active");
        }, 6500);
    render();
})();
