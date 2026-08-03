<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class UserManagementCredentialRegressionTest extends TestCase
{
    public function test_users_index_does_not_render_credentials_from_session(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->withSession([
                'temp_password' => 'TEMP-PASSWORD-MARKER',
                'api_token' => 'API-TOKEN-MARKER',
                'credential' => 'CREDENTIAL-MARKER',
            ])
            ->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertDontSee('TEMP-PASSWORD-MARKER');
        $response->assertDontSee('API-TOKEN-MARKER');
        $response->assertDontSee('CREDENTIAL-MARKER');
        $response->assertDontSee('<code', false);
    }

    public function test_user_listing_remains_available_and_renders_existing_records(): void
    {
        $admin = User::factory()->admin()->create(['full_name' => 'فهرست مدیر']);
        User::factory()->create([
            'full_name' => 'کاربر موجود',
            'phone' => '09120000001',
            'role' => RoleEnum::TEACHER,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('مدیریت کاربران')
            ->assertSee('کاربر موجود')
            ->assertSee('09120000001')
            ->assertSee('لیست کاربران')
            ->assertSee('aria-label="لیست کاربران"', false);
    }

    public function test_user_authorization_boundary_remains_unchanged(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));

        $student = User::factory()->student()->create();
        $this->actingAs($student)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.users.create'))->assertForbidden();
    }

    public function test_authorized_user_creation_still_persists_the_requested_role(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'full_name' => 'کاربر تازه',
            'phone' => '09120000002',
            'email' => 'new-user@example.test',
            'role' => RoleEnum::TEACHER->value,
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);

        $response->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $created = User::query()->where('phone', '09120000002')->firstOrFail();
        $this->assertSame(RoleEnum::TEACHER, $created->role);
        $this->assertTrue(Hash::check('StrongPass123!', $created->password));
        $this->assertSame($admin->id, $created->created_by);
    }

    public function test_role_allow_list_and_target_permissions_remain_unchanged(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->create(['role' => RoleEnum::TEACHER]);
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('value="teacher"', false)
            ->assertSee('value="student"', false)
            ->assertDontSee('value="super_admin"', false)
            ->assertDontSee('value="admin"', false);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'full_name' => 'تلاش ارتقای دسترسی',
                'phone' => '09120000003',
                'role' => RoleEnum::SUPER_ADMIN->value,
                'password' => 'StrongPass123!',
                'password_confirmation' => 'StrongPass123!',
            ])
            ->assertSessionHasErrors('role');

        $this->actingAs($admin)->get(route('admin.users.edit', $teacher))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.edit', $superAdmin))->assertForbidden();
    }
}
