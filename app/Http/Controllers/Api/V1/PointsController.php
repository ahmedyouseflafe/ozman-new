<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPointEntry;
use App\Services\LoyaltyPoints;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The customer's loyalty points: balance, points waiting for delivery, per shop and recent history. */
class PointsController extends Controller
{
    public function index(Request $request, LoyaltyPoints $loyalty): JsonResponse
    {
        $user = $request->user();
        $settings = $loyalty->settings();
        $earns = $user->earnsPoints();

        return response()->json([
            'enabled' => $settings['enabled'] && $earns,
            'rule' => ['amount' => $settings['amount'], 'points' => $settings['points']],
            'balance' => $earns ? $loyalty->balance($user) : 0,
            'pending' => $earns ? $loyalty->pendingPoints($user) : 0,
            'shops' => $earns ? $loyalty->shopBalances($user)->filter(fn ($row) => $row['balance'] > 0)
                ->map(fn ($row) => ['shop_id' => $row['shop_id'], 'shop_name' => $row['shop_name'] ?? 'أوزمان', 'balance' => $row['balance']])->values() : [],
            'history' => $earns ? $user->loyaltyEntries()->with('shop:id,name')->latest('id')->limit(30)->get()
                ->map(fn (LoyaltyPointEntry $entry) => [
                    'points' => (int) $entry->points,
                    'status' => $entry->status,
                    'type' => $entry->type,
                    'shop' => $entry->shop?->name,
                    'note' => $entry->note,
                    'created_at' => $entry->created_at?->toIso8601String(),
                ]) : [],
        ]);
    }
}
