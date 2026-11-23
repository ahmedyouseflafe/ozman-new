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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.agent.agents')
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
                                                @if(data_get($agent, 'user_id'))
                                                    <a href="{{ route('agents.permissions.edit', $agent) }}" class="action-btn" aria-label="صلاحيات">
                                                        <i class="ti ti-lock" aria-hidden="true"></i>
                                                    </a>
                                                @endif
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
