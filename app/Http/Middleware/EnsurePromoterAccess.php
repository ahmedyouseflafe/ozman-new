<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePromoterAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        // Customers can't open the creator area; their account page explains how to apply.
        if ($user?->is_active && $user->isCustomer()) {
            return redirect()->route('community.account')->with('status', __('هاي المساحة للمروّجين. بدك تصير مروّج؟ قدّم طلبك من حسابك.'));
        }
        // A promoter who signed up waits for approval; they get their customer account meanwhile.
        if ($user?->is_active && $user->isPromoter() && $user->promoterProfile?->isPending()) {
            return redirect()->route('community.account')->with('status', __('طلبك كمروّج لسا قيد المراجعة. بيوصلك إيميل أول ما الإدارة تقبله.'));
        }
        abort_unless($user?->is_active && ($user->isSuperAdmin() || ($user->isPromoter() && $user->promoterProfile?->is_active)), 403);
        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
