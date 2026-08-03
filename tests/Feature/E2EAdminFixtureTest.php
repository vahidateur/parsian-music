<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class E2EAdminFixtureTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_environment_refuses_to_create_the_fixture(): void
    {
        config(['app.env' => 'production']);
        $this->setFixtureCredentials();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('allowed only in local, testing, or e2e environments');

        try {
            app(TestDataSeeder::class)->seedE2EAdmin();
        } finally {
            $this->assertSame(0, User::query()->count());
        }
    }

    public function test_repeated_seeding_is_idempotent_and_preserves_the_password_hash(): void
    {
        config(['app.env' => 'testing']);
        $password = $this->setFixtureCredentials();

        $first = app(TestDataSeeder::class)->seedE2EAdmin();
        $firstHash = (string) $first->getRawOriginal('password');
        $firstId = $first->id;

        $second = app(TestDataSeeder::class)->seedE2EAdmin();

        $this->assertSame($firstId, $second->id);
        $this->assertSame(1, User::query()->count());
        $this->assertSame($firstHash, (string) $second->getRawOriginal('password'));
        $this->assertTrue(Hash::check($password, (string) $second->getRawOriginal('password')));
    }

    public function test_email_owned_fixture_is_reconciled_when_its_phone_changed(): void
    {
        config(['app.env' => 'testing']);
        $this->setFixtureCredentials();

        $fixture = User::factory()->create([
            'full_name' => 'E2E Admin Fixture',
            'phone' => '09000000999',
            'email' => TestDataSeeder::E2E_EMAIL,
            'role' => RoleEnum::ADMIN,
            'is_active' => true,
        ]);

        $reconciled = app(TestDataSeeder::class)->seedE2EAdmin();

        $this->assertSame($fixture->id, $reconciled->id);
        $this->assertSame(1, User::query()->where('email', TestDataSeeder::E2E_EMAIL)->count());
        $this->assertSame(1, User::query()->where('phone', (string) env(TestDataSeeder::E2E_PHONE_ENV))->count());
        $this->assertSame(0, User::query()->where('phone', '09000000999')->count());
        $this->assertSame(RoleEnum::ADMIN, $reconciled->role);
        $this->assertTrue($reconciled->is_active);
    }

    public function test_canonical_email_owned_by_an_unrelated_user_fails_without_mutation(): void
    {
        config(['app.env' => 'testing']);
        $this->setFixtureCredentials();

        $unrelated = User::factory()->create([
            'full_name' => 'Existing Admin',
            'email' => TestDataSeeder::E2E_EMAIL,
            'role' => RoleEnum::ADMIN,
        ]);
        $before = (array) DB::table('users')->where('id', $unrelated->id)->first();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Canonical E2E admin fixture email belongs to an unrelated user');

        try {
            app(TestDataSeeder::class)->seedE2EAdmin();
        } finally {
            $after = (array) DB::table('users')->where('id', $unrelated->id)->first();
            $this->assertSame($before, $after);
            $this->assertSame(1, User::query()->count());
        }
    }

    public function test_fixture_has_the_admin_role_and_can_reach_the_browser_suite_surface(): void
    {
        config(['app.env' => 'testing']);
        $this->setFixtureCredentials();

        $fixture = app(TestDataSeeder::class)->seedE2EAdmin();

        $this->assertSame(RoleEnum::ADMIN, $fixture->role);
        $this->assertTrue($fixture->is_active);
        $this->actingAs($fixture)
            ->get(route('admin.students.index'))
            ->assertOk();
    }

    public function test_fixture_authenticates_through_the_application_login_contract(): void
    {
        config(['app.env' => 'testing']);
        $password = $this->setFixtureCredentials();
        $fixture = app(TestDataSeeder::class)->seedE2EAdmin();

        $this->post('/login', [
            'phone' => (string) env(TestDataSeeder::E2E_PHONE_ENV),
            'password' => $password,
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($fixture);
    }

    public function test_seeding_does_not_modify_an_unrelated_existing_user(): void
    {
        config(['app.env' => 'testing']);
        $this->setFixtureCredentials();

        $unrelated = User::factory()->create([
            'phone' => '091'.str_repeat('6', 8),
            'email' => 'unrelated@example.test',
        ]);
        $before = (array) DB::table('users')->where('id', $unrelated->id)->first();

        app(TestDataSeeder::class)->seedE2EAdmin();

        $after = (array) DB::table('users')->where('id', $unrelated->id)->first();
        $this->assertSame($before, $after);
    }

    public function test_missing_credential_configuration_fails_without_inventing_an_account(): void
    {
        config(['app.env' => 'testing']);
        $this->clearFixtureCredentials();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('TEST_ADMIN_PHONE and TEST_ADMIN_PASSWORD configuration');

        try {
            app(TestDataSeeder::class)->seedE2EAdmin();
        } finally {
            $this->assertSame(0, User::query()->count());
        }
    }

    /** @return string the in-process test password, never emitted */
    private function setFixtureCredentials(): string
    {
        $password = bin2hex(random_bytes(24));
        $this->setEnvironmentVariable(TestDataSeeder::E2E_PHONE_ENV, '09'.str_repeat('7', 9));
        $this->setEnvironmentVariable(TestDataSeeder::E2E_PASSWORD_ENV, $password);

        return $password;
    }

    private function clearFixtureCredentials(): void
    {
        foreach ([TestDataSeeder::E2E_PHONE_ENV, TestDataSeeder::E2E_PASSWORD_ENV] as $key) {
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }
    }

    private function setEnvironmentVariable(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key.'='.$value);
    }
}
