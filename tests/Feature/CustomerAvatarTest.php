<?php

namespace Tests\Feature;

use App\Models\PromoterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerAvatarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function signup(array $extra = []): array
    {
        return ['name' => 'Lina', 'email' => 'lina@example.com', 'password' => 'secret12', 'password_confirmation' => 'secret12', ...$extra];
    }

    public function test_community_registration_still_works_without_a_photo(): void
    {
        $this->post(route('community.register'), $this->signup())->assertRedirect(route('community.account'));
        $user = User::where('email', 'lina@example.com')->firstOrFail();
        $this->assertNull($user->avatar_path);
        $this->get(route('community.account'))->assertOk()->assertSee('class="customer-avatar is-large"', false)->assertSee('>L</span>', false);
    }

    public function test_registration_photo_is_cropped_to_a_small_square(): void
    {
        $this->post(route('community.register'), $this->signup(['avatar' => UploadedFile::fake()->image('me.jpg', 1600, 900)]))->assertRedirect();
        $path = User::where('email', 'lina@example.com')->value('avatar_path');
        $this->assertStringStartsWith('customers/avatars/', $path);
        Storage::disk('public')->assertExists($path);
        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame([400, 400], [$width, $height]);
        $this->get(route('community.account'))->assertSee('storage/'.$path, false);
    }

    public function test_follow_registration_accepts_an_optional_photo(): void
    {
        $profile = PromoterProfile::create([
            'user_id' => User::factory()->create(['role' => 'promoter', 'is_active' => true])->id,
            'slug' => 'sara', 'display_name' => 'Sara', 'is_active' => true,
        ]);
        $this->get(route('promoters.follow.account', $profile))->assertOk()->assertSee('enctype="multipart/form-data"', false)->assertSee('name="avatar"', false);
        $this->post(route('promoters.follow.register', $profile), $this->signup(['avatar' => UploadedFile::fake()->image('me.png', 300, 500)]))->assertRedirect();
        $user = User::where('email', 'lina@example.com')->firstOrFail();
        Storage::disk('public')->assertExists($user->avatar_path);
        $this->assertDatabaseHas('promoter_followers', ['user_id' => $user->id, 'promoter_profile_id' => $profile->id]);
    }

    public function test_wrong_type_or_oversized_photos_are_rejected_without_creating_the_account(): void
    {
        $this->from(route('community.login'))->post(route('community.register'), $this->signup(['avatar' => UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf')]))
            ->assertRedirect(route('community.login'))->assertSessionHasErrors(['avatar' => 'الصورة لازم تكون JPG أو PNG أو WebP.']);
        $this->post(route('community.register'), $this->signup(['avatar' => UploadedFile::fake()->image('big.jpg')->size(6000)]))
            ->assertSessionHasErrors(['avatar' => 'حجم الصورة لازم يكون 5 ميجا أو أقل.']);
        $this->assertDatabaseMissing('users', ['email' => 'lina@example.com']);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_customer_can_change_and_delete_their_photo(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->actingAs($user)->put(route('community.avatar.update'), ['avatar' => UploadedFile::fake()->image('a.jpg', 500, 500)])->assertRedirect();
        $first = $user->fresh()->avatar_path;
        Storage::disk('public')->assertExists($first);

        $this->put(route('community.avatar.update'), ['avatar' => UploadedFile::fake()->image('b.webp', 500, 500)])->assertSessionHas('status');
        $second = $user->fresh()->avatar_path;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        $this->put(route('community.avatar.update'))->assertSessionHasErrors('avatar');

        $this->delete(route('community.avatar.destroy'))->assertSessionHas('status');
        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($second);
    }

    public function test_avatar_controls_are_for_customers_only(): void
    {
        $promoter = User::factory()->create(['role' => 'promoter', 'is_active' => true]);
        $this->actingAs($promoter)->put(route('community.avatar.update'), ['avatar' => UploadedFile::fake()->image('a.jpg')])->assertForbidden();
        $this->delete(route('community.avatar.destroy'))->assertForbidden();
        // The header shows an initial for everyone; the photo upload panel is for customers only.
        $this->get(route('community.account'))->assertOk()->assertDontSee(route('community.avatar.update'), false);
    }
}
