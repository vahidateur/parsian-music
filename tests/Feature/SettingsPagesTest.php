<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsPagesTest extends TestCase
{
    public function test_settings_index_and_show_pages_load_for_admin(): void
    {
        $admin = User::factory()->create([
            'role' => RoleEnum::ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('/admin/settings/general', false)
            ->assertDontSee('/admin/settings/0', false);

        foreach (array_keys(config('settings.catalogue')) as $section) {
            $this->actingAs($admin)
                ->get("/admin/settings/{$section}")
                ->assertOk();
        }
    }
}
