<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\RaffleCard;
use App\Models\RaffleEntry;
use App\Models\RaffleBooklet;
use App\Models\VisitorRegistration;
use App\Rules\ValidPhoneNumber;
use ArPHP\I18N\Arabic;
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

    public function inspector(Request $request): View
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $data = $request->validate([
            'card_number' => ['nullable', 'digits:6'],
        ], [
            'card_number.digits' => 'أدخل رقم البطاقة المكوّن من 6 أرقام.',
        ]);

        $searched = filled($data['card_number'] ?? null);
        $card = $searched
            ? RaffleCard::query()->where('card_number', $data['card_number'])->first()
            : null;

        $winnerName = $card?->used_customer_name;

        if ($card && blank($winnerName)) {
            $winnerName = RaffleEntry::query()
                ->where('card_number', $card->card_number)
                ->where('outcome', RaffleEntry::OUTCOME_WINNER)
                ->value('customer_name');
        }

        return view('admin.raffle_cards.inspector', [
            'cardNumber' => $data['card_number'] ?? '',
            'searched' => $searched,
            'card' => $card,
            'winnerName' => $winnerName,
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
            'cards_per_page' => ['required', 'integer', Rule::in([6, 8, 10, 24])],
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

        $cardsPerPage = (int) $data['cards_per_page'];
        $makeCard = function (int $number) use ($writer) {
                $cardNumber = str_pad((string) $number, 6, '0', STR_PAD_LEFT);
                $url = route('front.raffle-card.open', ['cardNumber' => $cardNumber]);

                return [
                    'number' => $cardNumber,
                    'url' => $url,
                    'qr' => $this->qrDataUri($writer, $url),
                ];
            };

        if ($cardsPerPage === 24) {
            $pageCount = (int) ceil($count / $cardsPerPage);
            $cardPages = collect(range(0, $pageCount - 1))
                ->map(function (int $pageIndex) use ($from, $to, $pageCount, $cardsPerPage, $makeCard) {
                    return collect(range(0, $cardsPerPage - 1))
                        ->map(fn (int $positionIndex) => $from + ($positionIndex * $pageCount) + $pageIndex)
                        ->filter(fn (int $number) => $number <= $to)
                        ->map($makeCard)
                        ->values();
                });
        } else {
            $cards = collect(range($from, $to))->map($makeCard);
            $cardPages = $cards->chunk($cardsPerPage)->values();
        }

        return view('admin.raffle_cards.printable', [
            'cardPages' => $cardPages,
            'cardsPerPage' => $cardsPerPage,
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

        if (! class_exists(Pdf::class) || ! class_exists(Arabic::class)) {
            return redirect()
                ->route('raffle-cards.index')
                ->withErrors([
                    'pdf' => 'مكتبات إنشاء PDF ودعم العربية غير مثبتة على السيرفر. شغّل composer install ثم حاول مجددًا.',
                ]);
        }

        $data = $request->validate([
            'from_number' => ['required', 'digits:6'],
            'to_number' => ['required', 'digits:6'],
        ]);

        if ((int) $data['from_number'] > (int) $data['to_number']) {
            throw ValidationException::withMessages([
                'to_number' => 'رقم النهاية يجب أن يكون أكبر من أو يساوي رقم البداية.',
            ]);
        }

        $arabic = new Arabic();
        $shapeArabic = fn (?string $text): string => $arabic->utf8Glyphs(
            (string) ($text ?: '-'),
            200,
            false,
            true
        );

        $cards = RaffleCard::query()
            ->whereBetween('card_number', [$data['from_number'], $data['to_number']])
            ->orderBy('card_number')
            ->get()
            ->map(function (RaffleCard $card) use ($shapeArabic) {
                $card->pdf_prize_image = $this->localImageDataUri($card->prize_image);
                $card->pdf_prize_title = $shapeArabic($card->prize_title);
                $card->pdf_customer_name = $shapeArabic($card->used_customer_name);

                return $card;
            });

        $pdf = Pdf::loadView('admin.raffle_cards.winning_cards_pdf', [
            'cards' => $cards,
            'fromNumber' => $data['from_number'],
            'toNumber' => $data['to_number'],
            'generatedAt' => now(),
            'shapeArabic' => $shapeArabic,
        ])->setPaper('a4', 'landscape');

        return $pdf->download(
            "winning-cards-{$data['from_number']}-{$data['to_number']}.pdf"
        );
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $data = $request->validate([
            'card_number' => ['required', 'digits:6', 'unique:raffle_cards,card_number'],
            'prize_title' => ['required', 'string', 'max:255'],
            'prize_image' => ['nullable', 'image', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'card_number.required' => 'أدخل رقم البطاقة.',
            'card_number.digits' => 'رقم البطاقة يجب أن يتكون من 6 أرقام.',
            'card_number.unique' => 'رقم البطاقة مستخدم مسبقًا.',
            'prize_title.required' => 'أدخل اسم الجائزة.',
            'prize_title.max' => 'اسم الجائزة يجب ألا يتجاوز 255 حرفًا.',
            'prize_image.image' => 'ملف الجائزة يجب أن يكون صورة.',
            'prize_image.max' => 'حجم صورة الجائزة يجب ألا يتجاوز 4 ميجابايت.',
        ]);

        if ($request->hasFile('prize_image')) {
            $data['prize_image'] = 'storage/' . $request->file('prize_image')->store('raffle/prizes', 'public');
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $data['created_by'] = Auth::id();

        $card = RaffleCard::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'تمت إضافة بطاقة الربح بنجاح.',
                'total_cards' => RaffleCard::count(),
                'row_html' => view('admin.raffle_cards._winning_card_row', compact('card'))->render(),
            ], 201);
        }

        return back()->with('status', 'تمت إضافة بطاقة الربح بنجاح.');
    }

    public function storeRandomBulk(Request $request): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        if ($request->has('booklet_start_number') || $request->has('gifts')) {
            return $this->storeBooklet($request);
        }

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

    private function storeBooklet(Request $request): RedirectResponse
    {
        $giftOptions = ['مدالية مفاتيح', 'معطر سيارة', 'قداحة', 'سماعة ايربودز'];
        $data = $request->validate([
            'booklet_start_number' => ['required', 'digits:6'],
            'gifts' => ['required', 'array', 'size:4'],
            'gifts.*.title' => ['nullable', 'string', Rule::in($giftOptions)],
            'gifts.*.count' => ['nullable', 'integer', 'min:0', 'max:48'],
            'gifts.*.image' => ['nullable', 'image', 'max:4096'],
        ]);

        $from = (int) $data['booklet_start_number'];
        $to = $from + 47;
        if ($to > 999999) {
            throw ValidationException::withMessages([
                'booklet_start_number' => 'رقم البداية لا يترك مساحة لدفتر كامل من 48 بطاقة.',
            ]);
        }

        $fromNumber = str_pad((string) $from, 6, '0', STR_PAD_LEFT);
        $toNumber = str_pad((string) $to, 6, '0', STR_PAD_LEFT);
        $errors = [];
        $gifts = [];
        $seenTitles = [];

        foreach ($data['gifts'] as $index => $gift) {
            $title = trim((string) ($gift['title'] ?? ''));
            $count = (int) ($gift['count'] ?? 0);
            $image = $request->file("gifts.{$index}.image");

            if ($title === '' && $count === 0 && ! $image) {
                continue;
            }

            if ($title === '') {
                $errors["gifts.{$index}.title"] = 'اختر نوع الهدية لهذا السطر.';
            }
            if ($count < 1) {
                $errors["gifts.{$index}.count"] = 'أدخل عددًا واحدًا على الأقل للهدية المختارة.';
            }
            if (! $image) {
                $errors["gifts.{$index}.image"] = 'ارفع صورة الهدية المختارة.';
            }
            if ($title !== '' && isset($seenTitles[$title])) {
                $errors["gifts.{$index}.title"] = 'اختر كل نوع هدية مرة واحدة فقط.';
            }

            $seenTitles[$title] = true;
            $gifts[] = compact('title', 'count', 'image');
        }

        if (empty($gifts)) {
            $errors['gifts'] = 'اختر هدية واحدة على الأقل للدفتر.';
        }

        $giftsCount = array_sum(array_column($gifts, 'count'));
        if ($giftsCount > 48) {
            $errors['gifts'] = 'مجموع الهدايا لا يمكن أن يتجاوز 48 بطاقة في الدفتر الواحد.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $overlappingBooklet = RaffleBooklet::query()
            ->where('start_card_number', '<=', $toNumber)
            ->where('end_card_number', '>=', $fromNumber)
            ->first(['start_card_number', 'end_card_number']);
        $winningNumbers = RaffleCard::query()
            ->whereBetween('card_number', [$fromNumber, $toNumber])
            ->orderBy('card_number')
            ->pluck('card_number');
        // Live-draw entries are intentionally not treated as blocked numbers:
        // a new physical booklet may reuse them, as requested by the operator.
        if ($overlappingBooklet || $winningNumbers->isNotEmpty()) {
            $reasons = [];
            if ($overlappingBooklet) {
                $reasons[] = "يوجد دفتر سابق من {$overlappingBooklet->start_card_number} إلى {$overlappingBooklet->end_card_number}";
            }
            if ($winningNumbers->isNotEmpty()) {
                $reasons[] = 'بطاقات رابحة موجودة: ' . $winningNumbers->take(5)->implode('، ')
                    . ($winningNumbers->count() > 5 ? '…' : '');
            }
            throw ValidationException::withMessages([
                'booklet_start_number' => 'لا يمكن استخدام هذا الدفتر: ' . implode(' — ', $reasons) . '. اختر رقم بداية آخر.',
            ]);
        }

        $storedImages = [];
        try {
            foreach ($gifts as $index => $gift) {
                $storedImages[$index] = 'storage/' . $gift['image']->store('raffle/prizes', 'public');
                $gifts[$index]['image_path'] = $storedImages[$index];
            }

            DB::transaction(function () use ($from, $to, $fromNumber, $toNumber, $gifts, $giftsCount) {
                RaffleBooklet::create([
                    'start_card_number' => $fromNumber,
                    'end_card_number' => $toNumber,
                    'cards_count' => 48,
                    'winning_cards_count' => $giftsCount,
                    'is_active' => true,
                    'created_by' => Auth::id(),
                ]);

                $cardNumbers = range($from, $to);
                shuffle($cardNumbers);
                $prizes = [];
                foreach ($gifts as $gift) {
                    for ($count = 0; $count < $gift['count']; $count++) {
                        $prizes[] = $gift;
                    }
                }

                $now = now();
                $cards = collect($prizes)->values()->map(function (array $gift, int $index) use ($cardNumbers, $now) {
                    return [
                        'card_number' => str_pad((string) $cardNumbers[$index], 6, '0', STR_PAD_LEFT),
                        'prize_title' => $gift['title'],
                        'prize_image' => $gift['image_path'],
                        'is_active' => true,
                        'created_by' => Auth::id(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->all();

                foreach (array_chunk($cards, 500) as $chunk) {
                    RaffleCard::insert($chunk);
                }
            });
        } catch (\Throwable $exception) {
            foreach ($storedImages as $path) {
                $this->deleteUpload($path);
            }

            throw $exception;
        }

        return back()->with(
            'status',
            "تم إنشاء دفتر من 48 بطاقة ({$fromNumber} إلى {$toNumber}) وتوزيع {$giftsCount} هدية عشوائيًا بداخله."
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

    public function bulkDestroy(Request $request): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $data = $request->validate([
            'cards' => ['required', 'array', 'min:1'],
            'cards.*' => ['integer', 'distinct', 'exists:raffle_cards,id'],
        ]);

        $cards = RaffleCard::query()
            ->whereIn('id', $data['cards'])
            ->get(['id', 'prize_image']);

        $imagePaths = $cards
            ->pluck('prize_image')
            ->filter()
            ->unique()
            ->values();

        $deleted = DB::transaction(fn () => RaffleCard::query()
            ->whereIn('id', $cards->pluck('id'))
            ->delete());

        $imagePaths->each(fn (string $path) => $this->deletePrizeImageIfUnused($path));

        return back()->with('status', "تم حذف {$deleted} بطاقة رابحة محددة.");
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
        $merchantShop = $request->user()?->isShopOwner()
            ? $request->user()->shops()->where('is_active', true)->first()
            : null;

        $approvedMerchant = null;
        if (! $merchantShop && filled($request->input('merchant_registration_token'))) {
            $approvedMerchant = VisitorRegistration::query()
                ->where('public_token', $request->input('merchant_registration_token'))
                ->where('type', 'merchant')
                ->where('status', 'approved')
                ->first();
        }

        if ($merchantShop) {
            $request->merge([
                'customer' => [
                    'name' => $merchantShop->name,
                    'phone' => $merchantShop->phone ?: $request->user()?->phone,
                    'whatsapp' => $merchantShop->whatsapp ?: $merchantShop->phone ?: $request->user()?->phone,
                    'address' => $merchantShop->address,
                ],
            ]);
        } elseif ($approvedMerchant) {
            $request->merge([
                'customer' => [
                    'name' => $approvedMerchant->shop_name ?: $approvedMerchant->name,
                    'phone' => $approvedMerchant->phone,
                    'whatsapp' => $approvedMerchant->phone,
                    'address' => $approvedMerchant->business_location ?: $approvedMerchant->residence_address,
                ],
            ]);
        }

        $data = $request->validate([
            'card_number' => ['required', 'digits:6'],
            'merchant_registration_token' => ['nullable', 'string', 'size:64'],
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.phone' => ['nullable', 'required_without:customer.whatsapp', 'string', 'max:60', new ValidPhoneNumber()],
            'customer.whatsapp' => ['nullable', 'string', 'max:60', new ValidPhoneNumber()],
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
