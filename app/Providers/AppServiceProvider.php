<?php

namespace App\Providers;

use App\Enums\NotificationChannelEnum;
use App\Notifications\Drivers\DatabaseDriver;
use App\Notifications\Drivers\NullDriver;
use App\Services\InstituteSettings;
use App\Services\SettingsManager;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Settings singletons ───────────────────────────────────────────
        // One DB hit per request per sub-service; swap for Cache::remember() when Redis is available.
        $this->app->singleton(InstituteSettings::class);
        $this->app->singleton(SettingsManager::class);

        // ── Notification channel drivers ──────────────────────────────────
        // To swap a driver (e.g. replace NullDriver with TelegramDriver):
        //   change the binding below — NotificationService is never touched.
        $this->app->bind("notification.driver.{$this->ch(NotificationChannelEnum::InApp)}",    DatabaseDriver::class);
        $this->app->bind("notification.driver.{$this->ch(NotificationChannelEnum::Telegram)}", NullDriver::class);
        $this->app->bind("notification.driver.{$this->ch(NotificationChannelEnum::Email)}",    NullDriver::class);
        $this->app->bind("notification.driver.{$this->ch(NotificationChannelEnum::SMS)}",      NullDriver::class);
        $this->app->bind("notification.driver.{$this->ch(NotificationChannelEnum::Push)}",     NullDriver::class);
    }

    public function boot(): void
    {
        View::share('locale', session('locale', config('app.locale', 'en')));
    }

    private function ch(NotificationChannelEnum $channel): string
    {
        return $channel->value;
    }
}
