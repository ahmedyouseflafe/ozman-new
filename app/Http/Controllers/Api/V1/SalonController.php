<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SalonAppointmentController as SiteBooking;
use App\Models\{PushDevice, SalonAppointment, Shop, User};
use App\Services\{FirebaseMessagingService, WebPushService};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Validation\Rule;

/** Salon (cosmetics) bookings in the app: the customer books, the owner confirms. Same rules as the website. */
class SalonController extends Controller
{
    public function form(Shop $shop): JsonResponse
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'cosmetics', 404);

        return response()->json([
            'shop' => Presenter::shop($shop),
            'services' => SiteBooking::SERVICES,
            'times' => collect(SiteBooking::TIMES)->map(fn ($time) => ['value' => $time, 'label' => $this->timeLabel($time)]),
            'whatsapp' => preg_replace('/\D+/', '', (string) ($shop->whatsapp ?: $shop->phone)) ?: null,
        ]);
    }

    public function availability(Request $request, Shop $shop, SiteBooking $site): JsonResponse
    {
        return $site->availability($request, $shop);
    }

    public function store(Request $request, Shop $shop, SiteBooking $site, FirebaseMessagingService $firebase): JsonResponse
    {
        $response = $site->store($request, $shop);
        if ($response->getStatusCode() === 201) {
            $appointment = SalonAppointment::find($response->getData(true)['appointment_id']);
            if ($appointment) {
                $this->tellTheSalon($shop, $appointment, $firebase);
            }
        }

        return $response;
    }

    public function mine(Request $request): JsonResponse
    {
        $appointments = SalonAppointment::with('shop')->where('user_id', $request->user()->id)
            ->orderByDesc('appointment_at')->limit(100)->get();

        return response()->json(['data' => $appointments->map(fn ($appointment) => $this->present($appointment, false))]);
    }

    // ── Owner ──────────────────────────────────────────────────────────────

    public function index(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeShop($request->user(), $shop);
        $data = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(SalonAppointment::statusLabels()))],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'when' => ['nullable', Rule::in(['upcoming', 'past', 'all'])],
        ]);
        $when = $data['when'] ?? 'upcoming';
        $appointments = $shop->salonAppointments()
            ->when($data['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($data['date'] ?? null, fn ($q, $date) => $q->whereDate('appointment_at', $date))
            ->when(! isset($data['date']) && $when === 'upcoming', fn ($q) => $q->where('appointment_at', '>=', today()))
            ->when(! isset($data['date']) && $when === 'past', fn ($q) => $q->where('appointment_at', '<', today()))
            ->when($when === 'past', fn ($q) => $q->orderByDesc('appointment_at'), fn ($q) => $q->orderBy('appointment_at'))
            ->limit(200)->get();
        $active = [SalonAppointment::STATUS_NEW, SalonAppointment::STATUS_CONFIRMED];

        return response()->json([
            'data' => $appointments->map(fn ($appointment) => $this->present($appointment, true)),
            'statuses' => collect(SalonAppointment::statusLabels())->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'stats' => [
                'today' => $shop->salonAppointments()->whereDate('appointment_at', today())->whereIn('status', $active)->count(),
                'new' => $shop->salonAppointments()->where('status', SalonAppointment::STATUS_NEW)->count(),
                'confirmed' => $shop->salonAppointments()->where('status', SalonAppointment::STATUS_CONFIRMED)->where('appointment_at', '>=', now())->count(),
            ],
        ], 200, ['Cache-Control' => 'no-store']);
    }

    public function status(Request $request, SalonAppointment $appointment, SiteBooking $site, FirebaseMessagingService $firebase): JsonResponse
    {
        $shop = $appointment->shop;
        $this->authorizeShop($request->user(), $shop);
        $before = $appointment->status;
        $site->status($request, $shop, $appointment);
        $appointment->refresh();
        if ($appointment->status === $before && $request->input('status') !== $before) {
            return response()->json(['message' => 'لا يمكن إعادة هذا الحجز؛ الموعد أصبح محجوزًا لعميل آخر.'], 422);
        }
        if ($appointment->status !== $before) {
            $this->tellTheCustomer($appointment, $firebase);
        }

        return response()->json(['appointment' => $this->present($appointment->load('shop'), true), 'message' => 'تم تحديث حالة الحجز.']);
    }

    private function present(SalonAppointment $appointment, bool $owner): array
    {
        return [
            'id' => $appointment->id,
            'service' => $appointment->service,
            'at' => $appointment->appointment_at->format('Y-m-d H:i'),
            'date' => $appointment->appointment_at->format('Y-m-d'),
            'time' => $this->timeLabel($appointment->appointment_at->format('H:i')),
            'status' => $appointment->status,
            'status_label' => SalonAppointment::statusLabels()[$appointment->status] ?? $appointment->status,
            'notes' => $appointment->notes,
            'past' => $appointment->appointment_at->isPast(),
            'shop' => $appointment->shop ? ['id' => $appointment->shop->id, 'name' => $appointment->shop->name,
                'phone' => $appointment->shop->whatsapp ?: $appointment->shop->phone] : null,
        ] + ($owner ? ['customer_name' => $appointment->customer_name, 'customer_phone' => $appointment->customer_phone, 'from_app' => $appointment->user_id !== null] : []);
    }

    private function timeLabel(string $time): string
    {
        $hour = (int) substr($time, 0, 2);
        $part = $hour < 12 ? 'صباحًا' : ($hour === 12 ? 'ظهرًا' : 'مساءً');

        return ($hour > 12 ? $hour - 12 : $hour).':'.substr($time, 3, 2).' '.$part;
    }

    private function authorizeShop(User $user, Shop $shop): void
    {
        abort_unless($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true), 403);
        abort_unless($shop->catalog_type === 'cosmetics', 404);
    }

    private function tellTheSalon(Shop $shop, SalonAppointment $appointment, FirebaseMessagingService $firebase): void
    {
        $title = 'حجز جديد · '.$shop->name;
        $body = "{$appointment->customer_name}: {$appointment->service} — ".$appointment->appointment_at->format('Y-m-d').' '.$this->timeLabel($appointment->appointment_at->format('H:i'));
        $url = route('salon-appointments.index', $shop);
        $data = ['type' => 'salon_booking', 'shop_id' => $shop->id, 'appointment_id' => $appointment->id];
        try {
            $tokens = PushDevice::query()->where('user_id', $shop->user_id)->pluck('token');
            if ($shop->user_id && $tokens->isNotEmpty()) {
                $firebase->sendToTokens($tokens, $title, $body, $url, $data);
            }
            app(WebPushService::class)->sendToShop($shop, $title, $body, $url, $data);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function tellTheCustomer(SalonAppointment $appointment, FirebaseMessagingService $firebase): void
    {
        if (! $appointment->user_id) {
            return;
        }
        $message = match ($appointment->status) {
            SalonAppointment::STATUS_CONFIRMED => 'تأكد موعدك ✅',
            SalonAppointment::STATUS_CANCELLED => 'انلغى موعدك',
            SalonAppointment::STATUS_COMPLETED => 'شكراً لزيارتك 💅',
            default => 'تحديث على موعدك',
        };
        try {
            $tokens = PushDevice::query()->where('user_id', $appointment->user_id)->pluck('token');
            if ($tokens->isNotEmpty()) {
                $firebase->sendToTokens($tokens, $message.' · '.$appointment->shop->name,
                    $appointment->service.' — '.$appointment->appointment_at->format('Y-m-d').' '.$this->timeLabel($appointment->appointment_at->format('H:i')),
                    route('cosmetics.booking', $appointment->shop), ['type' => 'salon_appointment', 'appointment_id' => $appointment->id]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
