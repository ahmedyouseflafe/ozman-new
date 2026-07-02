<?php

namespace App\Http\Controllers;

use App\Models\RaffleCard;
use App\Models\RaffleEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RaffleCardController extends Controller
{
    private const SETTINGS_PATH = 'ozman_settings.json';

    public function index(Request $request): View
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status', 'all');

        $cards = RaffleCard::query()
            ->when($search !== '', fn($query) => $query->where(function ($searchQuery) use ($search) {
                $searchQuery
                    ->where('card_number', 'like', "%{$search}%")
                    ->orWhere('prize_title', 'like', "%{$search}%");
            }))
            ->when($status === 'used', fn($query) => $query->whereNotNull('used_at'))
            ->when($status === 'available', fn($query) => $query->whereNull('used_at'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $liveEntries = RaffleEntry::query()
            ->where('outcome', RaffleEntry::OUTCOME_LIVE_DRAW)
            ->latest()
            ->paginate(15, ['*'], 'live_page')
            ->withQueryString();

        $settings = $this->settings();

        return view('admin.raffle_cards.index', [
            'cards' => $cards,
            'liveEntries' => $liveEntries,
            'search' => $search,
            'status' => $status,
            'raffleWhatsapp' => $settings['raffle']['whatsapp'] ?? '',
            'totalCards' => RaffleCard::count(),
            'usedCards' => RaffleCard::whereNotNull('used_at')->count(),
            'liveEntriesCount' => RaffleEntry::where('outcome', RaffleEntry::OUTCOME_LIVE_DRAW)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $data = $request->validate([
            'card_number' => ['required', 'digits:5', 'unique:raffle_cards,card_number'],
            'prize_title' => ['required', 'string', 'max:255'],
            'prize_image' => ['nullable', 'image', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('prize_image')) {
            $data['prize_image'] = 'storage/' . $request->file('prize_image')->store('raffle/prizes', 'public');
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $data['created_by'] = Auth::id();

        RaffleCard::create($data);

        return back()->with('status', 'تمت إضافة بطاقة الربح بنجاح.');
    }

    public function update(Request $request, RaffleCard $card): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $data = $request->validate([
            'card_number' => ['required', 'digits:5', Rule::unique('raffle_cards', 'card_number')->ignore($card->id)],
            'prize_title' => ['required', 'string', 'max:255'],
            'prize_image' => ['nullable', 'image', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('prize_image')) {
            $this->deleteUpload($card->prize_image);
            $data['prize_image'] = 'storage/' . $request->file('prize_image')->store('raffle/prizes', 'public');
        }

        $data['is_active'] = $request->boolean('is_active');
        $card->update($data);

        return back()->with('status', 'تم تحديث بطاقة الربح بنجاح.');
    }

    public function destroy(RaffleCard $card): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $this->deleteUpload($card->prize_image);
        $card->delete();

        return back()->with('status', 'تم حذف بطاقة الربح بنجاح.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $data = $request->validate([
            'raffle_whatsapp' => ['nullable', 'string', 'max:40'],
        ]);

        $settings = $this->settings();
        $settings['raffle']['whatsapp'] = $data['raffle_whatsapp'] ?? '';
        $this->saveSettings($settings);

        return back()->with('status', 'تم حفظ رقم واتساب السحب بنجاح.');
    }

    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'card_number' => ['required', 'digits:5'],
            'customer.name' => ['nullable', 'string', 'max:255'],
            'customer.phone' => ['nullable', 'string', 'max:60'],
            'customer.whatsapp' => ['nullable', 'string', 'max:60'],
            'customer.address' => ['nullable', 'string', 'max:1000'],
        ]);

        $cardNumber = $data['card_number'];
        $customer = $data['customer'] ?? [];
        $settings = $this->settings();
        $whatsapp = preg_replace('/\D+/', '', $settings['raffle']['whatsapp'] ?? '');

        $card = RaffleCard::query()
            ->where('card_number', $cardNumber)
            ->where('is_active', true)
            ->first();

        if (! $card) {
            $entry = RaffleEntry::firstOrCreate(
                ['card_number' => $cardNumber],
                [
                    'outcome' => RaffleEntry::OUTCOME_LIVE_DRAW,
                    'customer_name' => $customer['name'] ?? null,
                    'customer_phone' => $customer['phone'] ?? null,
                    'customer_whatsapp' => $customer['whatsapp'] ?? null,
                    'customer_payload' => $customer ?: null,
                    'ip_address' => $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                ]
            );

            return response()->json([
                'status' => 'live_draw',
                'already_registered' => ! $entry->wasRecentlyCreated,
                'title' => 'حظاً أوفر في السحب الفوري',
                'message' => $entry->wasRecentlyCreated
                    ? 'بطاقتك دخلت السحب على جوائز البثوث المباشرة.'
                    : 'هذه البطاقة مسجلة مسبقاً في سحب جوائز البثوث المباشرة.',
                'whatsapp' => $whatsapp,
            ]);
        }

        if ($card->used_at) {
            RaffleEntry::firstOrCreate(
                ['card_number' => $cardNumber],
                [
                    'raffle_card_id' => $card->id,
                    'outcome' => RaffleEntry::OUTCOME_USED_WINNER,
                    'customer_name' => $customer['name'] ?? null,
                    'customer_phone' => $customer['phone'] ?? null,
                    'customer_whatsapp' => $customer['whatsapp'] ?? null,
                    'customer_payload' => $customer ?: null,
                    'ip_address' => $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                ]
            );

            return response()->json([
                'status' => 'used',
                'title' => 'تم استخدام بطاقة الربح هذه من قبل',
                'message' => 'إذا عندك أي استفسار تواصل معنا عن طريق واتساب.',
                'whatsapp' => $whatsapp,
            ], 409);
        }

        $card->update([
            'used_at' => now(),
            'used_customer_name' => $customer['name'] ?? null,
            'used_customer_phone' => $customer['phone'] ?? null,
            'used_customer_whatsapp' => $customer['whatsapp'] ?? null,
            'used_customer_payload' => $customer ?: null,
        ]);

        RaffleEntry::updateOrCreate(
            ['card_number' => $cardNumber],
            [
                'raffle_card_id' => $card->id,
                'outcome' => RaffleEntry::OUTCOME_WINNER,
                'customer_name' => $customer['name'] ?? null,
                'customer_phone' => $customer['phone'] ?? null,
                'customer_whatsapp' => $customer['whatsapp'] ?? null,
                'customer_payload' => $customer ?: null,
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]
        );

        return response()->json([
            'status' => 'winner',
            'title' => 'مبروك! ربحت',
            'message' => $card->prize_title,
            'card_number' => $card->card_number,
            'prize_title' => $card->prize_title,
            'prize_image' => $card->prize_image ? asset($card->prize_image) : null,
            'whatsapp' => $whatsapp,
        ]);
    }

    private function settings(): array
    {
        $defaults = [
            'raffle' => [
                'whatsapp' => '',
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

    private function deleteUpload(?string $path): void
    {
        if (! $path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        Storage::disk('public')->delete(str_replace('storage/', '', $path));
    }
}
