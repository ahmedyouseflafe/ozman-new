<?php

namespace Tests\Feature;

use App\Models\{Category, Product, ProductCampaign, ProductImage, Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/** The dashboard's product form with files: main photo, gallery, video and ad campaigns — added, changed, removed. */
class ProductMediaFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_product_with_photos_video_and_campaigns_through_its_whole_life(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $admin->id, 'name' => 'محل', 'slug' => 'm-'.Str::random(5), 'catalog_type' => 'general',
            'is_active' => true, 'is_accepting_orders' => true, 'whatsapp' => '972501234567']);
        $category = Category::create(['shop_id' => $shop->id, 'slug' => 'c-'.$shop->id, 'name' => 'قسم', 'is_active' => true]);

        $this->actingAs($admin)->post(route('products.store'), [
            'shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'كرسي', 'price' => 100, 'quantity' => 3, 'is_active' => 1,
            'main_image' => UploadedFile::fake()->image('main.jpg', 40, 40),
            'images' => [UploadedFile::fake()->image('g1.jpg', 20, 20), UploadedFile::fake()->image('g2.png', 20, 20)],
            'video' => UploadedFile::fake()->create('v.mp4', 100, 'video/mp4'),
            'campaigns' => [['title' => 'عرض الصيف', 'type' => 'image', 'media' => UploadedFile::fake()->image('ad.jpg', 30, 30)]],
        ])->assertSessionHasNoErrors();

        $product = Product::where('name', 'كرسي')->firstOrFail();
        $this->assertStringStartsWith('storage/products/main/', $product->main_image);
        Storage::disk('public')->assertExists(Str::after($product->main_image, 'storage/'));
        Storage::disk('public')->assertExists(Str::after($product->video, 'storage/'));
        $gallery = ProductImage::where('product_id', $product->id)->get();
        $this->assertCount(2, $gallery);
        $campaign = ProductCampaign::where('product_id', $product->id)->firstOrFail();
        $this->assertSame('عرض الصيف', $campaign->title);
        Storage::disk('public')->assertExists(Str::after($campaign->media, 'storage/'));

        // Change: new main photo (old one deleted), drop one gallery photo and the campaign, add a new campaign.
        $oldMain = $product->main_image;
        $oldGallery = $gallery->first();
        $oldCampaignMedia = $campaign->media;
        $this->actingAs($admin)->put(route('products.update', $product), [
            'shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'كرسي', 'price' => 90, 'quantity' => 3, 'is_active' => 1,
            'main_image' => UploadedFile::fake()->image('main2.jpg', 40, 40),
            'delete_image_ids' => [$oldGallery->id],
            'delete_campaign_ids' => [$campaign->id],
            'campaigns' => [['title' => 'عرض الشتاء', 'type' => 'image', 'media' => UploadedFile::fake()->image('ad2.jpg', 30, 30)]],
        ])->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertNotSame($oldMain, $product->main_image);
        Storage::disk('public')->assertMissing(Str::after($oldMain, 'storage/'));
        Storage::disk('public')->assertMissing(Str::after($oldGallery->image, 'storage/'));
        $this->assertSame(1, ProductImage::where('product_id', $product->id)->count());
        Storage::disk('public')->assertMissing(Str::after($oldCampaignMedia, 'storage/'));
        $this->assertSame(['عرض الشتاء'], ProductCampaign::where('product_id', $product->id)->pluck('title')->all());

        // Delete: every file goes.
        $files = array_merge([$product->main_image, $product->video], ProductImage::where('product_id', $product->id)->pluck('image')->all(),
            ProductCampaign::where('product_id', $product->id)->pluck('media')->all());
        $this->actingAs($admin)->delete(route('products.destroy', $product));
        $this->assertNull(Product::find($product->id));
        foreach (array_filter($files) as $file) {
            Storage::disk('public')->assertMissing(Str::after($file, 'storage/'));
        }
    }
}
