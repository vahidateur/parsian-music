<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleEnum;
use App\Mail\PasswordResetTokenMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Production contract (App\Http\Controllers\Auth\PhonePasswordResetController):
 *  - Reset is phone-based; tokens live in `phone_password_resets` (sha256-hashed, single use, 15 min TTL).
 *  - Routes: password.phone.request / password.phone.send / password.phone.reset.form / password.phone.reset.
 *  - Only super_admin, admin and teacher roles may self-service reset; students are excluded.
 *  - The response is always generic to prevent phone enumeration.
 *  - Laravel's email-based ResetPassword notification flow is NOT used.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function insertToken(string $phone, string $plainToken, ?array $overrides = null): void
    {
        DB::table('phone_password_resets')->insert(array_merge([
            'phone' => $phone,
            'token' => hash('sha256', $plainToken),
            'created_at' => now(),
            'used' => false,
        ], $overrides ?? []));
    }

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $this->get(route('password.phone.request'))->assertStatus(200);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $this->get(route('password.phone.reset.form', ['phone' => '09121112233', 'token' => Str::random(64)]))
            ->assertStatus(200);
    }

    public function test_phone_is_required_to_request_a_reset(): void
    {
        $this->post(route('password.phone.send'), [])
            ->assertSessionHasErrors('phone');
    }

    public function test_reset_token_is_generated_and_mailed_for_an_allowed_role(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'phone' => '09121112233',
            'role' => RoleEnum::ADMIN->value,
        ]);

        $response = $this->post(route('password.phone.send'), ['phone' => '09121112233']);

        $response->assertSessionHas('status');
        $this->assertSame(1, DB::table('phone_password_resets')->where('phone', '09121112233')->count());
        Mail::assertSent(PasswordResetTokenMail::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_students_can_not_request_a_reset_but_the_response_is_generic(): void
    {
        User::factory()->create([
            'phone' => '09121112244',
            'role' => RoleEnum::STUDENT->value,
        ]);

        $response = $this->post(route('password.phone.send'), ['phone' => '09121112244']);

        $response->assertSessionHas('status');
        $response->assertSessionHasNoErrors();
        $this->assertSame(0, DB::table('phone_password_resets')->count());
    }

    public function test_unknown_phone_does_not_reveal_account_existence(): void
    {
        $response = $this->post(route('password.phone.send'), ['phone' => '09990000000']);

        $response->assertSessionHas('status');
        $response->assertSessionHasNoErrors();
        $this->assertSame(0, DB::table('phone_password_resets')->count());
    }

    public function test_requests_are_rate_limited_per_phone(): void
    {
        User::factory()->create(['phone' => '09121112255']);

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('password.phone.send'), ['phone' => '09121112255'])
                ->assertSessionHasNoErrors();
        }

        $this->post(route('password.phone.send'), ['phone' => '09121112255'])
            ->assertSessionHasErrors('phone');
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        $user = User::factory()->create([
            'phone' => '09121112266',
            'force_password_change' => true,
        ]);

        $token = Str::random(64);
        $this->insertToken('09121112266', $token);

        $response = $this->post(route('password.phone.reset'), [
            'phone' => '09121112266',
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertFalse((bool) $user->force_password_change);
        $this->assertTrue((bool) DB::table('phone_password_resets')->where('phone', '09121112266')->value('used'));
    }

    public function test_token_is_single_use(): void
    {
        User::factory()->create(['phone' => '09121112277']);

        $token = Str::random(64);
        $this->insertToken('09121112277', $token);

        $payload = [
            'phone' => '09121112277',
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ];

        $this->post(route('password.phone.reset'), $payload)->assertSessionHasNoErrors();

        $this->post(route('password.phone.reset'), $payload)
            ->assertSessionHasErrors('token');
    }

    public function test_expired_token_is_rejected(): void
    {
        User::factory()->create(['phone' => '09121112288']);

        $token = Str::random(64);
        $this->insertToken('09121112288', $token, ['created_at' => now()->subMinutes(20)]);

        $this->post(route('password.phone.reset'), [
            'phone' => '09121112288',
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('token');
    }

    public function test_reset_requires_a_confirmed_password_of_at_least_eight_characters(): void
    {
        User::factory()->create(['phone' => '09121112299']);

        $token = Str::random(64);
        $this->insertToken('09121112299', $token);

        $this->post(route('password.phone.reset'), [
            'phone' => '09121112299',
            'token' => $token,
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ])->assertSessionHasErrors('password');
    }

    public function test_students_can_not_reset_even_with_a_valid_token(): void
    {
        $user = User::factory()->create([
            'phone' => '09121113300',
            'role' => RoleEnum::STUDENT->value,
        ]);

        $token = Str::random(64);
        $this->insertToken('09121113300', $token);

        $this->post(route('password.phone.reset'), [
            'phone' => '09121113300',
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('phone');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }
}
