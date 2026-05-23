<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>إدارة المنتجات — هيلني شوب</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        :root {
            --red: #C0392B;
            --red-light: #E74C3C;
            --red-dark: #922B21;
            --bg: #0a0a0a;
            --bg2: #111;
            --bg3: #1a1a1a;
            --bg4: #222;
            --border: #2a2a2a;
            --border2: #333;
            --text: #f0f0f0;
            --text2: #aaa;
            --text3: #666;
            --gold: #D4AC0D;
        }

        html,
        body {
            height: 100%;
            background: var(--bg);
            color: var(--text);
            font-family: 'Segoe UI', Tahoma, sans-serif;
            direction: rtl
        }

        .shell {
            display: flex;
            min-height: 100vh
        }

        .sidebar {
            width: 210px;
            background: var(--bg2);
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: fixed;
            top: 0;
            right: 0;
            height: 100vh;
            overflow-y: auto
        }

        .logo {
            padding: 20px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px
        }

        .logo-icon {
            width: 36px;
            height: 36px;
            background: var(--red);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 900;
            color: #fff;
            flex-shrink: 0
        }

        .logo-text {
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
            line-height: 1.2
        }

        .logo-sub {
            font-size: 10px;
            color: var(--text3)
        }

        nav {
            padding: 12px 0;
            flex: 1
        }

        .nav-section {
            padding: 4px 12px 2px;
            font-size: 10px;
            color: var(--text3);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 8px
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            cursor: pointer;
            font-size: 13px;
            color: var(--text2);
            border-right: 2px solid transparent;
            text-decoration: none;
            transition: all .15s
        }

        .nav-item:hover {
            background: var(--bg3);
            color: var(--text)
        }

        .nav-item.active {
            background: rgba(192, 57, 43, .12);
            color: var(--red-light);
            border-right-color: var(--red)
        }

        .nav-item i {
            font-size: 15px;
            width: 18px;
            text-align: center
        }

        .main {
            flex: 1;
            margin-right: 210px;
            overflow: auto;
            background: var(--bg)
        }

        .topbar {
            background: var(--bg2);
            border-bottom: 1px solid var(--border);
            padding: 0 24px;
            height: 54px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 10
        }

        .topbar-title {
            font-size: 15px;
            font-weight: 600
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px
        }

        .topbar-btn {
            background: transparent;
            border: 1px solid var(--border2);
            color: var(--text2);
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            transition: all .15s;
            text-decoration: none
        }

        .topbar-btn:hover {
            border-color: var(--red);
            color: var(--red-light)
        }

        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--red-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer
        }

        .content {
            padding: 24px
        }

        .page-header {
            margin-bottom: 20px
        }

        .page-header h1 {
            font-size: 20px;
            font-weight: 700
        }

        .page-header p {
            font-size: 13px;
            color: var(--text3);
            margin-top: 2px
        }

        .page-header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px
        }

        .page-header-row h1 {
            font-size: 20px;
            font-weight: 700
        }

        .page-header-row p {
            font-size: 13px;
            color: var(--text3);
            margin-top: 2px
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 20px
        }

        .stat-card {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px;
            position: relative;
            overflow: hidden
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 3px;
            height: 100%;
            background: var(--accent, var(--red))
        }

        .stat-label {
            font-size: 11px;
            color: var(--text3);
            margin-bottom: 6px
        }

        .stat-val {
            font-size: 26px;
            font-weight: 800;
            color: var(--text);
            line-height: 1
        }

        .stat-sub {
            font-size: 11px;
            margin-top: 6px
        }

        .stat-up {
            color: #27AE60
        }

        .stat-down {
            color: #E74C3C
        }

        .stat-icon {
            position: absolute;
            bottom: 10px;
            left: 12px;
            font-size: 28px;
            opacity: .1;
            pointer-events: none
        }

        .grid2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 14px
        }

        .card {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 14px
        }

        .card:last-child {
            margin-bottom: 0
        }

        .card-hd {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px
        }

        .card-hd h3 {
            font-size: 14px;
            font-weight: 600
        }

        .badge {
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 20px;
            background: rgba(192, 57, 43, .18);
            color: var(--red-light)
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px
        }

        th {
            text-align: right;
            padding: 6px 8px;
            color: var(--text3);
            font-weight: 500;
            border-bottom: 1px solid var(--border)
        }

        td {
            padding: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, .04);
            color: var(--text2);
            vertical-align: middle
        }

        tr:last-child td {
            border-bottom: none
        }

        tr:hover td {
            background: rgba(255, 255, 255, .02)
        }

        .tag {
            display: inline-block;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 500
        }

        .tag-g {
            background: rgba(39, 174, 96, .15);
            color: #27AE60
        }

        .tag-r {
            background: rgba(231, 76, 60, .15);
            color: #E74C3C
        }

        .tag-y {
            background: rgba(241, 196, 15, .15);
            color: #F1C40F
        }

        .btn-red {
            background: var(--red);
            color: #fff;
            border: none;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none
        }

        .btn-red:hover {
            background: var(--red-light)
        }

        .input-wrap {
            position: relative
        }

        .search-inp {
            background: var(--bg3);
            border: 1px solid var(--border2);
            color: var(--text);
            padding: 6px 10px 6px 30px;
            border-radius: 6px;
            font-size: 12px;
            outline: none;
            width: 180px
        }

        .search-inp::placeholder {
            color: var(--text3)
        }

        .search-icon {
            position: absolute;
            left: 9px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text3);
            font-size: 13px;
            pointer-events: none
        }

        .filter-row {
            display: flex;
            gap: 8px;
            align-items: center
        }

        select {
            background: var(--bg3);
            border: 1px solid var(--border2);
            color: var(--text2);
            padding: 5px 8px;
            border-radius: 6px;
            font-size: 12px;
            outline: none;
            cursor: pointer
        }

        input[type=text],
        input[type=email],
        input[type=password],
        input[type=tel] {
            background: var(--bg3);
            border: 1px solid var(--border2);
            color: var(--text);
            padding: 8px 10px;
            border-radius: 6px;
            font-size: 13px;
            outline: none;
            width: 100%;
            direction: rtl
        }

        input:focus {
            border-color: var(--red)
        }

        .form-group {
            margin-bottom: 12px
        }

        .form-label {
            font-size: 11px;
            color: var(--text3);
            margin-bottom: 4px;
            display: block
        }

        .recent-list {
            display: flex;
            flex-direction: column;
            gap: 8px
        }

        .recent-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            background: var(--bg3);
            border-radius: 7px
        }

        .ri-icon {
            width: 30px;
            height: 30px;
            border-radius: 7px;
            background: var(--bg4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: var(--red-light);
            flex-shrink: 0
        }

        .ri-info {
            flex: 1
        }

        .ri-title {
            font-size: 12px;
            font-weight: 500;
            color: var(--text)
        }

        .ri-sub {
            font-size: 10px;
            color: var(--text3)
        }

        .ri-val {
            font-size: 13px;
            font-weight: 700;
            color: var(--red-light)
        }

        .chart-bars {
            display: flex;
            align-items: flex-end;
            gap: 6px;
            height: 90px;
            padding-top: 8px
        }

        .bar-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px
        }

        .bar {
            width: 100%;
            background: linear-gradient(to top, var(--red-dark), var(--red-light));
            border-radius: 3px 3px 0 0
        }

        .bar-label {
            font-size: 9px;
            color: var(--text3)
        }

        .cat-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px
        }

        .cat-card {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 20px;
            text-align: center
        }

        .cat-icon-wrap {
            width: 54px;
            height: 54px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px
        }

        .avatar-circle {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0
        }
    </style>
