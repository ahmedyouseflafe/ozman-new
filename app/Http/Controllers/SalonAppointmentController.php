<?php

namespace App\Http\Controllers;

use App\Models\SalonAppointment;
use App\Models\Shop;
use App\Rules\ValidPhoneNumber;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SalonAppointmentController extends Controller
{
    private const TIMES = ['10:00', '12:00', '14:00', '16:00', '18:00'];

    public function availability(Request $request, Shop $shop): JsonResponse
    {
        $this->ensureCosmeticsShop($shop);
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today']]);

        return response()->json([
            'booked_times' => $shop->salonAppointments()
                ->whereDate('appointment_at', $data['date'])
                ->whereIn('status', [SalonAppointment::STATUS_NEW, SalonAppointment::STATUS_CONFIRMED])
                ->orderBy('appointment_at')
                ->get()
                ->map(fn (SalonAppointment $appointment) => $appointment->appointment_at->format('H:i'))
                ->values(),
        ]);
    }

    public function store(Request $request, Shop $shop): JsonResponse
    {
        $this->ensureCosmeticsShop($shop);
        $data = $request->validate([
            'service' => ['required', 'string', 'max:120'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time' => ['required', Rule::in(self::TIMES)],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:60', new ValidPhoneNumber()],
            'notes' => ['nullable', 'string', 'max:1500'],
        ]);
        $appointmentAt = Carbon::createFromFormat('Y-m-d H:i', $data['date'].' '.$data['time'], config('app.timezone'));

        if ($appointmentAt->isPast()) {
            return response()->json(['message' => 'اختر وقتًا قادمًا للحجز.'], 422);
        }

        try {
            $appointment = SalonAppointment::create([
                'shop_id' => $shop->id,
                'service' => $data['service'],
                'appointment_at' => $appointmentAt,
                'slot_key' => $appointmentAt->format('Y-m-d H:i'),
                'customer_name' => $data['name'],
                'customer_phone' => $data['phone'],
                'notes' => $data['notes'] ?? null,
                'status' => SalonAppointment::STATUS_NEW,
                'locale' => app()->getLocale(),
                'whatsapp_opened_at' => now(),
            ]);
        } catch (QueryException) {
            return response()->json(['message' => 'هذا الموعد حُجز للتو. اختَر وقتًا آخر.'], 422);
        }

        return response()->json([
            'ok' => true,
            'appointment_id' => $appointment->id,
            'appointment_at' => $appointment->appointment_at->format('Y-m-d H:i'),
            'message' => 'تم تسجيل طلب الحجز. سيتواصل معك الصالون لتأكيده.',
        ], 201);
    }

    public function index(Request $request, Shop $shop): View
    {
        $this->authorizeShop($request, $shop);
        $this->ensureCosmeticsShop($shop);
        $status = $request->query('status');
        $date = $request->query('date');

        $appointments = $shop->salonAppointments()
            ->when(array_key_exists((string) $status, SalonAppointment::statusLabels()), fn ($query) => $query->where('status', $status))
            ->when($date, fn ($query) => $query->whereDate('appointment_at', $date))
            ->orderBy('appointment_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.salon_appointments.index', [
            'shop' => $shop,
            'appointments' => $appointments,
            'statusLabels' => SalonAppointment::statusLabels(),
            'selectedStatus' => $status,
            'selectedDate' => $date,
            'stats' => [
                'today' => $shop->salonAppointments()->whereDate('appointment_at', today())->whereIn('status', [SalonAppointment::STATUS_NEW, SalonAppointment::STATUS_CONFIRMED])->count(),
                'new' => $shop->salonAppointments()->where('status', SalonAppointment::STATUS_NEW)->count(),
                'confirmed' => $shop->salonAppointments()->where('status', SalonAppointment::STATUS_CONFIRMED)->count(),
            ],
        ]);
    }

    public function status(Request $request, Shop $shop, SalonAppointment $appointment): RedirectResponse
    {
        $this->authorizeShop($request, $shop);
        $this->ensureCosmeticsShop($shop);
        abort_unless($appointment->shop_id === $shop->id, 404);
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(SalonAppointment::statusLabels()))]]);

        try {
            $appointment->update([
                'status' => $data['status'],
                'slot_key' => $data['status'] === SalonAppointment::STATUS_CANCELLED
                    ? null
                    : $appointment->appointment_at->format('Y-m-d H:i'),
            ]);
        } catch (QueryException) {
            return back()->with('status', 'لا يمكن إعادة هذا الحجز؛ الموعد أصبح محجوزًا لعميل آخر.');
        }

        return back()->with('status', 'تم تحديث حالة الحجز.');
    }

    private function ensureCosmeticsShop(Shop $shop): void
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'cosmetics', 404);
    }

    private function authorizeShop(Request $request, Shop $shop): void
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true)), 403);
    }
}
