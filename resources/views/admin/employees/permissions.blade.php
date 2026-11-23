<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle ?? 'صلاحيات المستخدم' }} - Ozman</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.employees.permissions')
</head>
<body>
    @php
        $headerTitle = $headerTitle ?? 'صلاحيات الموظف';
        $description = $description ?? 'حدد الصفحات والعمليات التي يستطيع المستخدم الوصول إليها.';
        $formAction = $formAction ?? route('employees.permissions.update', $employee);
        $backUrl = $backUrl ?? route('employees');
    @endphp
    @include('admin.includes.sidebar')
    <main class="main">
        @include('admin.includes.header', ['title' => $headerTitle])
        <div class="content">
            <form method="POST" action="{{ $formAction }}">
                @csrf
                @method('PUT')

                <section class="hero">
                    <div>
                        <h1>{{ $headerTitle }}: {{ $employee->name }}</h1>
                        <p>{{ $description }}</p>
                    </div>
                    <a class="btn btn-outline" href="{{ $backUrl }}"><i class="ti ti-arrow-right"></i> رجوع</a>
                </section>

                @if($errors->any())
                    <div class="errors">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                @if($distributorMode ?? false)
                    <section class="hero" style="flex-wrap:wrap;align-items:center">
                        <div><strong>إعداد سريع</strong><p>الاختيار يجهّز الصلاحيات؛ اضغط «حفظ الصلاحيات» لتطبيقه.</p></div>
                        <button type="button" class="btn btn-blue" data-permission-preset="orders">مشاهدة طلباته فقط</button>
                        <button type="button" class="btn btn-outline" data-permission-preset="catalog">طلباته + مشاهدة المنتجات</button>
                        <button type="button" class="btn btn-outline" data-permission-preset="none">بدون صلاحيات</button>
                    </section>
                @endif
                <div class="toolbar">
                    <input class="search" id="permissionSearch" type="search" placeholder="ابحث داخل الصلاحيات...">
                    <span class="selected-summary"><span id="selectedCount">0</span> صلاحية محددة</span>
                    <button class="btn btn-blue" type="button" id="selectAll"><i class="ti ti-check"></i> تحديد الكل</button>
                    <button class="btn btn-outline" type="button" id="clearAll"><i class="ti ti-eraser"></i> إلغاء الكل</button>
                </div>

                <section class="grid" id="permissionsGrid">
                    @php
                        $groupIcons = [
                            'dashboard' => 'ti-layout-dashboard', 'shops' => 'ti-building-store',
                            'catalog' => 'ti-package', 'people' => 'ti-users-group',
                            'orders' => 'ti-receipt', 'reward_wheels' => 'ti-chart-donut',
                            'employees' => 'ti-user-shield', 'users' => 'ti-users',
                            'settings' => 'ti-settings',
                        ];
                    @endphp
                    @foreach($permissionGroups as $groupKey => $group)
                        @if(($distributorMode ?? false) && $groupKey !== 'distributor_basic')
                            <details data-advanced-permissions><summary style="cursor:pointer;padding:14px;border:1px solid #46555d;border-radius:12px">{{ $group['label'] }} — خيارات إضافية</summary>
                        @endif
                        <article class="group" data-permission-group>
                            <div class="group-head">
                                <span class="group-icon"><i class="ti {{ $groupIcons[$groupKey] ?? 'ti-lock' }}"></i></span>
                                <div class="group-heading">
                                    <h2>{{ $group['label'] }}</h2>
                                    <p class="group-description">{{ $group['description'] ?? '' }}</p>
                                </div>
                                <div class="group-tools">
                                    <span class="count"><span data-group-selected>0</span> / {{ count($group['permissions'] ?? []) }}</span>
                                    <button class="group-tool" type="button" data-group-select>تحديد القسم</button>
                                    <button class="group-tool" type="button" data-group-clear>إلغاء القسم</button>
                                </div>
                            </div>
                            <div class="permission-list">
                                @foreach($group['permissions'] ?? [] as $permission => $meta)
                                    <label class="perm" data-permission-item>
                                        <span class="perm-text">
                                            <span class="perm-title">{{ $meta['label'] }}</span>
                                            @if(filled($meta['description'] ?? null))
                                                <span class="perm-description">{{ $meta['description'] }}</span>
                                            @endif
                                        </span>
                                        <input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, $selectedPermissions, true))>
                                    </label>
                                @endforeach
                            </div>
                        </article>
                        @if(($distributorMode ?? false) && $groupKey !== 'distributor_basic')</details>@endif
                    @endforeach
                </section>
                <div class="empty-search" id="emptySearch"><i class="ti ti-search-off"></i> لا توجد صلاحيات مطابقة للبحث.</div>

                <div class="save-bar">
                    <span class="selected-summary">سيتم حفظ <span id="saveSelectedCount">0</span> صلاحية لهذا الحساب</span>
                    <div class="actions">
                        <button class="btn btn-green" type="submit"><i class="ti ti-device-floppy"></i> حفظ الصلاحيات</button>
                        <a class="btn btn-outline" href="{{ $backUrl }}">إلغاء</a>
                    </div>
                </div>
            </form>
        </div>
    </main>
    @viewScript('admin.employees.permissions')
</body>
</html>
