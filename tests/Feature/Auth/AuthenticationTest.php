<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleEnum;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Production contract (App\Modules\Auth\Controllers\AuthController + routes/auth.php):
 *  - Login is phone + password (no email login, no `name` field).
 *  - Successful login redirects per role (admin/super_admin → admin.dashboard,
 *    teacher → /teacher/dashboard, student → /student/dashboard).
 *  - Only `is_active` users may authenticate.
 *  - 3 failed attempts lock the account for 30 minutes (User::incrementLoginAttempts).
 *  - Every attempt is written to login_logs.
 *  - Logout invalidates the session and redirects to /login.
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_with_phone_and_password(): void
    {
        $user = User::factory()->create(['phone' => '09121112233']);

        $response = $this->post('/login', [
            'phone' => '09121112233',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_successful_login_is_logged_and_resets_attempt_counter(): void
    {
        $user = User::factory()->create([
            'phone' => '09121112244',
            'login_attempts' => 2,
        ]);

        $this->post('/login', [
            'phone' => '09121112244',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $this->assertSame(0, (int) $user->refresh()->login_attempts);
        $this->assertDatabaseHas('login_logs', [
            'user_id' => $user->id,
            'success' => true,
        ]);
    }

    public function test_login_redirects_student_to_the_student_dashboard(): void
    {
        User::factory()->create([
            'phone' => '09121112255',
            'role' => RoleEnum::STUDENT->value,
        ]);

        $response = $this->post('/login', [
            'phone' => '09121112255',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/student/dashboard');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create(['phone' => '09121112266']);

        $response = $this->post('/login', [
            'phone' => '09121112266',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('phone');
        $this->assertSame(1, (int) $user->refresh()->login_attempts);
        $this->assertDatabaseHas('login_logs', [
            'user_id' => $user->id,
            'success' => false,
        ]);
    }

    public function test_phone_and_password_are_required(): void
    {
        $response = $this->post('/login', []);

        $this->assertGuest();
        $response->assertSessionHasErrors(['phone', 'password']);
    }

    public function test_inactive_users_can_not_authenticate(): void
    {
        User::factory()->inactive()->create(['phone' => '09121112277']);

        $response = $this->post('/login', [
            'phone' => '09121112277',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('phone');
    }

    public function test_account_is_locked_after_three_failed_attempts(): void
    {
        $user = User::factory()->create(['phone' => '09121112288']);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', [
                'phone' => '09121112288',
                'password' => 'wrong-password',
            ]);
        }

        $user->refresh();
        $this->assertNotNull($user->locked_until);

        // Even the correct password is rejected while the lock is active.
        $response = $this->post('/login', [
            'phone' => '09121112288',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('phone');
    }

    public function test_users_can_logout_and_the_session_is_invalidated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    /**
     * Regression guard: the Breeze scaffold routes were intentionally removed.
     * User accounts are created by admins (admin.users.*), not by self-registration,
     * and there is no email verification / password confirmation flow.
     */
    public function test_scaffold_auth_routes_are_absent(): void
    {
        $this->assertFalse(Route::has('register'));
        $this->assertFalse(Route::has('verification.notice'));
        $this->assertFalse(Route::has('verification.verify'));
        $this->assertFalse(Route::has('password.confirm'));

        $this->get('/register')->assertNotFound();
        $this->actingAs(User::factory()->create())->get('/confirm-password')->assertNotFound();
    }

    public function test_failed_attempt_for_unknown_phone_is_logged_without_a_user(): void
    {
        $response = $this->post('/login', [
            'phone' => '09990000000',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('phone');
        $this->assertSame(1, LoginLog::where('success', false)->whereNull('user_id')->count());
    }
}
