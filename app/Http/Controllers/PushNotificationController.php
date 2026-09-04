<?php

namespace App\Http\Controllers;

use App\Models\PushDevice;
use App\Models\PushNotification;
use App\Services\FirebaseMessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class PushNotificationController extends Controller
{
    public function index(Request $request, FirebaseMessagingService $firebase): View
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        return view('admin.push_notifications.index', [
            'devicesCount' => PushDevice::query()->count(),
            'firebaseConfigured' => $firebase->isConfigured(),
            'notifications' => PushNotification::query()
                ->with('sender:id,name')
                ->latest()
                ->limit(30)
                ->get(),
        ]);
    }

    public function send(Request $request, FirebaseMessagingService $firebase): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:500'],
            'url' => ['nullable', 'url', 'starts_with:https://ozman.online', 'max:500'],
        ]);

        $notification = PushNotification::query()->create([
            'sent_by' => $request->user()->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'url' => $data['url'] ?: 'https://ozman.online/',
        ]);

        try {
            $firebase->sendToAll($notification->title, $notification->body, $notification->url);
            $notification->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (Throwable $exception) {
            $notification->update([
                'status' => 'failed',
                'error' => substr($exception->getMessage(), 0, 2000),
            ]);
            report($exception);

            return back()->withInput()->withErrors(['firebase' => $exception->getMessage()]);
        }

        return back()->with('success', 'تم إرسال الإشعار بنجاح.');
    }
}
