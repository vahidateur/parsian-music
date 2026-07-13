<?php

namespace App\Services;

use App\Models\AppSetting;

/**
 * Central settings hub.
 *
 * Public API (unchanged — call sites need no modification):
 *   settings()->institute()->name
 *   settings()->general()
 *   settings()->timezone()
 *   settings()->locale()
 *   settings()->email()
 *   settings()->telegram()
 *   settings()->notificationChannels()
 *
 * Storage backend: all settings are now persisted in the `app_settings` DB table
 * via AppSetting::getGroup() / AppSetting::setGroup().  AppSetting applies a
 * request-level in-memory cache so the DB is hit at most once per group per request.
 * Config values are used as fallbacks when a group has no DB row yet.
 *
 * Adding a new settings group:
 *   1. Add a partial view in resources/views/admin/settings/sections/{group}.blade.php
 *   2. Register the group in config/settings.php
 *   3. Add a typed accessor method below (optional but recommended for autocomplete).
 */
class SettingsManager
{
    public function __construct(
        private readonly InstituteSettings $institute,
    ) {}

    // ── Sub-services ─────────────────────────────────────────────────────────

    public function institute(): InstituteSettings
    {
        return $this->institute;
    }

    // ── General ──────────────────────────────────────────────────────────────

    /**
     * All general/system settings as an array.
     * Falls back gracefully to sensible defaults when the DB row doesn't exist yet.
     */
    public function general(): array
    {
        $db = AppSetting::getGroup('general');

        return array_merge([
            'app_name'                 => config('app.name', 'آموزشگاه موسیقی پارسیان'),
            'locale'                   => config('app.locale', 'fa'),
            'timezone'                 => config('app.timezone', 'Asia/Tehran'),
            'date_format'              => 'jalali',
            'week_start'               => 'saturday',
            'per_page'                 => 15,
            'session_default_duration' => 60,
        ], $db);
    }

    // ── Scalar shortcuts ──────────────────────────────────────────────────────

    public function timezone(): string
    {
        return AppSetting::getValue('general', 'timezone', config('app.timezone', 'Asia/Tehran'));
    }

    public function locale(): string
    {
        return AppSetting::getValue('general', 'locale', config('app.locale', 'fa'));
    }

    public function appName(): string
    {
        return AppSetting::getValue('general', 'app_name', config('app.name', 'آموزشگاه موسیقی پارسیان'));
    }

    // ── Email ─────────────────────────────────────────────────────────────────

    /**
     * SMTP / email settings — DB values override .env/config.
     *
     * @return array{host:string,port:int,username:string,encryption:string,from_name:string,from_address:string}
     */
    public function email(): array
    {
        $db = AppSetting::getGroup('email');

        return [
            'host'         => $db['mail_host']         ?? config('mail.mailers.smtp.host', ''),
            'port'         => (int) ($db['mail_port']  ?? config('mail.mailers.smtp.port', 587)),
            'username'     => $db['mail_username']     ?? config('mail.mailers.smtp.username', ''),
            'encryption'   => $db['mail_encryption']   ?? config('mail.mailers.smtp.encryption', 'tls'),
            'from_name'    => $db['mail_from_name']    ?? config('mail.from.name', ''),
            'from_address' => $db['mail_from_address'] ?? config('mail.from.address', ''),
        ];
    }

    // ── Telegram ──────────────────────────────────────────────────────────────

    /**
     * Telegram bot settings — DB values override .env/config.
     *
     * @return array{token:string,chat_id:string,enabled:bool}
     */
    public function telegram(): array
    {
        $db = AppSetting::getGroup('telegram');

        return [
            'token'   => $db['telegram_bot_token'] ?? config('services.telegram.token', ''),
            'chat_id' => $db['telegram_chat_id']   ?? config('services.telegram.chat_id', ''),
            'enabled' => (bool) ($db['telegram_enabled'] ?? config('services.telegram.enabled', false)),
        ];
    }

    // ── Notifications ─────────────────────────────────────────────────────────

    /**
     * Returns the notification channels enabled by the admin.
     *
     * Reads from the DB notifications group first.
     * Falls back to all channels when no preference has been saved.
     *
     * @return \App\Enums\NotificationChannelEnum[]
     */
    public function notificationChannels(): array
    {
        $db       = AppSetting::getGroup('notifications');
        $saved    = $db['channels'] ?? [];

        if (empty($saved)) {
            return \App\Enums\NotificationChannelEnum::cases();
        }

        return collect(\App\Enums\NotificationChannelEnum::cases())
            ->filter(fn ($ch) => in_array(strtolower($ch->value), $saved))
            ->values()
            ->all();
    }

    /**
     * Returns the notification events that have been enabled by the admin.
     *
     * @return string[]  e.g. ['session_reminder', 'enrollment_created']
     */
    public function enabledNotificationEvents(): array
    {
        $db = AppSetting::getGroup('notifications');

        return $db['events'] ?? [
            'session_reminder',
            'session_cancelled',
            'enrollment_created',
            'attendance_recorded',
        ];
    }

    // ── Login Page ────────────────────────────────────────────────────────────

    /**
     * Login page settings — customizable from admin panel.
     *
     * @return array
     */
    public function login(): array
    {
        $db = AppSetting::getGroup('login');

        return array_merge([
            'logo'                       => null,
            'title'                      => 'آموزشگاه موسیقی پارسیان',
            'subtitle'                   => 'تالار هنر، جادو و موسیقی',
            'title_en'                   => 'PARSIAN MUSIC',
            'academy_name'               => null,
            'divider_text'               => 'فرم ورود',
            'phone_placeholder'          => 'شماره موبایل',
            'password_placeholder'       => 'رمز عبور',
            'button_text'                => 'ورود به تالار',
            'forgot_password_text'       => 'فراموشی رمز عبور؟',
            'show_password_label'        => 'نمایش رمز عبور',
            'hide_password_label'        => 'مخفی کردن رمز عبور',
            'quote'                      => '«موسیقی جادوی بی‌کلام است»',
            'copyright'                  => 'Parsian Music Academy. All rights reserved.',
            'english_text'               => 'PARSIAN MUSIC',
        ], array_combine(
            array_map(fn ($k) => str_replace('login_', '', $k), array_keys($db)),
            array_values($db)
        ) ?: []);
    }
}
