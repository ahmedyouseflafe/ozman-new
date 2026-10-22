<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(\App\Services\ShopActivityRecorder::class);
        $this->app->scoped(\App\Services\AdminActivityPush::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([\App\Models\FrontOrder::class, \App\Models\Product::class, \App\Models\VisitorRegistration::class, \App\Models\Shop::class,
            \App\Models\MainScreen::class, \App\Models\Advertisement::class, \App\Models\Category::class,
            \App\Models\ShopStory::class, \App\Models\RewardWheel::class, \App\Models\RewardWheelSegment::class,
            \App\Models\ShopSignupReward::class, \App\Models\SalonAppointment::class,
            \App\Models\ProductImage::class, \App\Models\ProductCampaign::class] as $model) {
            $model::observe(\App\Services\ShopActivityRecorder::class);
        }
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
            // AuthController can issue a second Login event to enable remember-me.
            if (! $event->user->isShopOwner() || request()->attributes->get('analytics_login_recorded')) return;
            request()->attributes->set('analytics_login_recorded', true);
            foreach ($event->user->shops()->where('is_active', true)->pluck('id') as $shopId) {
                app(\App\Services\ShopActivityRecorder::class)->record($shopId, 'owner_login', 'دخول صاحب المتجر', [], $event->user);
            }
        });
    }
}
