<?php

return [
    'groups' => [
        'dashboard' => [
            'label' => 'لوحة التحكم',
            'permissions' => [
                'dashboard.view' => [
                    'label' => 'عرض لوحة التحكم',
                    'routes' => ['dashboard'],
                ],
                'dashboard.main' => [
                    'label' => 'عرض لوحة التحكم الرئيسية',
                    'routes' => ['dashboard.main'],
                ],
            ],
        ],
        'shops' => [
            'label' => 'المتاجر',
            'permissions' => [
                'shops.view' => ['label' => 'استعراض المتاجر', 'routes' => ['shops', 'shops.show', 'shops.ozman']],
                'shops.create' => ['label' => 'إضافة متجر', 'routes' => ['shops.create', 'shops.store']],
                'shops.edit' => ['label' => 'تعديل متجر', 'routes' => ['shops.edit', 'shops.update']],
                'shops.delete' => ['label' => 'حذف متجر', 'routes' => ['shops.destroy']],
            ],
        ],
        'products' => [
            'label' => 'المنتجات',
            'permissions' => [
                'products.view' => ['label' => 'استعراض المنتجات', 'routes' => ['products', 'products.show']],
                'products.preview' => ['label' => 'معاينة المتجر', 'routes' => ['products.preview']],
                'products.create' => ['label' => 'إضافة منتج', 'routes' => ['products.create', 'products.store']],
                'products.edit' => ['label' => 'تعديل منتج', 'routes' => ['products.edit', 'products.update']],
                'products.delete' => ['label' => 'حذف منتج', 'routes' => ['products.destroy']],
            ],
        ],
        'categories' => [
            'label' => 'الفئات',
            'permissions' => [
                'categories.view' => ['label' => 'استعراض الفئات', 'routes' => ['categories', 'categories.show']],
                'categories.create' => ['label' => 'إضافة فئة', 'routes' => ['categories.create', 'categories.store']],
                'categories.edit' => ['label' => 'تعديل فئة', 'routes' => ['categories.edit', 'categories.update']],
                'categories.delete' => ['label' => 'حذف فئة', 'routes' => ['categories.destroy']],
            ],
        ],
        'ads' => [
            'label' => 'الإعلانات',
            'permissions' => [
                'ads.view' => ['label' => 'استعراض الإعلانات', 'routes' => ['ads', 'ads.show']],
                'ads.create' => ['label' => 'إضافة إعلان', 'routes' => ['ads.create', 'ads.store']],
                'ads.edit' => ['label' => 'تعديل إعلان', 'routes' => ['ads.edit', 'ads.update']],
                'ads.delete' => ['label' => 'حذف إعلان', 'routes' => ['ads.destroy']],
            ],
        ],
        'screens' => [
            'label' => 'الشاشات',
            'permissions' => [
                'screens.view' => ['label' => 'استعراض الشاشات', 'routes' => ['screens', 'screens.show']],
                'screens.create' => ['label' => 'إضافة شاشة', 'routes' => ['screens.create', 'screens.store']],
                'screens.edit' => ['label' => 'تعديل شاشة', 'routes' => ['screens.edit', 'screens.update']],
                'screens.delete' => ['label' => 'حذف شاشة', 'routes' => ['screens.destroy']],
            ],
        ],
        'people' => [
            'label' => 'الوكلاء والموزعون',
            'permissions' => [
                'agents.view' => ['label' => 'استعراض الوكلاء', 'routes' => ['agents', 'agents.show']],
                'agents.create' => ['label' => 'إضافة وكيل', 'routes' => ['agents.create', 'agents.store']],
                'agents.edit' => ['label' => 'تعديل وكيل', 'routes' => ['agents.edit', 'agents.update']],
                'agents.delete' => ['label' => 'حذف وكيل', 'routes' => ['agents.destroy']],
                'distributors.view' => ['label' => 'استعراض الموزعين', 'routes' => ['distributors', 'distributors.show']],
                'distributors.create' => ['label' => 'إضافة موزع', 'routes' => ['distributors.create', 'distributors.store']],
                'distributors.edit' => ['label' => 'تعديل موزع', 'routes' => ['distributors.edit', 'distributors.update']],
                'distributors.delete' => ['label' => 'حذف موزع', 'routes' => ['distributors.destroy']],
            ],
        ],
        'orders' => [
            'label' => 'الطلبات والتسجيلات',
            'permissions' => [
                'visitor_registrations.view' => ['label' => 'استعراض تسجيلات الزوار', 'routes' => ['visitor-registrations.index']],
                'front_orders.view' => ['label' => 'استعراض طلبات الواجهة', 'routes' => ['front-orders.index']],
                'front_orders.manage' => ['label' => 'إدارة جوائز الطلبات', 'routes' => ['front-orders.reward', 'front-orders.spinReward']],
            ],
        ],
        'reward_wheels' => [
            'label' => 'عجلات الربح',
            'permissions' => [
                'reward_wheels.customer_signup.view' => ['label' => 'عرض عجلات الربح', 'routes' => ['reward-wheels.customer-signup.edit']],
                'reward_wheels.customer_signup.edit' => ['label' => 'تعديل عجلات الربح', 'routes' => ['reward-wheels.customer-signup.update']],
                'reward_wheels.purchase.view' => ['label' => 'عرض عجلات الشراء', 'routes' => ['reward-wheels.purchase.index', 'reward-wheels.purchase.edit']],
                'reward_wheels.purchase.manage' => ['label' => 'إدارة عجلات الشراء', 'routes' => ['reward-wheels.purchase.store', 'reward-wheels.purchase.update', 'reward-wheels.purchase.destroy']],
                'reward_wheels.marketer.view' => ['label' => 'عرض عجلة أسئلة المسوقة', 'routes' => ['reward-wheels.marketer.edit']],
                'reward_wheels.marketer.edit' => ['label' => 'تعديل عجلة أسئلة المسوقة', 'routes' => ['reward-wheels.marketer.update']],
                'reward_wheels.marketer.play' => ['label' => 'تشغيل عجلة أسئلة المسوقة', 'routes' => ['reward-wheels.marketer.play', 'reward-wheels.marketer.unlock', 'reward-wheels.marketer.spin', 'reward-wheels.marketer.reset']],
                'reward_wheels.marketer_direct.view' => ['label' => 'عرض عجلة المسوقة المباشرة', 'routes' => ['reward-wheels.marketer.direct.edit']],
                'reward_wheels.marketer_direct.edit' => ['label' => 'تعديل عجلة المسوقة المباشرة', 'routes' => ['reward-wheels.marketer.direct.update']],
                'reward_wheels.marketer_direct.play' => ['label' => 'تشغيل عجلة المسوقة المباشرة', 'routes' => ['reward-wheels.marketer.direct.play', 'reward-wheels.marketer.direct.spin']],
            ],
        ],
        'employees' => [
            'label' => 'الموظفون والصلاحيات',
            'permissions' => [
                'employees.view' => ['label' => 'استعراض الموظفين', 'routes' => ['employees', 'employees.show']],
                'employees.create' => ['label' => 'إضافة موظف', 'routes' => ['employees.create', 'employees.store']],
                'employees.edit' => ['label' => 'تعديل موظف', 'routes' => ['employees.edit', 'employees.update']],
                'employees.delete' => ['label' => 'حذف موظف', 'routes' => ['employees.destroy']],
                'employees.permissions' => ['label' => 'تعديل صلاحيات الموظفين', 'routes' => ['employees.permissions.edit', 'employees.permissions.update']],
            ],
        ],
        'users' => [
            'label' => 'المستخدمون',
            'permissions' => [
                'users.view' => ['label' => 'عرض المستخدمين', 'routes' => ['users']],
            ],
        ],
        'settings' => [
            'label' => 'الإعدادات',
            'permissions' => [
                'settings.view' => ['label' => 'عرض الإعدادات', 'routes' => ['settings']],
                'settings.profile' => ['label' => 'تعديل الملف الشخصي', 'routes' => ['settings.profile.update']],
                'settings.password' => ['label' => 'تغيير كلمة المرور', 'routes' => ['settings.password.update']],
                'settings.system' => ['label' => 'تعديل إعدادات النظام', 'routes' => ['settings.system.update']],
                'settings.notifications' => ['label' => 'تعديل إعدادات الإشعارات', 'routes' => ['settings.notifications.update']],
                'notifications.manage' => ['label' => 'إدارة الإشعارات', 'routes' => ['admin.notifications.readAll']],
            ],
        ],
    ],
];
