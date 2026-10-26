@php
    $pushGiftCopy = match ($locale) {
        'en' => ['enable' => 'Enable notifications to unlock your welcome wheel', 'pending' => 'Enable and save notifications first to unlock the wheel.', 'skip' => 'Browse and order without the gift'],
        'he' => ['enable' => 'הפעלת התראות לפתיחת גלגל המתנה', 'pending' => 'יש להפעיל התראות ולשמור אותן תחילה.', 'skip' => 'המשך לגלוש ולהזמין ללא המתנה'],
        default => ['enable' => 'فعّل الإشعارات وافتح عجلة هديتك', 'pending' => 'فعّل الإشعارات أولًا لفتح عجلة الهدية.', 'skip' => 'تصفح واطلب بدون الهدية'],
    };
@endphp
<style>
#notificationGiftEnable{display:block;max-width:100%;margin:12px auto;padding:10px 16px;border:1px solid #087e8b;border-radius:20px;background:#092027;color:#aeeef4;font:700 12px Cairo,Arial,sans-serif;cursor:pointer}
#notificationGiftEnable[hidden]{display:none}
#notificationGiftEnable:focus-visible{outline:2px solid #13d9ed;outline-offset:3px}
</style>
<button id="notificationGiftEnable" type="button" class="bankai-profile-edit">{{ $pushGiftCopy['enable'] }}</button>
<script>
window.OZMAN_NOTIFICATION_REWARD = @json(['shopId' => $shop->id, 'storageKey' => 'ozman.restaurant.'.$shop->id.'.customer.v'.($shop->customer_registration_version ?? 1), 'pending' => $pushGiftCopy['pending']]);
</script>
<script>{!! file_get_contents(public_path('restaurant-notification-reward.js')) !!}</script>
