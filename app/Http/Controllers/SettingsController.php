<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private const SETTINGS_PATH = 'ozman_settings.json';

    public function index(): View
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $settings = $this->settings();
        $user = Auth::user();

        return view('admin.settings', [
            'adminName' => $user?->name,
            'adminEmail' => $user?->email,
            'adminPhone' => $user?->phone,
            'systemName' => $settings['system_name'],
            'defaultCurrency' => $settings['currency'],
            'notificationSettings' => $settings['notifications'],
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $user = Auth::user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        $user?->update($data);

        return back()->with('status', 'تم حفظ بيانات الملف الشخصي بنجاح.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Auth::user()?->update([
            'password' => Hash::make($data['password']),
        ]);

        return back()->with('status', 'تم تحديث كلمة المرور بنجاح.');
    }

    public function updateSystem(Request $request): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $data = $request->validate([
            'system_name' => ['required', 'string', 'max:80'],
            'currency' => ['required', 'string', Rule::in(['₪ شيكل', '$ دولار', '€ يورو'])],
        ]);

        $settings = $this->settings();
        $settings['system_name'] = $data['system_name'];
        $settings['currency'] = $data['currency'];
        $this->saveSettings($settings);

        return back()->with('status', 'تم حفظ إعدادات النظام بنجاح.');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $settings = $this->settings();
        $settings['notifications'] = [
            'new_shops' => $request->boolean('new_shops'),
            'out_of_stock' => $request->boolean('out_of_stock'),
            'new_users' => $request->boolean('new_users'),
        ];
        $this->saveSettings($settings);

        return back()->with('status', 'تم حفظ إعدادات الإشعارات بنجاح.');
    }

    private function settings(): array
    {
        $defaults = [
            'system_name' => 'Ozman',
            'currency' => '₪ شيكل',
            'notifications' => [
                'new_shops' => true,
                'out_of_stock' => true,
                'new_users' => false,
            ],
        ];

        if (! Storage::disk('local')->exists(self::SETTINGS_PATH)) {
            return $defaults;
        }

        $stored = json_decode(Storage::disk('local')->get(self::SETTINGS_PATH), true);

        return array_replace_recursive($defaults, is_array($stored) ? $stored : []);
    }

    private function saveSettings(array $settings): void
    {
        Storage::disk('local')->put(
            self::SETTINGS_PATH,
            json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}
