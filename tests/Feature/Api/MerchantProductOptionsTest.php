<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\V1\Presenter;
use App\Models\{ApiToken, Category, Product, Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** A meal's sizes, add-ons and choices, and a device's storage/colour options, edited from the app. */
class MerchantProductOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function shop(string $type): array
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Demo '.$type, 'slug' => $type.'-'.uniqid(), 'catalog_type' => $type, 'is_active' => true, 'is_accepting_orders' => true]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Main', 'slug' => 'main-'.uniqid(), 'is_active' => true]);
        [, $plain] = ApiToken::issue($owner);

        return [$shop, $category, ['Authorization' => 'Bearer '.$plain, 'Accept' => 'application/json']];
    }

    public function test_a_restaurant_builds_a_meal_with_sizes_addons_and_choices_from_the_phone(): void
    {
        [$shop, $category, $headers] = $this->shop('restaurant');
        $options = [
            'sizes' => [['name' => 'عادي', 'price' => 25], ['name' => 'دبل', 'price' => 37.5]],
            'addons' => [['name' => 'جبنة', 'price' => 3]],
            'removable' => ['بصل', 'مخلل'],
            'groups' => [['name' => 'المشروب', 'min' => 1, 'max' => 1, 'allow_repeat' => false, 'options' => [['name' => 'كولا', 'price' => 0], ['name' => 'عصير', 'price' => 4]]]],
        ];

        // Sent the way the app sends it: multipart with the options as JSON text.
        $id = $this->post("/api/v1/merchant/shops/{$shop->id}/products", [
            'name' => 'برغر', 'price' => 25, 'category_id' => $category->id, 'image' => UploadedFile::fake()->image('b.jpg'),
            'meal_options' => json_encode($options),
        ], $headers)->assertCreated()
            ->assertJsonPath('product.meal_options.sizes.1', ['name' => 'دبل', 'price' => 37.5])
            ->assertJsonPath('product.meal_options.groups.0.options.1.name', 'عصير')
            ->assertJsonPath('product.has_website_options', false)
            ->json('product.id');

        $product = Product::findOrFail($id);
        // Stored exactly like the website's form stores it, so checkout and the shop page read it as before.
        $this->assertSame(['عادي:25', 'دبل:37.5'], $product->catalog_attributes['meal_size_prices']);
        $this->assertSame(['عادي', 'دبل'], $product->catalog_attributes['meal_sizes']);
        $this->assertSame(['جبنة:3'], $product->catalog_attributes['addon_prices']);
        $this->assertSame(['بصل', 'مخلل'], $product->catalog_attributes['removable_ingredients']);
        $meal = Presenter::mealOptions($product);
        $this->assertSame([['name' => 'جبنة', 'price' => 3.0]], $meal['addons']);
        $this->assertSame('المشروب', $meal['groups'][0]['name']);
        $groupId = $meal['groups'][0]['id'];
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_-]{1,64}$/', $groupId);

        // The customer's app sees the same options it would for a meal made on the website.
        $this->getJson("/api/v1/products/{$id}")->assertOk()->assertJsonPath('product.meal.sizes.1.price', 37.5);
    }

    public function test_saving_from_the_phone_keeps_what_only_the_website_sets_up(): void
    {
        [$shop, $category, $headers] = $this->shop('restaurant');
        $side = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'بطاطا', 'slug' => 'fries-'.uniqid(), 'price' => 8, 'quantity' => 1, 'rating' => 0, 'is_active' => true]);
        $product = Product::create([
            'shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'وجبة', 'slug' => 'meal-'.uniqid(), 'price' => 30, 'quantity' => 1, 'rating' => 0, 'is_active' => true,
            'catalog_attributes' => [
                'preparation_time' => 15, 'calories' => 700,
                'meal_size_prices' => ['عادي:30'],
                'meal_choice_groups' => [
                    ['id' => 'drink', 'name' => 'المشروب', 'min' => 1, 'max' => 1, 'allow_repeat' => false, 'source' => 'manual', 'options' => [['id' => 'cola', 'name' => 'كولا', 'price' => 0]]],
                    ['id' => 'side', 'name' => 'طبق جانبي', 'min' => 0, 'max' => 1, 'allow_repeat' => false, 'source' => 'menu', 'options' => []],
                ],
            ],
        ]);

        $this->getJson("/api/v1/merchant/products/{$product->id}", $headers)->assertOk()
            ->assertJsonPath('product.meal_options.groups.0.id', 'drink')
            ->assertJsonPath('product.meal_options.website_groups', ['طبق جانبي'])
            ->assertJsonPath('product.has_website_options', true);

        // The phone sends back the manual group (renamed, with a new choice) and a new add-on.
        $this->post("/api/v1/merchant/products/{$product->id}", ['meal_options' => json_encode([
            'sizes' => [['name' => 'عادي', 'price' => 30]],
            'addons' => [['name' => 'صوص', 'price' => 2]],
            'removable' => [],
            'groups' => [['id' => 'drink', 'name' => 'اختار مشروب', 'min' => 1, 'max' => 1, 'allow_repeat' => false, 'options' => [['id' => 'cola', 'name' => 'كولا', 'price' => 0], ['name' => 'ماء', 'price' => 1]]]],
        ])], $headers)->assertOk();

        $attributes = $product->fresh()->catalog_attributes;
        $this->assertSame(15, $attributes['preparation_time']);
        $this->assertSame(700, $attributes['calories']);
        $this->assertSame(['صوص:2'], $attributes['addon_prices']);
        $groups = collect($attributes['meal_choice_groups'])->keyBy('id');
        $this->assertSame('اختار مشروب', $groups['drink']['name']);
        $this->assertCount(2, $groups['drink']['options']);
        $this->assertSame('menu', $groups['side']['source']);
        // The menu group still lists the shop's other dishes for the customer.
        $resolved = collect(app(\App\Services\MealChoiceService::class)->forProduct($product->fresh()))->keyBy('id');
        $this->assertSame('product-'.$side->id, $resolved['side']['options'][0]['id']);

        // A plain save (no options sent) leaves them alone.
        $this->post("/api/v1/merchant/products/{$product->id}", ['price' => 32], $headers)->assertOk();
        $this->assertSame($attributes, $product->fresh()->catalog_attributes);

        // Emptying a list removes it.
        $this->post("/api/v1/merchant/products/{$product->id}", ['meal_options' => json_encode(['addons' => []])], $headers)->assertOk();
        $this->assertArrayNotHasKey('addon_prices', $product->fresh()->catalog_attributes);
    }

    public function test_meal_options_are_checked(): void
    {
        [$shop, $category, $headers] = $this->shop('restaurant');
        $make = fn (array $options) => $this->post("/api/v1/merchant/shops/{$shop->id}/products", ['name' => 'X', 'price' => 10, 'category_id' => $category->id, 'meal_options' => json_encode($options)], $headers);

        $make(['sizes' => [['name' => 'كبير, جداً', 'price' => 5]]])->assertStatus(422);
        $make(['addons' => [['name' => 'جبنة', 'price' => 1], ['name' => 'جبنة', 'price' => 2]]])->assertStatus(422);
        $make(['addons' => [['name' => 'جبنة', 'price' => -1]]])->assertStatus(422);
        // Asks for two different drinks out of one.
        $make(['groups' => [['name' => 'مشروب', 'min' => 2, 'max' => 2, 'allow_repeat' => false, 'options' => [['name' => 'كولا', 'price' => 0]]]]])->assertStatus(422);
        $this->assertSame(0, Product::count());

        // Only restaurants have meal options.
        [$clothes, $clothesCategory, $clothesHeaders] = $this->shop('clothing');
        $this->post("/api/v1/merchant/shops/{$clothes->id}/products", ['name' => 'X', 'price' => 10, 'category_id' => $clothesCategory->id, 'meal_options' => json_encode(['addons' => []])], $clothesHeaders)->assertStatus(422);
    }

    public function test_a_phone_shop_adds_a_device_with_storage_and_colour_options(): void
    {
        [$shop, $category, $headers] = $this->shop('electronics');
        $variants = [
            ['storage' => '128GB', 'ram' => '8GB', 'color' => '#111111', 'color_name' => 'أسود', 'price' => 3200, 'quantity' => 2, 'is_active' => true],
            ['storage' => '256GB', 'ram' => '8GB', 'color' => '#1565c0', 'color_name' => 'أزرق', 'price' => 3600, 'quantity' => 1, 'is_active' => true],
            ['storage' => '64GB', 'color' => '#ffffff', 'price' => 2500, 'quantity' => 5, 'is_active' => false],
        ];

        $this->getJson("/api/v1/merchant/shops/{$shop->id}/products", $headers)->assertOk()->assertJsonPath('can_create', true);
        $this->post("/api/v1/merchant/shops/{$shop->id}/products", ['name' => 'Galaxy', 'category_id' => $category->id], $headers)->assertStatus(422);
        $this->post("/api/v1/merchant/shops/{$shop->id}/products", ['name' => 'Galaxy', 'category_id' => $category->id, 'variants' => json_encode([['storage' => '128GB', 'color' => 'blue', 'price' => 1]])], $headers)->assertStatus(422);

        $id = $this->post("/api/v1/merchant/shops/{$shop->id}/products", ['name' => 'Galaxy', 'category_id' => $category->id, 'variants' => json_encode($variants)], $headers)
            ->assertCreated()
            ->assertJsonPath('product.variants.1.storage', '256GB')
            ->assertJsonCount(3, 'product.variants')
            ->json('product.id');

        $product = Product::findOrFail($id);
        // Starts at the cheapest option still for sale; stock is what the options for sale hold.
        $this->assertEquals(3200, $product->price);
        $this->assertSame(3, $product->quantity);
        $this->assertDatabaseHas('product_variants', ['product_id' => $id, 'storage' => '256GB', 'color' => '#1565c0', 'color_name' => 'أزرق', 'quantity' => 1]);

        // A sale price is checked against the device's real starting price.
        $this->post("/api/v1/merchant/products/{$id}", ['discount_price' => 3300], $headers)->assertStatus(422);
        $this->post("/api/v1/merchant/products/{$id}", ['discount_price' => 3000], $headers)->assertOk();

        // Saving without options keeps them; sending options replaces them.
        $this->assertSame(3, $product->variants()->count());
        // An option cheaper than the sale price would make the "discount" a price rise.
        $this->post("/api/v1/merchant/products/{$id}", ['variants' => json_encode([['storage' => '64GB', 'color' => '#222222', 'price' => 2900, 'quantity' => 1]])], $headers)->assertStatus(422);
        $this->assertSame(3, $product->variants()->count());
        $this->post("/api/v1/merchant/products/{$id}", ['discount_price' => '', 'variants' => json_encode([['storage' => '512GB', 'color' => '#222222', 'price' => 4100, 'quantity' => 1]])], $headers)->assertOk();
        $product->refresh();
        $this->assertSame(1, $product->variants()->count());
        $this->assertEquals(4100, $product->price);
        $this->assertSame(1, $product->quantity);
        $this->post("/api/v1/merchant/products/{$id}", ['variants' => json_encode([])], $headers)->assertStatus(422);
    }
}
