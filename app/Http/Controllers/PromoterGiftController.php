<?php

namespace App\Http\Controllers;

use App\Models\PromoterGift;
use App\Models\PromoterGiftSend;
use App\Models\PromoterProfile;
use App\Services\LoyaltyPoints;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Customers support promoters with gifts paid in loyalty points; gifts raise the promoter's level. */
class PromoterGiftController extends Controller
{
    public function store(Request $request, PromoterProfile $profile, LoyaltyPoints $loyalty): JsonResponse
    {
        abort_unless($profile->isPublished(), 404);
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'message' => __('سجّل دخول بحساب عميل عشان تهدي من نقاطك.'),
                'login' => route('community.login', ['return' => parse_url($profile->publicUrl(), PHP_URL_PATH)]),
            ], 401);
        }
        abort_unless($loyalty->eligibleCustomer($user), 403, __('الهدايا متاحة لحسابات العملاء والمروّجين فقط.'));
        abort_if((int) $profile->user_id === (int) $user->id, 403, __('ما بتقدر تهدي صفحتك.'));

        $data = $request->validate([
            'gift_id' => ['required', 'integer', Rule::exists('promoter_gifts', 'id')->where('is_active', true)],
            'post_id' => ['nullable', 'integer', Rule::exists('promoter_posts', 'id')->where('promoter_profile_id', $profile->id)],
        ]);
        $gift = PromoterGift::query()->whereKey($data['gift_id'])->where('is_active', true)->firstOrFail();

        $send = DB::transaction(function () use ($user, $profile, $gift, $data, $loyalty) {
            $send = PromoterGiftSend::create([
                'promoter_profile_id' => $profile->id, 'user_id' => $user->id, 'promoter_gift_id' => $gift->id,
                'promoter_post_id' => $data['post_id'] ?? null,
                'gift_name' => $gift->name, 'gift_icon' => $gift->icon, 'points' => $gift->points,
            ]);
            $send->setRelation('profile', $profile);
            $loyalty->spendOnGift($user, $gift->points, $send);
            PromoterProfile::query()->whereKey($profile->id)->increment('gift_points', $gift->points);

            return $send;
        });

        $profile->refresh()->loadCount(['followers', 'guestFollowers']);

        return response()->json([
            'sent' => true,
            'message' => __(':icon وصلت :name لـ :promoter. شكراً لدعمك!', ['icon' => $send->gift_icon, 'name' => $send->gift_name, 'promoter' => $profile->display_name]),
            'balance' => $loyalty->balance($user),
            'gift_points' => (int) $profile->gift_points,
            'level' => $profile->level(),
        ]);
    }

    public function index(Request $request): View
    {
        $this->admin($request);

        return view('promoters.gifts', [
            'gifts' => PromoterGift::query()->orderBy('sort_order')->orderBy('points')->get(),
            'topProfiles' => PromoterProfile::query()->orderByDesc('gift_points')->limit(10)->get(['id', 'display_name', 'slug', 'gift_points', 'page_views']),
        ]);
    }

    public function storeGift(Request $request): RedirectResponse
    {
        $this->admin($request);
        PromoterGift::create($this->validated($request));

        return back()->with('status', __('تمت إضافة الهدية.'));
    }

    public function updateGift(Request $request, PromoterGift $gift): RedirectResponse
    {
        $this->admin($request);
        $gift->update($this->validated($request));

        return back()->with('status', __('تم تحديث الهدية.'));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'icon' => ['required', 'string', 'max:16'],
            'points' => ['required', 'integer', 'min:1', 'max:100000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return [...$data, 'sort_order' => $data['sort_order'] ?? 0, 'is_active' => $request->boolean('is_active')];
    }

    private function admin(Request $request): void
    {
        abort_unless($request->user()?->is_active && $request->user()->isSuperAdmin(), 403);
    }
}
