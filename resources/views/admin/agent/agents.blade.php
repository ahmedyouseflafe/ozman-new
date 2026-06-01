@php
    $agentsList = collect($agents ?? []);
    $agentsCount = $agentsCount ?? $agentsList->count();
    $activeAgentsCount = $activeAgentsCount ?? $agentsList->filter(fn($agent) => data_get($agent, 'status_class') === 'tag-g' || data_get($agent, 'status_label') === 'نشط' || data_get($agent, 'is_active') === true)->count();
    $inactiveAgentsCount = $inactiveAgentsCount ?? max($agentsCount - $activeAgentsCount, 0);
    $coveredCitiesCount = $coveredCitiesCount ?? $agentsList->pluck('shop.city')->filter()->unique()->count();
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>الوكلاء — Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        :root {
            --primary-color: #00e5ff;
            --accent-color: #7000ff;
            --green: #25d366;
            --yellow: #f1c40f;
            --danger: #ff3b30;
            --glass-bg: rgba(255, 255, 255, .05);
            --glass-border: rgba(255, 255, 255, .1);
            --text-main: #fff;
            --text-soft: rgba(255, 255, 255, .72);
            --text-muted: rgba(255, 255, 255, .42);
        }

        html,
        body {
            min-height: 100%;
            background:
                radial-gradient(circle at 50% 4%, rgba(0, 229, 255, .10), transparent 34%),
                radial-gradient(circle at 12% 70%, rgba(112, 0, 255, .10), transparent 28%),
                #050505;
            color: var(--text-main);
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
            direction: rtl;
            overflow-x: hidden
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(rgba(255, 255, 255, .018) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .018) 1px, transparent 1px);
            background-size: 52px 52px;
            mask-image: radial-gradient(circle at center, black, transparent 72%);
            opacity: .5
        }

        .shell {
            display: flex;
            min-height: 100vh
        }

        .main {
            flex: 1;
            margin-right: 245px;
            min-width: 0
        }

        .content {
            padding: 28px;
            max-width: 1500px;
            margin: 0 auto
        }

        .hero-panel {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 24px;
            align-items: center;
            margin-bottom: 24px
        }

        .display-screen {
            min-height: 150px;
            background: rgba(255, 255, 255, .035);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            overflow: hidden;
            display: flex;
            align-items: center;
            position: relative;
            box-shadow: inset 0 0 40px rgba(0, 229, 255, .035)
        }

        .display-screen::before {
            content: '';
            position: absolute;
            inset: -1px;
            border-radius: inherit;
            pointer-events: none;
            background: linear-gradient(90deg, transparent, rgba(0, 229, 255, .2), transparent);
            opacity: .35
        }

        .story-slider {
            display: flex;
            width: max-content;
            animation: slide-rtl 24s linear infinite;
            white-space: nowrap
        }

        .welcome-msg {
            color: var(--primary-color);
            font-weight: 900;
            font-size: 1.02rem;
            padding: 0 48px;
            text-shadow: 0 0 13px rgba(0, 229, 255, .55)
        }

        @keyframes slide-rtl {
            from {
                transform: translateX(0)
            }

            to {
                transform: translateX(50%)
            }
        }

        .hero-orb {
            width: 145px;
            height: 145px;
            border-radius: 50%;
            border: 2px solid var(--primary-color);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #000;
            box-shadow: 0 0 34px rgba(0, 229, 255, .48);
            animation: float 4s ease-in-out infinite
        }

        .hero-orb i {
            font-size: 42px;
            color: var(--primary-color);
            filter: drop-shadow(0 0 12px rgba(0, 229, 255, .8))
        }

        .hero-orb span {
            margin-top: 8px;
            color: var(--text-main);
            font-size: 12px;
            font-weight: 800
        }

        @keyframes float {
            0%,
            100% {
                transform: translateY(0)
            }

            50% {
                transform: translateY(-10px)
            }
        }

        .page-header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            margin-bottom: 22px;
            animation: slideUp .7s ease both
        }

        .page-header-row h1 {
            font-size: 28px;
            font-weight: 900;
            color: var(--primary-color);
            text-shadow: 0 0 16px rgba(0, 229, 255, .45)
        }

        .page-header-row p {
            font-size: 13px;
            color: var(--text-soft);
            margin-top: 4px
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: #fff;
            border: none;
            padding: 11px 22px;
            border-radius: 999px;
            font-size: 13px;
            cursor: pointer;
            font-weight: 900;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            box-shadow: 0 0 20px rgba(0, 229, 255, .28);
            transition: all .3s ease
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 0 28px rgba(0, 229, 255, .58)
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px
        }

        .stat-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 22px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            min-height: 126px;
            backdrop-filter: blur(12px);
            animation: slideUp .8s ease both;
            transition: all .38s cubic-bezier(.175, .885, .32, 1.275)
        }

        .stat-card:nth-child(2) {
            animation-delay: .07s
        }

        .stat-card:nth-child(3) {
            animation-delay: .14s
        }

        .stat-card:nth-child(4) {
            animation-delay: .21s
        }

        .stat-card::before {
            content: '';
            position: absolute;
            inset: auto 18px 0 18px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent, var(--primary-color)), transparent)
        }

        .stat-card:hover {
            transform: translateY(-8px) scale(1.015);
            border-color: var(--accent, var(--primary-color));
            box-shadow: 0 16px 36px rgba(0, 0, 0, .36), 0 0 26px rgba(0, 229, 255, .2)
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-soft);
            margin-bottom: 10px;
            font-weight: 800
        }

        .stat-val {
            font-size: 34px;
            font-weight: 900;
            color: var(--accent, var(--primary-color));
            line-height: 1;
            text-shadow: 0 0 18px rgba(0, 229, 255, .42)
        }

        .stat-icon {
            position: absolute;
            bottom: 14px;
            left: 16px;
            font-size: 42px;
            color: var(--accent, var(--primary-color));
            opacity: .22;
            pointer-events: none;
            filter: drop-shadow(0 0 12px currentColor)
        }

        .card {
            background: rgba(255, 255, 255, .045);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            padding: 18px;
            margin-bottom: 16px;
            backdrop-filter: blur(14px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, .24);
            animation: slideUp .9s ease both
        }

        .card-hd {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--glass-border)
        }

        .card-hd h3 {
            font-size: 16px;
            font-weight: 900;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 8px
        }

        .card-hd h3::before {
            content: '\eb4d';
            font-family: tabler-icons;
            color: var(--primary-color);
            font-size: 20px;
            filter: drop-shadow(0 0 8px rgba(0, 229, 255, .65))
        }

        .input-wrap {
            position: relative
        }

        .search-inp {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-main);
            padding: 9px 12px 9px 36px;
            border-radius: 999px;
            font-size: 12px;
            font-family: inherit;
            outline: none;
            width: 240px;
            min-height: 38px;
            transition: all .3s ease
        }

        .search-inp:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 18px rgba(0, 229, 255, .24)
        }

        .search-inp::placeholder {
            color: var(--text-muted)
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            font-size: 15px;
            pointer-events: none
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 9px;
            font-size: 12px;
            min-width: 900px
        }

        th {
            text-align: right;
            padding: 7px 12px;
            color: var(--primary-color);
            font-weight: 900;
            text-shadow: 0 0 10px rgba(0, 229, 255, .3)
        }

        td {
            padding: 13px 12px;
            background: rgba(255, 255, 255, .035);
            border-top: 1px solid var(--glass-border);
            border-bottom: 1px solid var(--glass-border);
            color: var(--text-soft);
            vertical-align: middle;
            transition: all .3s ease
        }

        td:first-child {
            border-right: 1px solid var(--glass-border);
            border-radius: 0 16px 16px 0
        }

        td:last-child {
            border-left: 1px solid var(--glass-border);
            border-radius: 16px 0 0 16px
        }

        tr:hover td {
            background: rgba(0, 229, 255, .075);
            border-color: rgba(0, 229, 255, .28);
            color: #fff;
            box-shadow: 0 0 18px rgba(0, 229, 255, .08)
        }

        .agent-cell {
            display: flex;
            align-items: center;
            gap: 10px
        }

        .agent-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #000;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            box-shadow: 0 0 14px rgba(0, 229, 255, .35);
            flex-shrink: 0
        }

        .agent-name {
            color: var(--text-main);
            font-weight: 900
        }

        .tag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 64px;
            font-size: 11px;
            padding: 5px 10px;
            border-radius: 999px;
            font-weight: 900;
            border: 1px solid currentColor;
            background: rgba(255, 255, 255, .04)
        }

        .tag-g {
            color: var(--green);
            box-shadow: 0 0 12px rgba(37, 211, 102, .18)
        }

        .tag-r {
            color: var(--danger);
            box-shadow: 0 0 12px rgba(255, 59, 48, .18)
        }

        .tag-y {
            color: var(--yellow);
            box-shadow: 0 0 12px rgba(241, 196, 15, .16)
        }

        .action-btn {
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .1);
            color: rgba(255, 255, 255, .72);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            transition: all .3s ease
        }

        .agent-actions {
            display: flex;
            align-items: center;
            gap: 6px
        }

        .agent-actions form {
            display: inline-flex
        }

        td > button.action-btn {
            display: none
        }

        .status-alert {
            margin-bottom: 18px;
            padding: 14px 16px;
            border: 1px solid rgba(37, 211, 102, .35);
            background: rgba(37, 211, 102, .09);
            color: #fff;
            border-radius: 18px;
            font-size: 13px;
            font-weight: 800
        }

        .action-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-3px) scale(1.08);
            box-shadow: 0 0 16px rgba(0, 229, 255, .35)
        }

        .empty-state {
            text-align: center;
            padding: 54px 16px !important;
            color: var(--text-muted)
        }

        .empty-state i {
            display: block;
            color: var(--primary-color);
            font-size: 42px;
            margin-bottom: 10px;
            filter: drop-shadow(0 0 14px rgba(0, 229, 255, .6))
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(26px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        @media(max-width:1100px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr))
            }

            .hero-panel {
                grid-template-columns: 1fr
            }

            .hero-orb {
                display: none
            }
        }

        @media(max-width:900px) {
            .main {
                margin-right: 0
            }

            .content {
                padding: 18px
            }
        }

        @media(max-width:640px) {

            .page-header-row,
            .card-hd {
                flex-direction: column;
                align-items: stretch
            }

            .stats-grid {
                grid-template-columns: 1fr
            }

            .display-screen {
                min-height: 118px
            }

            .welcome-msg {
                font-size: .92rem;
                padding: 0 30px
            }

            .btn-primary,
            .search-inp {
                width: 100%
            }
        }
    </style>
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')

        <div class="main">
            @include('admin.includes.header', ['title' => 'الوكلاء'])

            <div class="content">
                <div class="hero-panel">
                    <div class="display-screen">
                        <div class="story-slider">
                            <span class="welcome-msg">إدارة وكلاء Ozman بطريقة واضحة وسريعة</span>
                            <span class="welcome-msg">{{ $agentsCount }} وكيل داخل النظام، مع {{ $coveredCitiesCount }} مدينة مغطاة</span>
                            <span class="welcome-msg">تابع حالة الوكلاء ومعلومات التواصل من مكان واحد</span>
                        </div>
                    </div>
                    <div class="hero-orb">
                        <i class="ti ti-user-star" aria-hidden="true"></i>
                        <span>الوكلاء</span>
                    </div>
                </div>

                <div class="page-header-row">
                    <div>
                        <h1>الوكلاء</h1>
                        <p>إدارة وكلاء المبيعات ومعلومات التواصل والمدن المغطاة.</p>
                    </div>
                    <a href="{{ route('agents.create') }}" class="btn-primary">
                        <i class="ti ti-plus" aria-hidden="true"></i>
                        وكيل جديد
                    </a>
                </div>

                @if(session('status'))
                    <div class="status-alert">{{ session('status') }}</div>
                @endif

                <div class="stats-grid">
                    <div class="stat-card" style="--accent: var(--primary-color)">
                        <div class="stat-label">إجمالي الوكلاء</div>
                        <div class="stat-val">{{ $agentsCount }}</div>
                        <i class="ti ti-user-check stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent: var(--green)">
                        <div class="stat-label">نشط</div>
                        <div class="stat-val">{{ $activeAgentsCount }}</div>
                        <i class="ti ti-circle-check stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent: var(--danger)">
                        <div class="stat-label">غير نشط</div>
                        <div class="stat-val">{{ $inactiveAgentsCount }}</div>
                        <i class="ti ti-circle-x stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent: var(--accent-color)">
                        <div class="stat-label">المدن المغطاة</div>
                        <div class="stat-val">{{ $coveredCitiesCount }}</div>
                        <i class="ti ti-map-pin stat-icon" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="card">
                    <div class="card-hd">
                        <h3>قائمة الوكلاء</h3>
                        <div class="input-wrap">
                            <i class="ti ti-search search-icon" aria-hidden="true"></i>
                            <input class="search-inp" id="agentSearch" placeholder="بحث...">
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table id="agentsTable">
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>الهاتف</th>
                                    <th>واتساب</th>
                                    <th>البريد</th>
                                    <th>المدينة</th>
                                    <th>العنوان</th>
                                    <th>الحالة</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($agentsList as $agent)
                                    <tr>
                                        <td>
                                            <div class="agent-cell">
                                                <div class="agent-avatar">
                                                    {{ strtoupper(mb_substr(data_get($agent, 'name', '-'), 0, 1)) }}
                                                </div>
                                                <span class="agent-name">{{ data_get($agent, 'name', '-') }}</span>
                                            </div>
                                        </td>
                                        <td dir="ltr">{{ data_get($agent, 'phone', '-') }}</td>
                                        <td dir="ltr">{{ data_get($agent, 'whatsapp', '-') }}</td>
                                        <td>{{ data_get($agent, 'email', '-') }}</td>
                                        <td>{{ data_get($agent, 'shop.city', '-') }}</td>
                                        <td>{{ data_get($agent, 'address', '-') }}</td>
                                        <td>
                                            <span class="tag {{ data_get($agent, 'status_class', 'tag-g') }}">
                                                {{ data_get($agent, 'status_label', '-') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="agent-actions">
                                                <a href="{{ route('agents.show', $agent) }}" class="action-btn" aria-label="عرض">
                                                    <i class="ti ti-eye" aria-hidden="true"></i>
                                                </a>
                                                <a href="{{ route('agents.edit', $agent) }}" class="action-btn" aria-label="تعديل">
                                                    <i class="ti ti-edit" aria-hidden="true"></i>
                                                </a>
                                                <form action="{{ route('agents.destroy', $agent) }}" method="POST" onsubmit="return confirm('هل تريد حذف هذا الوكيل؟')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="action-btn" aria-label="حذف">
                                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            <button class="action-btn" style="margin-left:6px" aria-label="تعديل">
                                                <i class="ti ti-edit" aria-hidden="true"></i>
                                            </button>
                                            <button class="action-btn" aria-label="حذف">
                                                <i class="ti ti-trash" aria-hidden="true"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="empty-state">
                                            <i class="ti ti-user-off" aria-hidden="true"></i>
                                            لا يوجد وكلاء حالياً.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.getElementById('agentSearch').addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#agentsTable tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    </script>
</body>

</html>
