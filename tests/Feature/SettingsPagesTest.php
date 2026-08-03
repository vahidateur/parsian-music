<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\AppSetting;
use App\Models\User;
use App\Models\InstituteProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsPagesTest extends TestCase
{
    // Without this the test ran against the shared connection and left the
    // in-memory schema torn down for every suite that ran after it.
    use RefreshDatabase;

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

    public function test_settings_views_keep_script_free_owned_markup_and_working_day_alpine_binding(): void
    {
        $this->assertStringNotContainsString(
            '<script',
            File::get(resource_path('views/admin/settings/sections/login.blade.php')),
        );
        $this->assertStringNotContainsString(
            '<script',
            File::get(resource_path('views/admin/settings/sections/institute.blade.php')),
        );

        $admin = User::factory()->create([
            'role' => RoleEnum::ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.settings.show', 'institute'))
            ->assertOk()
            ->assertSee('x-data="settingsWorkingDays"', false)
            ->assertSee('x-model="selectedDays"', false)
            ->assertSee('name="working_days[]"', false);
    }

    public function test_login_logo_upload_persists_and_renders_without_preview_script(): void
    {
        Storage::fake('public');
        AppSetting::flushCache();

        $admin = User::factory()->create([
            'role' => RoleEnum::ADMIN,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.settings.update', 'login'), [
            'login_logo'  => UploadedFile::fake()->image('login-logo.png'),
            'login_title' => 'عنوان صفحه ورود',
        ]);

        $response->assertRedirect(route('admin.settings.show', 'login'));

        $path = AppSetting::getValue('login', 'login_logo');
        $this->assertIsString($path);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($admin)
            ->get(route('admin.settings.show', 'login'))
            ->assertOk()
            ->assertSee(Storage::url($path), false);
    }

    public function test_institute_update_preserves_empty_days_hours_and_existing_upload(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => RoleEnum::ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.settings.institute.update'), [
            'name'               => 'آموزشگاه پارسیان',
            'logo'               => UploadedFile::fake()->image('institute-logo.png'),
            'working_hours_from' => '09:00',
            'working_hours_to'   => '17:00',
        ])->assertRedirect(route('admin.settings.show', 'institute'));

        $profile = InstituteProfile::instance()->fresh();
        $storedLogo = $profile->logo_path;

        $this->assertSame([], $profile->working_days);
        $this->assertSame('09:00', $profile->working_hours_from);
        $this->assertSame('17:00', $profile->working_hours_to);
        $this->assertNotNull($storedLogo);
        Storage::disk('public')->assertExists($storedLogo);

        $this->actingAs($admin)->post(route('admin.settings.institute.update'), [
            'name'               => 'آموزشگاه پارسیان',
            'working_days'       => ['monday', 'friday'],
            'working_hours_from' => '10:00',
            'working_hours_to'   => '18:00',
        ])->assertRedirect(route('admin.settings.show', 'institute'));

        $profile->refresh();
        $this->assertSame(['monday', 'friday'], $profile->working_days);
        $this->assertSame($storedLogo, $profile->logo_path);
    }

    public function test_persisted_settings_credentials_are_never_rendered_in_html(): void
    {
        AppSetting::flushCache();
        AppSetting::setGroup('email', ['mail_password' => 'MAIL-PERSISTED-MARKER']);
        AppSetting::setGroup('telegram', ['telegram_bot_token' => 'TELEGRAM-PERSISTED-MARKER']);

        $admin = User::factory()->create([
            'role' => RoleEnum::ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.settings.show', 'email'))
            ->assertOk()
            ->assertSee('<input id="mail_password" type="password" name="mail_password"', false)
            ->assertSee('فیلد را خالی بگذارید تا رمز قبلی حفظ شود.')
            ->assertDontSee('MAIL-PERSISTED-MARKER');

        $this->actingAs($admin)
            ->get(route('admin.settings.show', 'telegram'))
            ->assertOk()
            ->assertSee('<input id="telegram_bot_token" type="password" name="telegram_bot_token"', false)
            ->assertSee('برای حفظ توکن فعلی، این فیلد را خالی بگذارید.')
            ->assertDontSee('TELEGRAM-PERSISTED-MARKER');
    }

    public function test_blank_credential_updates_preserve_secrets_and_apply_non_secret_settings(): void
    {
        AppSetting::flushCache();
        AppSetting::setGroup('email', [
            'mail_host' => 'smtp.previous.test',
            'mail_password' => 'MAIL-PERSISTED-MARKER',
        ]);
        AppSetting::setGroup('telegram', [
            'telegram_bot_token' => 'TELEGRAM-PERSISTED-MARKER',
            'telegram_enabled' => false,
        ]);

        $admin = User::factory()->create([
            'role' => RoleEnum::ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->put(route('admin.settings.update', 'email'), [
            'mail_host' => 'smtp.updated.test',
            'mail_port' => 2525,
            'mail_username' => 'updated-user',
            'mail_password' => '',
            'mail_encryption' => 'tls',
            'mail_from_name' => 'Parsian Music',
            'mail_from_address' => 'mailer@example.test',
        ])->assertRedirect(route('admin.settings.show', 'email'));

        $this->actingAs($admin)->put(route('admin.settings.update', 'telegram'), [
            'telegram_bot_token' => '',
            'telegram_chat_id' => '-1001234567890',
            'telegram_enabled' => '1',
        ])->assertRedirect(route('admin.settings.show', 'telegram'));

        $email = AppSetting::getGroup('email');
        $telegram = AppSetting::getGroup('telegram');

        $this->assertSame('MAIL-PERSISTED-MARKER', $email['mail_password']);
        $this->assertSame('smtp.updated.test', $email['mail_host']);
        $this->assertSame(2525, $email['mail_port']);
        $this->assertSame('TELEGRAM-PERSISTED-MARKER', $telegram['telegram_bot_token']);
        $this->assertSame('-1001234567890', $telegram['telegram_chat_id']);
        $this->assertTrue($telegram['telegram_enabled']);
    }

    public function test_submitted_settings_credentials_persist_without_being_rendered_afterward(): void
    {
        AppSetting::flushCache();

        $admin = User::factory()->create([
            'role' => RoleEnum::ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->put(route('admin.settings.update', 'email'), [
            'mail_password' => 'MAIL-NEW-MARKER',
        ])->assertRedirect(route('admin.settings.show', 'email'));

        $this->actingAs($admin)->put(route('admin.settings.update', 'telegram'), [
            'telegram_bot_token' => 'TELEGRAM-NEW-MARKER',
        ])->assertRedirect(route('admin.settings.show', 'telegram'));

        $this->assertSame('MAIL-NEW-MARKER', AppSetting::getValue('email', 'mail_password'));
        $this->assertSame('TELEGRAM-NEW-MARKER', AppSetting::getValue('telegram', 'telegram_bot_token'));

        $this->actingAs($admin)
            ->get(route('admin.settings.show', 'email'))
            ->assertOk()
            ->assertDontSee('MAIL-NEW-MARKER');

        $this->actingAs($admin)
            ->get(route('admin.settings.show', 'telegram'))
            ->assertOk()
            ->assertDontSee('TELEGRAM-NEW-MARKER');
    }

    public function test_generic_settings_updates_and_route_authorization_remain_unchanged(): void
    {
        $this->get(route('admin.settings.show', 'general'))->assertRedirect(route('login'));

        $student = User::factory()->create([
            'role' => RoleEnum::STUDENT,
            'is_active' => true,
        ]);
        $this->actingAs($student)
            ->put(route('admin.settings.update', 'general'), [])
            ->assertForbidden();

        AppSetting::flushCache();
        $admin = User::factory()->create([
            'role' => RoleEnum::ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->put(route('admin.settings.update', 'general'), [
            'app_name' => 'آموزشگاه به‌روزشده',
            'locale' => 'fa',
            'timezone' => 'Asia/Tehran',
            'date_format' => 'jalali',
            'week_start' => 'saturday',
            'per_page' => 20,
            'session_default_duration' => 75,
        ])->assertRedirect(route('admin.settings.show', 'general'));

        $settings = AppSetting::getGroup('general');
        $this->assertSame('آموزشگاه به‌روزشده', $settings['app_name']);
        $this->assertSame(20, $settings['per_page']);
        $this->assertSame(75, $settings['session_default_duration']);
    }

}
