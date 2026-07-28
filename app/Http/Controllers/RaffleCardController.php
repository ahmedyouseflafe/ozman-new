<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\RaffleCard;
use App\Models\RaffleEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

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
            'defaultSocialQrLinks' => $this->defaultSocialQrLinks(),
            'totalCards' => RaffleCard::count(),
            'usedCards' => RaffleCard::whereNotNull('used_at')->count(),
            'liveEntriesCount' => RaffleEntry::where('outcome', RaffleEntry::OUTCOME_LIVE_DRAW)->count(),
        ]);
    }

    public function openCard(string $cardNumber): RedirectResponse
    {
        abort_unless(preg_match('/^\d{6}$/', $cardNumber) === 1, 404);

        return redirect()->route('home', ['raffle_card' => $cardNumber]);
    }

    public function printable(Request $request): View
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $data = $request->validate([
            'from_number' => ['required', 'digits:6'],
            'to_number' => ['required', 'digits:6'],
            'cards_per_page' => ['required', 'integer', Rule::in([6, 8, 10])],
            'social_qr_1_url' => ['nullable', 'url', 'max:1000'],
            'social_qr_2_url' => ['nullable', 'url', 'max:1000'],
            'brand_text' => ['nullable', 'string', 'max:120'],
        ]);

        $from = (int) $data['from_number'];
        $to = (int) $data['to_number'];

        if ($from > $to) {
            throw ValidationException::withMessages([
                'to_number' => 'رقم النهاية يجب أن يكون أكبر من أو يساوي رقم البداية.',
            ]);
        }

        $count = $to - $from + 1;
        if (false && $count > 1000) {
            throw ValidationException::withMessages([
                'to_number' => 'للحفاظ على سرعة المتصفح، ولد كل ملف بحد أقصى 1000 بطاقة.',
            ]);
        }

        $writer = new Writer(new ImageRenderer(
            new RendererStyle(260, 1),
            new SvgImageBackEnd()
        ));
        $smallWriter = new Writer(new ImageRenderer(
            new RendererStyle(160, 1),
            new SvgImageBackEnd()
        ));

        $socialQr1 = filled($data['social_qr_1_url'] ?? null)
            ? $this->qrDataUri($smallWriter, $data['social_qr_1_url'])
            : null;
        $socialQr2 = filled($data['social_qr_2_url'] ?? null)
            ? $this->qrDataUri($smallWriter, $data['social_qr_2_url'])
            : null;

        $cards = collect(range($from, $to))
            ->map(function (int $number) use ($writer) {
                $cardNumber = str_pad((string) $number, 6, '0', STR_PAD_LEFT);
                $url = route('front.raffle-card.open', ['cardNumber' => $cardNumber]);

                return [
                    'number' => $cardNumber,
                    'url' => $url,
                    'qr' => $this->qrDataUri($writer, $url),
                ];
            });

        return view('admin.raffle_cards.printable', [
            'cards' => $cards,
            'cardsPerPage' => (int) $data['cards_per_page'],
            'socialQr1' => $socialQr1,
            'socialQr2' => $socialQr2,
            'brandText' => $data['brand_text'] ?: 'Ozman',
            'fromNumber' => $data['from_number'],
            'toNumber' => $data['to_number'],
        ]);
    }

    public function exportWinningCardsPdf(Request $request): Response
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $data = $request->validate([
            'from_number' => ['required', 'digits:6'],
            'to_number' => ['required', 'digits:6'],
        ]);

        if ((int) $data['from_number'] > (int) $data['to_number']) {
            throw ValidationException::withMessages([
                'to_number' => 'رقم النهاية يجب أن يكون أكبر من أو يساوي رقم البداية.',
            ]);
        }

        $cards = RaffleCard::query()
            ->whereBetween('card_number', [$data['from_number'], $data['to_number']])
            ->orderBy('card_number')
            ->get()
            ->map(function (RaffleCard $card) {
                $card->pdf_prize_image = $this->localImageDataUri($card->prize_image);

                return $card;
            });

        $pdf = Pdf::loadView('admin.raffle_cards.winning_cards_pdf', [
            'cards' => $cards,
            'fromNumber' => $data['from_number'],
            'toNumber' => $data['to_number'],
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download(
            "winning-cards-{$data['from_number']}-{$data['to_number']}.pdf"
        );
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $data = $request->validate([
            'card_number' => ['required', 'digits:6', 'unique:raffle_cards,card_number'],
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

    public function storeRandomBulk(Request $request): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $data = $request->validate([
            'from_number' => ['required', 'digits:6'],
            'to_number' => ['required', 'digits:6'],
            'prize_count' => ['required', 'integer', 'min:1', 'max:10000'],
            'prize_title' => ['required', 'string', 'max:255'],
            'prize_image' => ['nullable', 'image', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $from = (int) $data['from_number'];
        $to = (int) $data['to_number'];
        $requestedCount = (int) $data['prize_count'];

        if ($from > $to) {
            throw ValidationException::withMessages([
                'to_number' => 'رقم النهاية يجب أن يكون أكبر من أو يساوي رقم البداية.',
            ]);
        }

        $blockedNumbers = RaffleCard::query()
            ->whereBetween('card_number', [$data['from_number'], $data['to_number']])
            ->pluck('card_number')
            ->merge(
                RaffleEntry::query()
                    ->whereBetween('card_number', [$data['from_number'], $data['to_number']])
                    ->pluck('card_number')
            )
            ->mapWithKeys(fn (string $number) => [(int) $number => true])
            ->all();

        $availableCount = ($to - $from + 1) - count($blockedNumbers);
        if ($requestedCount > $availableCount) {
            throw ValidationException::withMessages([
                'prize_count' => "العدد المطلوب أكبر من الأرقام المتاحة في هذا النطاق. المتاح حاليًا: {$availableCount}.",
            ]);
        }

        $selectedNumbers = [];
        $randomAttempts = 0;
        $maxRandomAttempts = max(100, $requestedCount * 30);

        while (count($selectedNumbers) < $requestedCount && $randomAttempts < $maxRandomAttempts) {
            $candidate = random_int($from, $to);
            $randomAttempts++;

            if (! isset($blockedNumbers[$candidate]) && ! isset($selectedNumbers[$candidate])) {
                $selectedNumbers[$candidate] = true;
            }
        }

        if (count($selectedNumbers) < $requestedCount) {
            $start = random_int($from, $to);
            $rangeSize = $to - $from + 1;

            for ($offset = 0; $offset < $rangeSize && count($selectedNumbers) < $requestedCount; $offset++) {
                $candidate = $from + (($start - $from + $offset) % $rangeSize);
                if (! isset($blockedNumbers[$candidate]) && ! isset($selectedNumbers[$candidate])) {
                    $selectedNumbers[$candidate] = true;
                }
            }
        }

        $imagePath = $request->hasFile('prize_image')
            ? 'storage/' . $request->file('prize_image')->store('raffle/prizes', 'public')
            : null;

        try {
            DB::transaction(function () use ($selectedNumbers, $data, $request, $imagePath) {
                $now = now();
                $rows = [];

                foreach (array_keys($selectedNumbers) as $number) {
                    $rows[] = [
                        'card_number' => str_pad((string) $number, 6, '0', STR_PAD_LEFT),
                        'prize_title' => $data['prize_title'],
                        'prize_image' => $imagePath,
                        'is_active' => $request->boolean('is_active', true),
                        'created_by' => Auth::id(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach (array_chunk($rows, 500) as $chunk) {
                    RaffleCard::insert($chunk);
                }
            });
        } catch (\Throwable $exception) {
            $this->deleteUpload($imagePath);
            throw $exception;
        }

        return back()->with(
            'status',
            "تمت إضافة {$requestedCount} بطاقة رابحة بأرقام عشوائية من {$data['from_number']} إلى {$data['to_number']}."
        );
    }

    public function update(Request $request, RaffleCard $card): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $data = $request->validate([
            'card_number' => ['required', 'digits:6', Rule::unique('raffle_cards', 'card_number')->ignore($card->id)],
            'prize_title' => ['required', 'string', 'max:255'],
            'prize_image' => ['nullable', 'image', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $oldImagePath = $card->prize_image;
        if ($request->hasFile('prize_image')) {
            $data['prize_image'] = 'storage/' . $request->file('prize_image')->store('raffle/prizes', 'public');
        }

        $data['is_active'] = $request->boolean('is_active');
        $card->update($data);
        if ($request->hasFile('prize_image')) {
            $this->deletePrizeImageIfUnused($oldImagePath);
        }

        return back()->with('status', 'تم تحديث بطاقة الربح بنجاح.');
    }

    public function destroy(RaffleCard $card): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $imagePath = $card->prize_image;
        $card->delete();
        $this->deletePrizeImageIfUnused($imagePath);

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

    public function randomLiveDraw(): JsonResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $entry = RaffleEntry::query()
            ->where('outcome', RaffleEntry::OUTCOME_LIVE_DRAW)
            ->inRandomOrder()
            ->first();

        if (! $entry) {
            return response()->json([
                'ok' => false,
                'message' => 'لا توجد بطاقات داخلة في سحب البثوث المباشرة حتى الآن.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'title' => 'مبروك الفوز',
            'card_number' => $entry->card_number,
            'customer_name' => $entry->customer_name ?: '-',
            'customer_phone' => $entry->customer_phone ?: '',
            'customer_whatsapp' => $entry->customer_whatsapp ?: '',
            'created_at' => optional($entry->created_at)->format('Y-m-d H:i'),
        ]);
    }

    public function destroyLiveEntry(RaffleEntry $entry): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);
        abort_unless($entry->outcome === RaffleEntry::OUTCOME_LIVE_DRAW, 404);

        $entry->delete();

        return back()->with('status', 'تم حذف رقم سحب البث المباشر بنجاح.');
    }

    public function bulkDestroyLiveEntries(Request $request): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $data = $request->validate([
            'entries' => ['required', 'array', 'min:1'],
            'entries.*' => ['integer', 'exists:raffle_entries,id'],
        ]);

        $deleted = RaffleEntry::query()
            ->where('outcome', RaffleEntry::OUTCOME_LIVE_DRAW)
            ->whereIn('id', $data['entries'])
            ->delete();

        return back()->with('status', "تم حذف {$deleted} رقم من جوائز البث المباشر.");
    }

    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'card_number' => ['required', 'digits:6'],
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.phone' => ['nullable', 'required_without:customer.whatsapp', 'string', 'max:60'],
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

    private function qrDataUri(Writer $writer, string $value): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode($writer->writeString($value));
    }

    private function defaultSocialQrLinks(): array
    {
        $shop = Shop::query()
            ->with('social')
            ->where(function ($query) {
                $query->where('slug', 'ozman')
                    ->orWhere('name', 'Ozman');
            })
            ->first();

        $social = optional($shop?->social);

        return [
            'first' => $social->instagram ?: $social->facebook ?: '',
            'second' => $social->tiktok ?: $social->youtube ?: '',
        ];
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

    private function deletePrizeImageIfUnused(?string $path): void
    {
        if ($path && ! RaffleCard::query()->where('prize_image', $path)->exists()) {
            $this->deleteUpload($path);
        }
    }

    private function localImageDataUri(?string $path): ?string
    {
        if (! $path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return null;
        }

        $absolutePath = public_path(ltrim($path, '/'));
        if (! is_file($absolutePath)) {
            return null;
        }

        $mimeType = mime_content_type($absolutePath) ?: 'image/jpeg';

        return 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($absolutePath));
    }
}
