<?php

namespace App\View\Composers;

use App\Models\AdminNotification;
use Illuminate\View\View;

/** The admin header's notification bell: the latest 30 notifications and the unread count (super admins only). */
class AdminHeaderComposer
{
    public function compose(View $view): void
    {
        $notifications = collect();
        $unread = 0;
        if (auth()->user()?->isSuperAdmin()) {
            $notifications = AdminNotification::query()->with(['shop', 'user'])->latest()->limit(30)->get();
            $unread = AdminNotification::query()->whereNull('read_at')->count();
        }

        $view->with(['adminNotifications' => $notifications, 'adminUnreadNotificationsCount' => $unread]);
    }
}
