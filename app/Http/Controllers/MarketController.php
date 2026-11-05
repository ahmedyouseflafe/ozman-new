<?php

namespace App\Http\Controllers;

use App\Models\MarketListing;
use App\Models\MarketListingImage;
use App\Services\MarketImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** "المتجر الحر": browsing, and members managing their own listings. */
class MarketController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'category' => array_key_exists((string) $request->query('category'), config('market.categories')) ? (string) $request->query('category') : null,
            'condition' => array_key_exists((string) $request->query('condition'), config('market.conditions')) ? (string) $request->query('condition') : null,
            'city' => trim((string) $request->query('city', '')),
            'sort' => in_array($request->query('sort'), ['price_asc', 'price_desc'], true) ? $request->query('sort') : 'latest',
        ];
        $query = MarketListing::visible()->with(['images', 'seller:id,name,username'])
            ->when($filters['q'] !== '', fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$filters['q'].'%')->orWhere('description', 'like', '%'.$filters['q'].'%')))
            ->when($filters['category'], fn ($q, $c) => $q->where('category', $c))
            ->when($filters['condition'], fn ($q, $c) => $q->where('condition', $c))
            ->when($filters['city'] !== '', fn ($q) => $q->where('city', 'like', '%'.$filters['city'].'%'));
        $query = match ($filters['sort']) {
            'price_asc' => $query->orderBy('price')->latest('id'),
            'price_desc' => $query->orderByDesc('price')->latest('id'),
            default => $query->latest('id'),
        };

        return response()->view('market.index', ['listings' => $query->paginate(24)->withQueryString(), 'filters' => $filters]);
    }

    public function show(Request $request, MarketListing $listing)
    {
        $listing->load(['images', 'seller']);
        $canManage = $listing->canBeManagedBy($request->user());
        abort_unless($listing->isVisible() || $canManage, 404);
        // One view per visitor per day; the seller's own visits don't count.
        $key = 'market_viewed.'.$listing->id;
        if (! $canManage && $request->session()->get($key) !== now()->toDateString()) {
            $request->session()->put($key, now()->toDateString());
            MarketListing::query()->whereKey($listing->id)->increment('views');
        }
        $more = MarketListing::visible()->with('images')->where('user_id', $listing->user_id)->whereKeyNot($listing->id)->latest('id')->limit(4)->get();

        return response()->view('market.show', compact('listing', 'canManage', 'more'))->header('Cache-Control', 'private, no-store');
    }

    public function image(Request $request, MarketListingImage $image)
    {
        $listing = $image->listing;
        abort_unless($listing && ($listing->isVisible() || $listing->canBeManagedBy($request->user())), 404);
        $path = $image->path;
        abort_unless(str_starts_with($path, MarketImage::DIRECTORY.'/') && ! str_contains($path, '..') && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), ['Cache-Control' => 'public, max-age=86400', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function mine(Request $request)
    {
        $this->member($request);

        return response()->view('market.mine', [
            'listings' => $request->user()->hasMany(MarketListing::class)->with('images')->withCount(['orders as open_orders_count' => fn ($q) => $q->where('status', 'pending')])->latest('id')->paginate(20),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function create(Request $request)
    {
        $this->member($request);

        return response()->view('market.form', ['listing' => new MarketListing(['quantity' => 1, 'condition' => 'new', 'city' => ''])])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, MarketImage $images)
    {
        $this->member($request);
        $active = MarketListing::where('user_id', $request->user()->id)->where('status', MarketListing::ACTIVE)->count();
        if ($active >= config('market.max_active_listings', 50)) {
            return back()->withInput()->withErrors(['title' => __('وصلت للحد الأقصى من المنتجات المعروضة. اخفي أو احذف منتج قبل ما تضيف جديد.')]);
        }
        $data = $this->validated($request, true);
        $paths = $this->storeImages($request, $images);
        try {
            $listing = DB::transaction(function () use ($request, $data, $paths) {
                $listing = MarketListing::create([...$data, 'user_id' => $request->user()->id, 'status' => MarketListing::ACTIVE]);
                foreach ($paths as $i => $path) {
                    $listing->images()->create(['path' => $path, 'sort_order' => $i]);
                }

                return $listing;
            });
        } catch (\Throwable $e) {
            $images->delete($paths);
            throw $e;
        }

        return redirect()->to($listing->url())->with('status', __('تم نشر منتجك بالمتجر الحر 🎉'));
    }

    public function edit(Request $request, MarketListing $listing)
    {
        $this->owner($request, $listing);

        return response()->view('market.form', ['listing' => $listing->load('images')])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, MarketListing $listing, MarketImage $images)
    {
        $this->owner($request, $listing);
        $data = $this->validated($request, false);
        $listing->load('images');
        $remove = $listing->images->whereIn('id', array_map('intval', (array) $request->input('remove_images', [])));
        $keep = $listing->images->count() - $remove->count();
        $max = config('market.max_images', 6);
        if ($keep + count($request->file('images', [])) > $max) {
            return back()->withInput()->withErrors(['images' => __('بتقدر تضيف لحد :max صور.', ['max' => $max])]);
        }
        if ($keep + count($request->file('images', [])) < 1) {
            return back()->withInput()->withErrors(['images' => __('خلّي صورة وحدة على الأقل للمنتج.')]);
        }
        $paths = $this->storeImages($request, $images);
        try {
            DB::transaction(function () use ($listing, $data, $paths, $remove) {
                // A sold-out listing comes back when the seller adds stock; the admin's removal stays.
                $status = match (true) {
                    $listing->status === MarketListing::SOLD && (int) $data['quantity'] > 0 => MarketListing::ACTIVE,
                    $listing->status === MarketListing::ACTIVE && (int) $data['quantity'] === 0 => MarketListing::SOLD,
                    default => $listing->status,
                };
                $listing->update([...$data, 'status' => $status]);
                MarketListingImage::query()->whereIn('id', $remove->pluck('id'))->delete();
                $next = (int) $listing->images()->max('sort_order') + 1;
                foreach ($paths as $i => $path) {
                    $listing->images()->create(['path' => $path, 'sort_order' => $next + $i]);
                }
            });
        } catch (\Throwable $e) {
            $images->delete($paths);
            throw $e;
        }
        $images->delete($remove->pluck('path')->all());

        return redirect()->to($listing->url())->with('status', __('تم حفظ التعديلات.'));
    }

    public function toggle(Request $request, MarketListing $listing)
    {
        $this->owner($request, $listing);
        abort_if($listing->status === MarketListing::REMOVED, 403, __('الإدارة أوقفت هذا المنتج.'));
        $listing->update(['status' => $listing->status === MarketListing::ACTIVE ? MarketListing::HIDDEN : MarketListing::ACTIVE]);

        return back()->with('status', $listing->status === MarketListing::ACTIVE ? __('المنتج رجع معروض.') : __('تم إخفاء المنتج.'));
    }

    public function destroy(Request $request, MarketListing $listing, MarketImage $images)
    {
        $this->owner($request, $listing);
        abort_if($listing->orders()->where('status', 'accepted')->exists(), 422, __('في طلب مقبول على هالمنتج. سلّمه أو ألغيه أول.'));
        $paths = $listing->images()->pluck('path')->all();
        DB::transaction(function () use ($listing) {
            // Pending orders can't be fulfilled any more.
            $listing->orders()->where('status', 'pending')->update(['status' => 'cancelled']);
            $listing->delete();
        });
        $images->delete($paths);

        return redirect()->route('market.mine')->with('status', __('تم حذف المنتج.'));
    }

    private function validated(Request $request, bool $creating): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['nullable', 'string', 'max:3000'],
            'price' => ['required', 'numeric', 'min:0.5', 'max:1000000'],
            'category' => ['required', Rule::in(array_keys(config('market.categories')))],
            'condition' => ['required', Rule::in(array_keys(config('market.conditions')))],
            'city' => ['required', 'string', 'min:2', 'max:60'],
            'quantity' => ['required', 'integer', 'min:'.($creating ? 1 : 0), 'max:999'],
            ...MarketImage::rules(),
            'images' => [$creating ? 'required' : 'nullable', 'array', 'max:'.config('market.max_images', 6)],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
        ], [...MarketImage::messages(), 'images.required' => __('أضف صورة وحدة على الأقل للمنتج.'),
            'price.min' => __('السعر لازم يكون نص شيكل أو أكثر.'), 'quantity.min' => __('الكمية لازم تكون 1 أو أكثر.')]);
        unset($data['images'], $data['remove_images']);
        $data['title'] = trim($data['title']);
        $data['city'] = trim($data['city']);

        return $data;
    }

    private function storeImages(Request $request, MarketImage $images): array
    {
        $paths = [];
        try {
            foreach ($request->file('images', []) as $file) {
                $paths[] = $images->store($file);
            }
        } catch (\Throwable $e) {
            $images->delete($paths);
            throw $e;
        }

        return $paths;
    }

    private function member(Request $request): void
    {
        abort_unless($request->user()?->is_active && $request->user()->earnsPoints(), 403, __('المتجر الحر لحسابات العملاء والمروّجين.'));
    }

    private function owner(Request $request, MarketListing $listing): void
    {
        abort_unless($listing->canBeManagedBy($request->user()), 403);
    }
}
