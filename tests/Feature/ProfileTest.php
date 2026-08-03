<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Production contract (ProfileController + ProfileUpdateRequest + routes/web.php):
 *  - Canonical name column is `full_name`; there is no `name` and no `email_verified_at`.
 *  - PATCH /profile handles two sections via `_section`: "info" (default) and "avatar".
 *  - info: full_name required; phone/email nullable + unique; locale in fa|en; timezone valid.
 *  - avatar: image (jpg/jpeg/png/webp, max 2MB) stored on the public disk under avatars/.
 *  - Both redirect to profile.edit with status=profile-updated.
 *  - DELETE /profile requires the current password, logs out and redirects to /.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('profile.edit'))->assertOk();
    }

    public function test_guests_can_not_view_the_profile_page(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'full_name' => 'کاربر تست',
                'phone' => '09121119988',
                'email' => 'test@example.com',
                'locale' => 'en',
                'timezone' => 'Asia/Tehran',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'profile-updated')
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('کاربر تست', $user->full_name);
        $this->assertSame('09121119988', $user->phone);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('en', $user->locale);
        $this->assertSame('Asia/Tehran', $user->timezone);
    }

    public function test_full_name_is_required(): void
    {
        $user = User::factory()->create(['full_name' => 'اصلی']);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), ['full_name' => ''])
            ->assertSessionHasErrors('full_name');

        $this->assertSame('اصلی', $user->refresh()->full_name);
    }

    public function test_phone_must_be_unique_across_users(): void
    {
        $other = User::factory()->create(['phone' => '09121110000']);
        $user = User::factory()->create(['phone' => '09121112222']);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'full_name' => $user->full_name,
                'phone' => $other->phone,
            ])
            ->assertSessionHasErrors('phone');

        $this->assertSame('09121112222', $user->refresh()->phone);
    }

    public function test_user_can_keep_their_own_phone_and_email(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'full_name' => 'همان کاربر',
                'phone' => $user->phone,
                'email' => $user->email,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('همان کاربر', $user->refresh()->full_name);
    }

    public function test_avatar_can_be_uploaded(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                '_section' => 'avatar',
                'avatar' => UploadedFile::fake()->create('avatar.jpg', 64, 'image/jpeg'),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
    }

    public function test_non_image_avatar_is_rejected(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                '_section' => 'avatar',
                'avatar' => UploadedFile::fake()->create('resume.pdf', 32, 'application/pdf'),
            ])
            ->assertSessionHasErrors('avatar');

        $this->assertNull($user->refresh()->avatar_path);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'password']);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), ['password' => 'wrong-password']);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->fresh());
    }
}
