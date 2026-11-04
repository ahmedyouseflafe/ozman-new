<?php

namespace App\Http\Controllers;

use App\Models\LoyaltyPointEntry;
use App\Models\User;
use App\Services\LoyaltyPoints;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerPointsController extends Controller
{
    public function index(Request $request, LoyaltyPoints $loyalty): View
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $search = trim((string) $request->query('search', ''));
        $customers = User::query()
            ->whereIn('role', ['customer', 'promoter'])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")))
            ->withSum(['loyaltyEntries as points_balance' => fn ($q) => $q->where('status', LoyaltyPointEntry::CONFIRMED)], 'points')
            ->withSum(['loyaltyEntries as points_pending' => fn ($q) => $q->where('status', LoyaltyPointEntry::PENDING)], 'points')
            ->orderByDesc('points_balance')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $selected = $request->integer('customer')
            ? User::query()->whereIn('role', ['customer', 'promoter'])->find($request->integer('customer'))
            : null;
        $history = $selected?->loyaltyEntries()->with(['shop', 'order'])->latest('id')->limit(30)->get();

        return view('admin.customer_points.index', [
            'customers' => $customers,
            'search' => $search,
            'selected' => $selected,
            'history' => $history,
            'selectedBalance' => $selected ? $loyalty->balance($selected) : 0,
            'selectedPending' => $selected ? $loyalty->pendingPoints($selected) : 0,
            'settings' => $loyalty->settings(),
            'canAdjust' => $request->user()->isSuperAdmin() || $request->user()->canAccessRouteName('customer-points.adjust'),
        ]);
    }

    public function adjust(Request $request, User $customer, LoyaltyPoints $loyalty): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);
        abort_unless($customer->earnsPoints(), 404);

        $data = $request->validate([
            'points' => ['required', 'integer', 'not_in:0', 'between:-100000,100000'],
            'note' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'points.not_in' => __('عدد النقاط لازم يكون موجب أو سالب، مش صفر.'),
            'note.required' => __('سبب التعديل إجباري.'),
        ]);

        $loyalty->adjust($customer, (int) $data['points'], trim($data['note']), $request->user());

        return redirect()->route('customer-points.index', ['customer' => $customer->id, 'search' => $request->query('search')])
            ->with('status', __('تم تعديل نقاط العميل.'));
    }
}
