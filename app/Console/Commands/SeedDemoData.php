<?php

namespace App\Console\Commands;

use App\Models\{Category, Product, RewardWheel, Shop, User};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Fills a TEST copy of the site (staging) with demo shops, products and test accounts,
 * so the mobile app can be tried without touching real shops or customers.
 * Refuses to run on production.
 */
class SeedDemoData extends Command
{
    protected $signature = 'ozman:demo-data';

    protected $description = 'Create demo shops, products and test accounts on the staging copy (never on production)';

    private const SHOPS = [
        ['restaurant', 'مطعم الديمو', 'demo-restaurant', 'رام الله', [
            'سوشي' => [['سلمون رول', 45, 39], ['كاليفورنيا رول', 38, null], ['ساندويش سوشي مقلي', 42, null]],
            'برغر' => [['برغر لحمة دبل', 48, null], ['برغر دجاج كرسبي', 39, 35]],
            'مشروبات' => [['بيبسي', 8, null], ['عصير برتقال طازة', 14, null]],
        ]],
        ['cosmetics', 'إليجانس للتجميل (ديمو)', 'demo-cosmetics', 'نابلس', [
            'مكياج' => [['أحمر شفاه مات', 59, 49], ['كريم أساس خفيف', 89, null]],
            'عناية بالبشرة' => [['سيروم فيتامين C', 120, 99], ['كريم ترطيب يومي', 75, null]],
        ]],
        ['clothing', 'ستايل للملابس (ديمو)', 'demo-clothing', 'الخليل', [
            'رجالي' => [['قميص قطن أبيض', 99, null], ['جاكيت شتوي', 249, 199]],
            'نسائي' => [['فستان صيفي', 159, 129], ['بلوزة كتان', 89, null]],
        ]],
        ['electronics', 'تك ستور (ديمو)', 'demo-electronics', 'جنين', [
            'سماعات' => [['سماعة بلوتوث لاسلكية', 189, 159], ['سماعة رأس للألعاب', 249, null]],
            'شواحن' => [['شاحن سريع 33 واط', 69, null], ['باور بنك 20000', 129, 109]],
        ]],
        ['sweets', 'حلويات الديمو', 'demo-sweets', 'بيت لحم', [
            'كنافة' => [['كنافة نابلسية (كيلو)', 70, null], ['كنافة بالقشطة', 75, 65]],
            'كيك' => [['كيك شوكولاتة', 120, null], ['تشيز كيك فراولة', 110, 95]],
        ]],
    ];

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('This command only runs on staging/local copies, never on production.');
            return self::FAILURE;
        }

        $passwords = [];
        DB::transaction(function () use (&$passwords) {
            foreach (self::SHOPS as $index => [$type, $name, $slug, $city, $menu]) {
                $email = "demo-owner-{$type}@staging.ozman.test";
                $owner = User::where('email', $email)->first();
                if (! $owner) {
                    $passwords[$email] = Str::password(14, symbols: false);
                    $owner = User::create(['name' => 'صاحب '.$name, 'email' => $email, 'password' => Hash::make($passwords[$email]), 'role' => 'shop_owner', 'is_active' => true]);
                }
                $shop = Shop::updateOrCreate(['slug' => $slug], [
                    'user_id' => $owner->id, 'name' => $name, 'catalog_type' => $type, 'city' => $city,
                    'description' => 'متجر تجريبي لتجربة تطبيق أوزمان.', 'phone' => '0590000'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                    'is_active' => true, 'is_accepting_orders' => true, 'loyalty_enabled' => true,
                ]);
                foreach ($menu as $categoryName => $products) {
                    $category = Category::updateOrCreate(['shop_id' => $shop->id, 'name' => $categoryName],
                        ['slug' => $slug.'-'.substr(md5($categoryName), 0, 8), 'is_active' => true]);
                    foreach ($products as $position => [$productName, $price, $discount]) {
                        Product::updateOrCreate(['shop_id' => $shop->id, 'name' => $productName], [
                            'category_id' => $category->id, 'slug' => $slug.'-'.substr(md5($productName), 0, 8),
                            'description' => 'منتج تجريبي: '.$productName.'.', 'price' => $price, 'discount_price' => $discount,
                            'is_active' => true, 'is_featured' => $position === 0,
                            // Clothes come in sizes and colours, so the app's option picker can be tried.
                            'catalog_attributes' => match (true) {
                                $type === 'clothing' => ['sizes' => ['S', 'M', 'L', 'XL'], 'colors' => ['أسود', 'أبيض', 'كحلي']],
                                // Burgers get every kind of meal choice, so the app's meal sheet can be tried.
                                $type === 'restaurant' && $categoryName === 'برغر' => [
                                    'meal_size_prices' => ['عادي:'.($discount ?: $price), 'دبل:'.(($discount ?: $price) + 12)],
                                    'addon_prices' => ['جبنة زيادة:3', 'بيض:4', 'هالبينو:2'],
                                    'removable_ingredients' => ['بصل', 'مخلل', 'صوص'],
                                    'meal_choice_groups' => [
                                        ['id' => 'drink', 'name' => 'المشروب', 'min' => 1, 'max' => 1, 'allow_repeat' => false, 'source' => 'manual',
                                            'options' => [['id' => 'cola', 'name' => 'كولا', 'price' => 0], ['id' => 'orange', 'name' => 'عصير برتقال', 'price' => 4]]],
                                        ['id' => 'sauce', 'name' => 'صوصات', 'min' => 0, 'max' => 2, 'allow_repeat' => false, 'source' => 'manual',
                                            'options' => [['id' => 'garlic', 'name' => 'ثوم', 'price' => 0], ['id' => 'bbq', 'name' => 'باربكيو', 'price' => 1], ['id' => 'spicy', 'name' => 'حار', 'price' => 1]]],
                                    ],
                                ],
                                default => null,
                            },
                        ]);
                    }
                }
            }

            // The demo restaurant gets a welcome wheel, so "sign up and spin" can be tried in the app.
            $restaurant = Shop::where('slug', 'demo-restaurant')->first();
            if ($restaurant && ! RewardWheel::where('key', 'shop_signup_'.$restaurant->id)->exists()) {
                $wheel = RewardWheel::create(['shop_id' => $restaurant->id, 'key' => 'shop_signup_'.$restaurant->id,
                    'wheel_type' => RewardWheel::TYPE_CUSTOMER_SIGNUP, 'title' => 'سجّل واربح على أول طلب', 'is_active' => true]);
                foreach ([['خصم 10%', 'percent', 10, '#08DEF4'], ['خصم 5 ₪', 'amount', 5, '#7C4DFF'], ['حظ أوفر', 'none', 0, '#27DD86'], ['خصم 15%', 'percent', 15, '#FF9F43']] as [$label, $type, $value, $color]) {
                    $wheel->segments()->create(['label' => $label, 'discount_type' => $type, 'discount_value' => $value, 'color' => $color, 'is_active' => true, 'win_quota' => 1]);
                }
            }

            $customerEmail = 'demo-customer@staging.ozman.test';
            if (! User::where('email', $customerEmail)->exists()) {
                $passwords[$customerEmail] = Str::password(14, symbols: false);
                User::create(['name' => 'زبون تجريبي', 'email' => $customerEmail, 'username' => 'democustomer', 'password' => Hash::make($passwords[$customerEmail]), 'role' => 'customer', 'is_active' => true]);
            }
        });

        $this->info('Demo shops: '.Shop::where('slug', 'like', 'demo-%')->count().' · demo products: '.Product::whereIn('shop_id', Shop::where('slug', 'like', 'demo-%')->pluck('id'))->count());
        if ($passwords) {
            $this->warn('New test accounts (shown once — keep them for testing the app on staging):');
            foreach ($passwords as $email => $password) {
                $this->line("  {$email}  {$password}");
            }
        } else {
            $this->line('Test accounts already exist; passwords unchanged.');
        }

        return self::SUCCESS;
    }
}
