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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([\App\Models\FrontOrder::class, \App\Models\Product::class, \App\Models\VisitorRegistration::class, \App\Models\Shop::class,
            \App\Models\MainScreen::class, \App\Models\Advertisement::class, \App\Models\Category::class] as $model) {
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
