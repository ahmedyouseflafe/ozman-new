const titles = {
    dashboard: "لوحة التحكم",
    shops: "إدارة المتاجر",
    products: "إدارة المنتجات",
    categories: "الفئات",
    agents: "الوكلاء",
    distributors: "الموزعون",
    ads: "الإعلانات",
    screens: "الشاشات الرئيسية",
    users: "إدارة المستخدمين",
    settings: "الإعدادات",
};

Object.assign(titles, {
    dashboard: "لوحة التحكم",
    shops: "إدارة المتاجر",
    products: "إدارة المنتجات",
    categories: "الفئات",
    agents: "الوكلاء",
    distributors: "الموزعون",
    ads: "الإعلانات",
    screens: "الشاشات الرئيسية",
    users: "إدارة المستخدمين",
    settings: "الإعدادات",
});

function showPage(id, el) {
    document.querySelectorAll(".page").forEach((p) => p.classList.remove("active"));
    document.getElementById("page-" + id).classList.add("active");
    document.getElementById("page-title").textContent = titles[id] || id;
    document.querySelectorAll(".nav-item").forEach((n) => n.classList.remove("active"));
    if (el) el.classList.add("active");
    else {
        document.querySelectorAll(".nav-item").forEach((n) => {
            if (n.textContent.trim().includes(titles[id]?.substring(0, 4))) n.classList.add("active");
        });
    }
}
