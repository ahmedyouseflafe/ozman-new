(() => {
    const rail = document.getElementById("categoryRail"),
        tabs = [...document.querySelectorAll(".category-tab")],
        panes = [...document.querySelectorAll("[data-category-pane]")],
        content = document.getElementById("categoryContent"),
        video = document.getElementById("categoryBackground");
    let active = document.querySelector(".category-tab.active")?.dataset.categoryKey,
        frame;
    const hash = (v) => [...String(v)].reduce((h, c) => Math.imul(h ^ c.charCodeAt(0), 16777619) >>> 0, 2166136261),
        rng = (s) => () => {
            s = Math.imul(s ^ (s >>> 15), s | 1);
            s ^= s + Math.imul(s ^ (s >>> 7), s | 61);
            return ((s ^ (s >>> 14)) >>> 0) / 4294967296;
        };
    const arc = () => {
        if (!rail) return;
        const box = rail.getBoundingClientRect(),
            center = box.top + box.height / 2,
            reach = Math.max(1, box.height / 2);
        tabs.forEach((tab) => {
            const b = tab.getBoundingClientRect(),
                d = Math.min(1, Math.abs(b.top + b.height / 2 - center) / reach),
                curve = Math.cos((d * Math.PI) / 2);
            tab.style.setProperty("--arc-x", `${-(innerWidth <= 720 ? 14 : 22) * curve}px`);
            tab.style.setProperty("--arc-scale", String(0.78 + curve * 0.52));
            tab.style.setProperty("--arc-opacity", String(0.38 + curve * 0.62));
        });
    };
    const scatter = (pane) => {
        const area = pane?.querySelector(".devices"),
            cards = [...(area?.querySelectorAll(".device:not([hidden])") || [])];
        if (!area || !cards.length || innerWidth <= 720) return;
        const width = area.clientWidth;
        if (!width) return;
        const cw = 180,
            ch = 240,
            cols = Math.max(1, Math.floor(width / 215)),
            rows = Math.max(2, Math.ceil(cards.length / cols)),
            height = Math.max(500, rows * 270),
            random = rng(hash(`${pane.dataset.categoryPane}-${pane.dataset.currentPage || 1}`)),
            slots = [];
        for (let r = 0; r < rows; r++) for (let c = 0; c < cols; c++) slots.push({ r, c });
        for (let i = slots.length - 1; i > 0; i--) {
            const j = Math.floor(random() * (i + 1));
            [slots[i], slots[j]] = [slots[j], slots[i]];
        }
        cards.forEach((card, i) => {
            const s = slots[i],
                cellW = width / cols,
                cellH = height / rows,
                x = s.c * cellW + Math.max(0, cellW - cw) * (0.15 + random() * 0.7),
                y = s.r * cellH + Math.max(0, cellH - ch) * random();
            card.style.setProperty("--scatter-x", `${Math.round(x)}px`);
            card.style.setProperty("--scatter-y", `${Math.round(y)}px`);
        });
        area.style.height = `${height}px`;
    };
    const background = (pane) => {
        if (!video || !content) return;
        const src = pane?.dataset.backgroundVideo || "",
            poster = pane?.dataset.backgroundPoster || "";
        if (poster) video.poster = poster;
        else video.removeAttribute("poster");
        if (!src) {
            content.classList.remove("has-video");
            video.pause();
            video.removeAttribute("src");
            video.load();
            return;
        }
        if (video.getAttribute("src") !== src) {
            content.classList.remove("has-video");
            video.src = src;
            video.load();
        }
        video
            .play()
            .then(() => content.classList.add("has-video"))
            .catch(() => {});
    };
    const setCategoryPage = (pane, page) => {
        const cards = [...pane.querySelectorAll(".device[data-local-page]")],
            total = Math.max(1, ...cards.map((card) => Number(card.dataset.localPage) || 1)),
            next = Math.max(1, Math.min(total, page));
        pane.dataset.currentPage = String(next);
        cards.forEach((card) => (card.hidden = Number(card.dataset.localPage) !== next));
        const pager = pane.querySelector("[data-category-pagination]");
        if (pager) {
            pager.querySelector("[data-page-status]").textContent = `صفحة ${next} من ${total}`;
            pager.querySelector('[data-page-move="-1"]').disabled = next === 1;
            pager.querySelector('[data-page-move="1"]').disabled = next === total;
            pager.querySelector("[data-page-numbers]").innerHTML = Array.from(
                { length: total },
                (_, index) =>
                    `<button type="button" class="${index + 1 === next ? "page-current" : "page-link"}" data-page-number="${index + 1}">${index + 1}</button>`,
            ).join("");
        }
        requestAnimationFrame(() => scatter(pane));
    };
    panes.forEach((pane) => {
        pane.dataset.currentPage = "1";
        setCategoryPage(pane, 1);
        pane.querySelector("[data-category-pagination]")?.addEventListener("click", (event) => {
            const number = event.target.closest("[data-page-number]"),
                move = event.target.closest("[data-page-move]");
            if (number) setCategoryPage(pane, Number(number.dataset.pageNumber));
            else if (move) setCategoryPage(pane, Number(pane.dataset.currentPage) + Number(move.dataset.pageMove));
        });
    });
    const activate = (key, center = false) => {
        if (!key) return;
        active = key;
        tabs.forEach((tab) => {
            const on = tab.dataset.categoryKey === key;
            tab.classList.toggle("active", on);
            if (on && center) tab.scrollIntoView({ behavior: "smooth", block: "center" });
        });
        panes.forEach((p) => (p.hidden = p.dataset.categoryPane !== key));
        const pane = panes.find((p) => p.dataset.categoryPane === key);
        background(pane);
        requestAnimationFrame(() => scatter(pane));
    };
    tabs.forEach((tab) => (tab.onclick = () => activate(tab.dataset.categoryKey, true)));
    rail?.addEventListener(
        "scroll",
        () => {
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => {
                arc();
                const b = rail.getBoundingClientRect(),
                    center = b.top + b.height / 2,
                    closest = tabs.reduce((best, t) => {
                        const r = t.getBoundingClientRect(),
                            d = Math.abs(r.top + r.height / 2 - center);
                        return !best || d < best.d ? { t, d } : best;
                    }, null);
                if (closest && closest.t.dataset.categoryKey !== active) activate(closest.t.dataset.categoryKey);
            });
        },
        { passive: true },
    );
    video?.addEventListener("canplay", () => {
        content.classList.add("has-video");
        video.play().catch(() => {});
    });
    addEventListener(
        "resize",
        () => {
            arc();
            scatter(panes.find((p) => !p.hidden));
        },
        { passive: true },
    );
    requestAnimationFrame(() => {
        arc();
        const pane = panes.find((p) => !p.hidden);
        scatter(pane);
        background(pane);
    });
})();
