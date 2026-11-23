(() => {
    const cart = [],
        money = (v) => Number(v || 0).toFixed(2) + " ₪",
        $ = (id) => document.getElementById(id),
        toList = (values) =>
            Array.isArray(values)
                ? values
                : typeof values === "string"
                  ? values
                        .split(",")
                        .map((value) => value.trim())
                        .filter(Boolean)
                  : [];
    let selected = null,
        size = "",
        color = "";
    const render = () => {
        const total = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
        $("bagCount").textContent = cart.reduce((sum, item) => sum + item.qty, 0);
        $("bagTotal").textContent = $("sheetTotal").textContent = money(total);
        $("cartItems").innerHTML = cart.length
            ? cart
                  .map(
                      (item) =>
                          '<div class="cart-row"><span>' +
                          item.qty +
                          "× " +
                          item.name +
                          "</span><span>" +
                          money(item.price * item.qty) +
                          ' <button class="remove" data-remove="' +
                          item.key +
                          '">×</button></span></div>',
                  )
                  .join("")
            : '<div class="empty" style="min-height:120px">السلة فاضية — اختار قطعة تعجبك.</div>';
    };
    const add = (item) => {
        const name = item.name + (item.size ? " · " + item.size : "") + (item.color ? " · " + item.color : "");
        const key = item.id + "|" + (item.size || "") + "|" + (item.color || "");
        const existing = cart.find((entry) => entry.key === key);
        if (existing) existing.qty++;
        else
            cart.push({
                key,
                id: item.id,
                name,
                price: Number(item.price),
                qty: 1,
                size: item.size || null,
                color: item.color || null,
            });
        render();
        $("bag").animate([{ transform: "scale(1)" }, { transform: "scale(1.18)" }, { transform: "scale(1)" }], {
            duration: 480,
            easing: "ease-out",
        });
    };
    const open = (layer) => layer.classList.add("open"),
        close = (layer) => layer.classList.remove("open");
    document
        .querySelectorAll("[data-close]")
        .forEach((button) => (button.onclick = () => close($(button.dataset.close))));
    document.querySelectorAll(".overlay").forEach(
        (layer) =>
            (layer.onclick = (e) => {
                if (e.target === layer) close(layer);
            }),
    );
    $("bag").onclick = () => open($("bagLayer"));
    document.querySelectorAll("[data-filter]").forEach(
        (button) =>
            (button.onclick = () => {
                document
                    .querySelectorAll("[data-filter]")
                    .forEach((item) => item.classList.toggle("active", item === button));
                document
                    .querySelectorAll("[data-card]")
                    .forEach(
                        (card) =>
                            (card.hidden =
                                button.dataset.filter !== "all" && card.dataset.card !== button.dataset.filter),
                    );
            }),
    );
    document.querySelectorAll(".direct-add").forEach((button) => (button.onclick = () => add(button.dataset)));
    const makeOptions = (target, values, kind) => {
        target.innerHTML = "";
        values.forEach((value, index) => {
            const button = document.createElement("button");
            button.type = "button";
            button.textContent = value;
            button.classList.toggle("active", index === 0);
            button.onclick = () => {
                [...target.children].forEach((x) => x.classList.toggle("active", x === button));
                if (kind === "size") size = value;
                else color = value;
            };
            target.append(button);
        });
        if (kind === "size") size = values[0] || "";
        else color = values[0] || "";
    };
    document.querySelectorAll(".preview").forEach(
        (button) =>
            (button.onclick = () => {
                selected = button.dataset;
                $("quickImage").dataset.productId = selected.id;
                size = "";
                color = "";
                $("quickImage").src = selected.image;
                $("quickImage").alt = selected.name;
                $("quickName").textContent = selected.name;
                $("quickPrice").textContent = money(selected.price);
                $("quickDescription").textContent = selected.description || "قطعة مختارة لتكمل ستايلك.";
                let sizes = [],
                    colors = [];
                try {
                    sizes = JSON.parse(selected.sizes || "[]");
                } catch (_) {}
                try {
                    colors = JSON.parse(selected.colors || "[]");
                } catch (_) {}
                sizes = toList(sizes);
                colors = toList(colors);
                $("sizeLabel").hidden = !sizes.length;
                $("sizeOptions").hidden = !sizes.length;
                $("colorLabel").hidden = !colors.length;
                $("colorOptions").hidden = !colors.length;
                makeOptions($("sizeOptions"), sizes, "size");
                makeOptions($("colorOptions"), colors, "color");
                open($("quickLayer"));
            }),
    );
    $("quickAdd").onclick = () => {
        if (!selected) return;
        add({ ...selected, size, color });
        close($("quickLayer"));
    };
    $("cartItems").onclick = (e) => {
        const button = e.target.closest("[data-remove]");
        if (!button) return;
        const index = cart.findIndex((item) => item.key === button.dataset.remove);
        if (index > -1) cart.splice(index, 1);
        render();
    };
    $("sendOrder").onclick = () => {
        if (!cart.length) return;
        const name = $("buyerName").value.trim(),
            phone = $("buyerPhone").value.trim();
        if (!name || !phone) {
            alert("اكتب الاسم ورقم الجوال أولًا.");
            return;
        }
        window.OzmanSaveCatalogOrder(cart, name, phone, $("sendOrder"));
    };
    render();
})();
