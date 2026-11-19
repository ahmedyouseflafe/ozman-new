<?php

namespace Tests\Feature;

use App\Models\{ApiToken, Category, Product, Product3dJob, Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Http, Storage};
use Tests\TestCase;

/** Furniture "see it in your room": the owner's 3D model (uploaded or made from the photo) and the customer's AR page. */
class Product3dTest extends TestCase
{
    use RefreshDatabase;

    private function setUpShop(string $type = 'furniture_appliances'): array
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Home', 'slug' => 'home-'.uniqid(), 'catalog_type' => $type, 'is_active' => true]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Sofas', 'slug' => 'sofas-'.$shop->id]);
        $product = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'كنباية', 'slug' => 'sofa-'.uniqid(),
            'price' => 1500, 'is_active' => true, 'main_image' => 'products/sofa.jpg']);
        [, $token] = ApiToken::issue($owner);

        return [$owner, $shop, $product, ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json']];
    }

    private function glb(string $name = 'sofa.glb', string $head = 'glTF'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $head.str_repeat("\0", 200));
    }

    public function test_the_owner_uploads_a_model_sets_its_size_and_customers_get_the_ar_page(): void
    {
        [, $shop, $product, $h] = $this->setUpShop();
        $stranger = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        [, $strangerToken] = ApiToken::issue($stranger);

        $this->postJson("/api/v1/merchant/products/{$product->id}/model-3d", ['model' => $this->glb()], ['Authorization' => 'Bearer '.$strangerToken, 'Accept' => 'application/json'])->assertForbidden();
        $this->postJson("/api/v1/merchant/products/{$product->id}/model-3d", ['model' => $this->glb('photo.glb', 'JFIF')], $h)->assertUnprocessable();
        $this->postJson("/api/v1/merchant/products/{$product->id}/model-3d", ['model' => $this->glb()], $h)->assertOk()
            ->assertJsonPath('model_3d.ar_url', route('products.ar', $product));
        Storage::disk('public')->assertExists($product->fresh()->model_3d);
        $this->postJson("/api/v1/merchant/products/{$product->id}/model-3d/size", ['width' => 220, 'depth' => 90, 'height' => 85], $h)->assertOk()
            ->assertJsonPath('model_3d.size', ['w' => 220, 'd' => 90, 'h' => 85]);

        $this->get(route('products.ar', $product))->assertOk()->assertSee('model-viewer', false)->assertSee('220');
        $this->getJson("/api/v1/products/{$product->id}")->assertJsonPath('product.ar_url', route('products.ar', $product));
        $this->get($shop->publicUrl())->assertOk()->assertSee(route('products.ar', $product), false);

        $old = $product->fresh()->model_3d;
        $this->deleteJson("/api/v1/merchant/products/{$product->id}/model-3d", [], $h)->assertOk()->assertJsonPath('model_3d.model', null);
        Storage::disk('public')->assertMissing($old);
        $this->get(route('products.ar', $product))->assertNotFound();
    }

    public function test_a_model_made_from_the_photo_shows_only_after_the_owner_approves_it(): void
    {
        [, , $product, $h] = $this->setUpShop();
        $this->getJson("/api/v1/merchant/products/{$product->id}/model-3d", $h)->assertJsonPath('model_3d.can_convert', false);
        config(['services.meshy.key' => 'msy_test']);
        Http::fake([
            'api.meshy.ai/openapi/v1/image-to-3d' => Http::response(['result' => 'task_1']),
            'api.meshy.ai/openapi/v1/image-to-3d/task_1' => Http::sequence()
                ->push(['status' => 'IN_PROGRESS', 'progress' => 40])
                ->push(['status' => 'SUCCEEDED', 'progress' => 100, 'model_urls' => ['glb' => 'https://assets.meshy.ai/task_1/model.glb']]),
            'assets.meshy.ai/*' => Http::response('glTF'.str_repeat("\0", 100)),
        ]);

        $this->postJson("/api/v1/merchant/products/{$product->id}/model-3d/convert", [], $h)->assertOk()->assertJsonPath('model_3d.job.status', 'running');
        Http::assertSent(fn ($r) => $r->url() === 'https://api.meshy.ai/openapi/v1/image-to-3d' && str_contains($r->data()['image_url'], 'products/sofa.jpg'));
        $this->getJson("/api/v1/merchant/products/{$product->id}/model-3d", $h)->assertJsonPath('model_3d.job.progress', 40);
        $this->travel(10)->seconds();
        $job = $this->getJson("/api/v1/merchant/products/{$product->id}/model-3d", $h)->assertJsonPath('model_3d.job.status', 'ready')->json('model_3d.job');
        $this->assertNull($product->fresh()->model_3d);

        $this->postJson("/api/v1/merchant/product-3d-jobs/{$job['id']}/approve", [], $h)->assertOk()->assertJsonPath('model_3d.job', null);
        $this->assertSame(Product3dJob::find($job['id'])->result_path, $product->fresh()->model_3d);
        $this->get(route('products.ar', $product))->assertOk();
    }

    public function test_only_furniture_shops_get_3d_models(): void
    {
        [, , $product, $h] = $this->setUpShop('sweets');
        $this->getJson("/api/v1/merchant/products/{$product->id}/model-3d", $h)->assertJsonPath('model_3d.available', false);
        $this->postJson("/api/v1/merchant/products/{$product->id}/model-3d", ['model' => $this->glb()], $h)->assertUnprocessable();
    }

    public function test_the_dashboard_page_and_its_link_on_the_product_edit_page(): void
    {
        [$owner, , $product] = $this->setUpShop();
        $this->actingAs($owner)->get(route('products.edit', $product))->assertOk()->assertSee(route('products.model3d', $product), false);
        $this->actingAs($owner)->get(route('products.model3d', $product))->assertOk()->assertSee('model-viewer', false);
        $this->actingAs($owner)->post(route('products.model3d.upload', $product), ['model' => $this->glb()], ['Accept' => 'application/json'])->assertOk();
    }
}