</head>

<body>
    <div class="shell">
        <div class="sidebar">
            <div class="logo">
                <div class="logo-icon">H</div>
                <div>
                    <div class="logo-text">هيلني شوب</div>
                    <div class="logo-sub">لوحة التحكم</div>
                </div>
            </div>
          @include('admin.includes.sidebar')
        </div>
        <div class="main">
            <div class="topbar">
                <div class="topbar-title">إدارة المنتجات</div>
                <div class="topbar-right">
                    <div class="input-wrap">
                        <i class="ti ti-search search-icon" aria-hidden="true"></i>
                        <input class="search-inp" placeholder="بحث...">
                    </div>
                    <a href="#" class="topbar-btn" aria-label="إشعارات"><i class="ti ti-bell"
                            aria-hidden="true"></i></a>
                    <a href="settings.html" class="topbar-btn" aria-label="إعدادات"><i class="ti ti-settings"
                            aria-hidden="true"></i></a>
                    <div class="avatar" title="المشرف">A</div>
                </div>
            </div>
            <div class="content">

                <div class="page-header-row">
                    <div>
                        <h1>إدارة المنتجات</h1>
                        <p>1,248 منتج في جميع المتاجر</p>
                    </div>
                    <a href="#" class="btn-red"><i class="ti ti-plus" aria-hidden="true"></i> منتج جديد</a>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">إجمالي المنتجات</div>
                        <div class="stat-val">1,248</div><i class="ti ti-package stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent:var(--gold)">
                        <div class="stat-label">المميزة</div>
                        <div class="stat-val" style="color:var(--gold)">48</div><i class="ti ti-star stat-icon"
                            aria-hidden="true"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">نفد المخزون</div>
                        <div class="stat-val" style="color:var(--red-light)">17</div><i
                            class="ti ti-alert-triangle stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent:#2980B9">
                        <div class="stat-label">متوسط السعر</div>
                        <div class="stat-val">32₪</div><i class="ti ti-coin stat-icon" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="card">
                    <div class="card-hd">
                        <h3>قائمة المنتجات</h3>
                        <div class="filter-row">
                            <select id="catFilter">
                                <option value="">كل الفئات</option>
                                <option>مشروبات</option>
                                <option>العناية بالبشرة</option>
                                <option>العناية بالشعر</option>
                                <option>العناية للرجال</option>
                            </select>
                            <div class="input-wrap"><i class="ti ti-search search-icon" aria-hidden="true"></i><input
                                    class="search-inp" id="prodSearch" placeholder="بحث..."></div>
                        </div>
                    </div>
                    <table id="prodTable">
                        <thead>
                            <tr>
                                <th>الصورة</th>
                                <th>الاسم</th>
                                <th>الفئة</th>
                                <th>السعر</th>
                                <th>سعر الخصم</th>
                                <th>الكمية</th>
                                <th>التقييم</th>
                                <th>مميز</th>
                                <th>الحالة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr data-cat="مشروبات">
                                <td>
                                    <div
                                        style="width:32px;height:32px;border-radius:6px;background:var(--bg4);display:flex;align-items:center;justify-content:center">
                                        <i class="ti ti-bottle" style="color:var(--red-light);font-size:16px"
                                            aria-hidden="true"></i></div>
                                </td>
                                <td style="color:var(--text);font-weight:500">Cola الأصلية</td>
                                <td>مشروبات</td>
                                <td style="color:var(--red-light);font-weight:600">15₪</td>
                                <td style="color:#27AE60">12₪</td>
                                <td>240</td>
                                <td>⭐ 4.8</td>
                                <td><span class="tag tag-y">نعم</span></td>
                                <td><span class="tag tag-g">نشط</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                            <tr data-cat="العناية بالبشرة">
                                <td>
                                    <div
                                        style="width:32px;height:32px;border-radius:6px;background:var(--bg4);display:flex;align-items:center;justify-content:center">
                                        <i class="ti ti-droplet" style="color:#8E44AD;font-size:16px"
                                            aria-hidden="true"></i></div>
                                </td>
                                <td style="color:var(--text);font-weight:500">كريم العناية بالوجه</td>
                                <td>العناية بالبشرة</td>
                                <td style="color:var(--red-light);font-weight:600">45₪</td>
                                <td>—</td>
                                <td>80</td>
                                <td>⭐ 4.5</td>
                                <td><span class="tag tag-r">لا</span></td>
                                <td><span class="tag tag-g">نشط</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                            <tr data-cat="العناية بالشعر">
                                <td>
                                    <div
                                        style="width:32px;height:32px;border-radius:6px;background:var(--bg4);display:flex;align-items:center;justify-content:center">
                                        <i class="ti ti-spray" style="color:#2980B9;font-size:16px"
                                            aria-hidden="true"></i></div>
                                </td>
                                <td style="color:var(--text);font-weight:500">شامبو Cliven</td>
                                <td>العناية بالشعر</td>
                                <td style="color:var(--red-light);font-weight:600">28₪</td>
                                <td style="color:#27AE60">22₪</td>
                                <td>0</td>
                                <td>⭐ 4.2</td>
                                <td><span class="tag tag-y">نعم</span></td>
                                <td><span class="tag tag-r">نفد</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                            <tr data-cat="العناية للرجال">
                                <td>
                                    <div
                                        style="width:32px;height:32px;border-radius:6px;background:var(--bg4);display:flex;align-items:center;justify-content:center">
                                        <i class="ti ti-gender-male" style="color:#16A085;font-size:16px"
                                            aria-hidden="true"></i></div>
                                </td>
                                <td style="color:var(--text);font-weight:500">ماسكرا للرجال</td>
                                <td>العناية للرجال</td>
                                <td style="color:var(--red-light);font-weight:600">38₪</td>
                                <td>—</td>
                                <td>55</td>
                                <td>⭐ 4.0</td>
                                <td><span class="tag tag-r">لا</span></td>
                                <td><span class="tag tag-g">نشط</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
    <script>
        function filterProds() {
            const q = document.getElementById('prodSearch').value.toLowerCase();
            const cat = document.getElementById('catFilter').value;
            document.querySelectorAll('#prodTable tbody tr').forEach(r => {
                const matchQ = r.textContent.toLowerCase().includes(q);
                const matchC = !cat || r.dataset.cat === cat;
                r.style.display = (matchQ && matchC) ? '' : 'none';
            });
        }
        document.getElementById('prodSearch').addEventListener('input', filterProds);
        document.getElementById('catFilter').addEventListener('change', filterProds);
    </script>
</body>

</html>
