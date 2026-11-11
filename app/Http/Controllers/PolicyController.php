<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\View\View;

/** Public info pages: the privacy policy, how to delete an account, and "Ozman for shops". */
class PolicyController extends Controller
{
    public function privacy(): View
    {
        return view('front.policies.privacy', ['contact' => $this->contact()]);
    }

    public function deleteAccount(): View
    {
        return view('front.policies.delete_account', ['contact' => $this->contact()]);
    }

    /** The landing page that invites shops to join, with a WhatsApp button to sign up. */
    public function business(): View
    {
        $number = $this->contactNumber();

        return view('front.business', [
            'whatsappNumber' => $number,
            'whatsapp' => $number ? 'https://wa.me/'.$number.'?text='.rawurlencode('مرحبا، بدي أسجّل محلي على أوزمان') : null,
            // "Try it as a customer": the demo restaurant where it exists (staging), otherwise the shops directory.
            'demoUrl' => Shop::query()->where('slug', 'demo-restaurant')->where('is_active', true)->exists()
                ? route('restaurant.menu', 'demo-restaurant')
                : route('front.home'),
        ]);
    }

    private function contactNumber(): ?string
    {
        $shop = Shop::query()->where('slug', 'ozman')->first(['whatsapp', 'phone']);
        $digits = preg_replace('/\D+/', '', (string) ($shop?->whatsapp ?: $shop?->phone));

        return $digits ?: null;
    }

    /** Ozman's own WhatsApp (the "ozman" shop), for questions and deletion requests. */
    private function contact(): ?string
    {
        $number = $this->contactNumber();

        return $number ? 'https://wa.me/'.$number : null;
    }
}
