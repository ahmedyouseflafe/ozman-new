<?php
namespace App\Http\Controllers;

use App\Models\AdminPushSubscription;
use App\Services\{AdminActivityPush, WebPushService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminPushController extends Controller
{
    public function key(WebPushService $push)
    {
        abort_unless(app(AdminActivityPush::class)->ready(), 503, 'يلزم تحديث قاعدة البيانات أولًا.');
        return response()->json(['key' => $push->publicKey()]);
    }

    private function endpoint(Request $request): string
    {
        $endpoint = $request->validate(['endpoint' => ['required', 'string', 'max:4096']])['endpoint'];
        $parts = parse_url($endpoint);
        $host = strtolower($parts['host'] ?? '');
        $approved = $host === 'fcm.googleapis.com' || $host === 'updates.push.services.mozilla.com'
            || str_ends_with($host, '.push.services.mozilla.com') || $host === 'web.push.apple.com'
            || str_ends_with($host, '.push.apple.com') || str_ends_with($host, '.notify.windows.com');
        if (! $approved || ($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['fragment']) || (isset($parts['port']) && $parts['port'] !== 443)) {
            throw ValidationException::withMessages(['endpoint' => 'عنوان خدمة إشعارات الجهاز غير مدعوم.']);
        }
        return $endpoint;
    }

    public function store(Request $request)
    {
        abort_unless(app(AdminActivityPush::class)->ready(), 503);
        $endpoint = $this->endpoint($request);
        $data = $request->validate(['keys.p256dh' => ['required', 'string', 'max:100'],
            'keys.auth' => ['required', 'string', 'max:40'], 'device_name' => ['nullable', 'string', 'max:100']]);
        foreach (['p256dh' => 65, 'auth' => 16] as $key => $length) {
            $value = $data['keys'][$key];
            $decoded = base64_decode(strtr($value, '-_', '+/'), true);
            if (! preg_match('/^[A-Za-z0-9_-]+={0,2}$/D', $value) || $decoded === false || strlen($decoded) !== $length
                || ($key === 'p256dh' && $decoded[0] !== "\x04")) {
                throw ValidationException::withMessages(['keys.'.$key => 'مفتاح اشتراك الجهاز غير صالح.']);
            }
        }
        $hash = hash('sha256', $endpoint);
        // A browser subscription belongs to one account, and cannot be taken over by another account.
        abort_if(AdminPushSubscription::where('endpoint_hash', $hash)->where('user_id', '!=', $request->user()->id)->exists(), 409);
        AdminPushSubscription::updateOrCreate(['endpoint_hash' => $hash], [
            'user_id' => $request->user()->id, 'endpoint' => $endpoint, 'public_key' => $data['keys']['p256dh'],
            'auth_token' => $data['keys']['auth'], 'content_encoding' => 'aes128gcm',
            'device_name' => $data['device_name'] ?? 'جهاز الإدارة', 'last_seen_at' => now(),
        ]);
        $request->session()->put('admin_web_push_endpoint_hash', $hash);
        return response()->json(['registered' => true])->cookie('admin_push_device', $hash, 60 * 24 * 365,
            '/', null, $request->isSecure(), true, false, 'lax');
    }

    private function device(Request $request): ?AdminPushSubscription
    {
        abort_unless(app(AdminActivityPush::class)->ready(), 503);
        $endpoint = $this->endpoint($request);
        return AdminPushSubscription::where('user_id', $request->user()->id)->where('endpoint_hash', hash('sha256', $endpoint))->first();
    }

    public function status(Request $request)
    {
        $device = $this->device($request);
        if (! $device) return response()->json(['registered' => false]);
        $query = DB::table('admin_push_deliveries')->where('subscription_id', $device->id);
        return response()->json(['registered' => true,
            'pending' => (clone $query)->whereNull('sent_at')->whereNull('failed_at')->count(),
            'failed' => (clone $query)->whereNotNull('failed_at')->count(),
            'last_accepted_at' => (clone $query)->max('sent_at')]);
    }

    public function destroy(Request $request)
    {
        $this->device($request)?->delete();
        $request->session()->forget('admin_web_push_endpoint_hash');
        return response()->json(['registered' => false])->withoutCookie('admin_push_device');
    }

    public function test(Request $request, WebPushService $push)
    {
        $device = $this->device($request);
        abort_unless($device, 422, 'فعّل إشعارات هذا الجهاز أولًا.');
        try {
            $result = $push->sendAdminDevice($device, ['title' => 'إدارة أوزمان',
                'body' => 'وصل إشعار التجربة. إشعارات نشاط المحلات تظهر هنا حتى بعد إغلاق اللوحة.',
                'url' => route('admin.analytics.index'), 'tag' => 'ozman-admin-test-'.\Illuminate\Support\Str::uuid()]);
        } catch (\Throwable $e) { $result = 'retry'; }
        if ($result === 'expired') $device->delete();
        return response()->json(['accepted' => $result === 'sent', 'expired' => $result === 'expired',
            'message' => $result === 'sent' ? 'قبلت خدمة الجهاز الإشعار؛ تأكد من ظهوره على جوالك.' : 'تعذّر إرسال التجربة. حاول مجددًا أو أعد تفعيل إشعارات الجهاز.'],
            $result === 'sent' ? 200 : 503);
    }
}
