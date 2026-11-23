<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الموظفون - Ozman</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.employees.index')
</head>
<body>
    @include('admin.includes.sidebar')
    <main class="main">
        @include('admin.includes.header', ['title' => 'الموظفون'])
        <div class="content">
            @if(session('status'))
                <div class="alert">{{ session('status') }}</div>
            @endif

            <section class="hero">
                <div>
                    <h1>إدارة الموظفين</h1>
                    <p>أنشئ موظفين للوحة التحكم وحدد لكل موظف الصلاحيات المناسبة.</p>
                </div>
                <a class="btn btn-primary" href="{{ route('employees.create') }}">
                    <i class="ti ti-plus"></i>
                    إضافة موظف
                </a>
            </section>

            <section class="stats">
                <div class="panel stat"><span>إجمالي الموظفين</span><strong>{{ $employeesCount }}</strong></div>
                <div class="panel stat"><span>الموظفون النشطون</span><strong>{{ $activeEmployeesCount }}</strong></div>
                <div class="panel stat"><span>غير النشطين</span><strong>{{ max($employeesCount - $activeEmployeesCount, 0) }}</strong></div>
            </section>

            <form class="panel toolbar" method="GET" action="{{ route('employees') }}">
                <input class="search" type="search" name="search" value="{{ request('search') }}" placeholder="بحث بالاسم أو الإيميل أو الهاتف...">
                <div>
                    <button class="btn btn-blue" type="submit"><i class="ti ti-search"></i> بحث</button>
                    <a class="btn btn-outline" href="{{ route('employees') }}">مسح</a>
                </div>
            </form>

            <section class="panel table-panel">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الموظف</th>
                            <th>الإيميل</th>
                            <th>الحالة</th>
                            <th>الصلاحيات</th>
                            <th>تاريخ الإنشاء</th>
                            <th>التحكم</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $employee)
                            <tr>
                                <td>{{ $employee->id }}</td>
                                <td>
                                    <div class="employee-name">
                                        <span class="avatar">{{ mb_substr($employee->name, 0, 1) }}</span>
                                        <span>{{ $employee->name }}<br><span class="muted">{{ $employee->phone ?: 'بدون هاتف' }}</span></span>
                                    </div>
                                </td>
                                <td dir="ltr">{{ $employee->email }}</td>
                                <td><span class="tag {{ $employee->is_active ? 'tag-on' : 'tag-off' }}">{{ $employee->is_active ? 'نشط' : 'متوقف' }}</span></td>
                                <td>{{ $employee->employee_permissions_count }} صلاحية</td>
                                <td>{{ $employee->created_at?->format('Y-m-d') }}</td>
                                <td>
                                    <div class="actions">
                                        <a class="btn btn-yellow" href="{{ route('employees.permissions.edit', $employee) }}"><i class="ti ti-lock"></i> صلاحيات</a>
                                        <a class="btn btn-blue" href="{{ route('employees.edit', $employee) }}"><i class="ti ti-edit"></i> تعديل</a>
                                        <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('حذف الموظف؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-red" type="submit"><i class="ti ti-trash"></i> حذف</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7">لا يوجد موظفون بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="pagination">{{ $employees->links() }}</div>
            </section>
        </div>
    </main>
</body>
</html>
