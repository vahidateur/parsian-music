<?php

namespace App\Services;

/**
 * Central settings hub.
 *
 * Returns typed sub-services so call sites look like:
 *   settings()->institute()->name
 *   settings()->timezone()
 *   settings()->email()
 *   settings()->telegram()
 *
 * Every method delegates to a singleton, so the DB is hit once per request
 * per sub-service.  Add caching, multi-branch, or new sub-services here
 * without touching any call site.
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

    // ── Scalar shortcuts ─────────────────────────────────────────────────────
    // These read from config/env for now.
    // When an Email/Telegram settings table is added, swap the source here only.

    public function timezone(): string
    {
        return config('app.timezone', 'Asia/Tehran');
    }

    public function locale(): string
    {
        return config('app.locale', 'fa');
    }

    /**
     * SMTP / email settings.
     * Returns an array; extend to a typed EmailSettings service when the
     * Email settings page is persisted to the database.
     *
     * @return array{host:string,port:int,username:string,encryption:string,from_name:string,from_address:string}
     */
    public function email(): array
    {
        return [
            'host'         => config('mail.mailers.smtp.host', ''),
            'port'         => (int) config('mail.mailers.smtp.port', 587),
            'username'     => config('mail.mailers.smtp.username', ''),
            'encryption'   => config('mail.mailers.smtp.encryption', 'tls'),
            'from_name'    => config('mail.from.name', ''),
            'from_address' => config('mail.from.address', ''),
        ];
    }

    /**
     * Returns the notification channels enabled by the admin.
     *
     * Currently returns all channels (all enabled by default).
     * When the Notification settings page gains DB persistence, replace the
     * return value with a query on the settings table and wire up a
     * dedicated NotificationSettings sub-service, e.g.:
     *
     *   return $this->notificationSettings->enabledChannels();
     *
     * @return \App\Enums\NotificationChannelEnum[]
     */
    public function notificationChannels(): array
    {
        return \App\Enums\NotificationChannelEnum::cases();
    }

    /**
     * Telegram bot settings.
     * Swap with a DB-backed TelegramSettings service when that page is persisted.
     *
     * @return array{token:string,chat_id:string,enabled:bool}
     */
    public function telegram(): array
    {
        return [
            'token'   => config('services.telegram.token', ''),
            'chat_id' => config('services.telegram.chat_id', ''),
            'enabled' => (bool) config('services.telegram.enabled', false),
        ];
    }
}
