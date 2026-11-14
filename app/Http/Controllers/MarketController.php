<?php

namespace App\Http\Controllers;

use App\Models\MarketListing;
use App\Models\MarketListingImage;
use App\Services\FreeMarket;
use App\Services\MarketImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    public function store(Request $request, FreeMarket $market)
    {
        $this->member($request);
        $listing = $market->createListing($request->user(), $market->validateListing($request->all(), true), $request->file('images', []));

        return redirect()->to($listing->url())->with('status', __('تم نشر منتجك بالمتجر الحر 🎉'));
    }

    public function edit(Request $request, MarketListing $listing)
    {
        $this->owner($request, $listing);

        return response()->view('market.form', ['listing' => $listing->load('images')])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, MarketListing $listing, FreeMarket $market)
    {
        $this->owner($request, $listing);
        $data = $market->validateListing($request->all(), false);
        $market->updateListing($listing, $data, $request->file('images', []), (array) $request->input('remove_images', []));

        return redirect()->to($listing->url())->with('status', __('تم حفظ التعديلات.'));
    }

    public function toggle(Request $request, MarketListing $listing, FreeMarket $market)
    {
        $this->owner($request, $listing);
        $market->toggleListing($listing);

        return back()->with('status', $listing->status === MarketListing::ACTIVE ? __('المنتج رجع معروض.') : __('تم إخفاء المنتج.'));
    }

    public function destroy(Request $request, MarketListing $listing, FreeMarket $market)
    {
        $this->owner($request, $listing);
        $market->deleteListing($listing);

        return redirect()->route('market.mine')->with('status', __('تم حذف المنتج.'));
    }

    private function member(Request $request): void
    {
        if (! ($request->user()?->is_active && $request->user()->earnsPoints())) {
            // Admins and staff browse the market but don't buy or sell in it.
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                redirect()->route('market.index')->with('status', __('البيع والطلب بالمتجر الحر لحسابات العملاء والمروّجين.')));
        }
    }

    private function owner(Request $request, MarketListing $listing): void
    {
        abort_unless($listing->canBeManagedBy($request->user()), 403);
    }
}
