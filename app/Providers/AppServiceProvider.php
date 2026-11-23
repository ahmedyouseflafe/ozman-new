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
        // Data the shared admin header and sidebar need, prepared outside the views.
        \Illuminate\Support\Facades\View::composer('admin.includes.sidebar', \App\View\Composers\AdminSidebarComposer::class);
        \Illuminate\Support\Facades\View::composer('admin.includes.header', \App\View\Composers\AdminHeaderComposer::class);

        $pages = \App\View\Composers\PageDataComposers::class;
        \Illuminate\Support\Facades\View::composer('admin.dashboard', "{$pages}@adminDashboard");
        \Illuminate\Support\Facades\View::composer('promoters.layout', "{$pages}@communityLayout");
        \Illuminate\Support\Facades\View::composer('market.nav', "{$pages}@marketNav");
        \Illuminate\Support\Facades\View::composer(['admin.products.products', 'admin.products.products_show'], "{$pages}@agentIds");
        \Illuminate\Support\Facades\View::composer('front.partials.page_share', "{$pages}@shopRating");
        \Illuminate\Support\Facades\View::composer('front.partials.restaurant_purchase_wheel', "{$pages}@purchaseWheels");
        \Illuminate\Support\Facades\View::composer('front.restaurant_menu', "{$pages}@restaurantMenu");
        \Illuminate\Support\Facades\View::composer('admin.reward_wheels.purchase_amount', "{$pages}@purchaseWheelSettings");

        // Pages' own CSS and scripts, kept in public/css/views and public/js/views (see App\Support\ViewAssets).
        \Illuminate\Support\Facades\Blade::directive('viewStyle', fn (string $name) => "<?php echo \\App\\Support\\ViewAssets::style({$name}); ?>");
        \Illuminate\Support\Facades\Blade::directive('viewScript', fn (string $name) => "<?php echo \\App\\Support\\ViewAssets::script({$name}); ?>");

        // Customer sign-up rule, shared by /community/register and the promoter follow form.
        \Illuminate\Validation\Rules\Password::defaults(fn () => \Illuminate\Validation\Rules\Password::min(8)->max(128));

        // "Forgot password" emails for customers and promoters: community reset page, Arabic text.
        \Illuminate\Auth\Notifications\ResetPassword::toMailUsing(fn ($user, string $token) => (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject(__('تعيين كلمة مرور جديدة · أوزمان'))
            ->greeting(__('أهلًا :name', ['name' => \Illuminate\Support\Str::before(trim((string) $user->name), ' ') ?: $user->name]))
            ->line(__('وصلنا طلب لتعيين كلمة مرور جديدة لحسابك على أوزمان.'))
            ->action(__('اختيار كلمة مرور جديدة'), route('community.password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()]))
            ->line(__('الرابط صالح لمدة :count دقيقة ويُستخدم مرة واحدة.', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]))
            ->line(__('إذا ما طلبت هذا، تجاهل الرسالة وكلمة مرورك بتضل زي ما هي.'))
            ->salutation(__('فريق أوزمان')));

        foreach ([\App\Models\FrontOrder::class, \App\Models\Product::class, \App\Models\VisitorRegistration::class, \App\Models\Shop::class,
            \App\Models\MainScreen::class, \App\Models\Advertisement::class, \App\Models\Category::class,
            \App\Models\ShopStory::class, \App\Models\RewardWheel::class, \App\Models\RewardWheelSegment::class,
            \App\Models\ShopSignupReward::class, \App\Models\SalonAppointment::class,
            \App\Models\ProductImage::class, \App\Models\ProductCampaign::class] as $model) {
            $model::observe(\App\Services\ShopActivityRecorder::class);
        }
        \App\Models\FrontOrder::observe(\App\Observers\FrontOrderLoyaltyObserver::class);
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
