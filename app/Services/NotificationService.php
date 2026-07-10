<?php

namespace App\Services;

use App\Contracts\Notifications\NotificationDriver;
use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationEventEnum;
use App\Enums\NotificationPriorityEnum;
use Illuminate\Database\Eloquent\Model;

class NotificationService
{
    /**
     * Dispatch a notification through one or more channel drivers.
     *
     * Each channel resolves to its registered NotificationDriver via the
     * service container, so swapping a provider (e.g. FCM → OneSignal)
     * only requires a one-line change in AppServiceProvider — this class
     * never needs to be touched.
     *
     * @param  NotificationChannelEnum[]   $channels  Defaults to [InApp].
     */
    public function notify(
        NotificationEventEnum     $event,
        Model                     $notifiable,
        array                     $payload   = [],
        array                     $channels  = [],
        ?NotificationPriorityEnum $priority  = null,
    ): void {
        $channels = $channels ?: [NotificationChannelEnum::InApp];
        $priority = $priority ?? $event->defaultPriority();

        foreach ($channels as $channel) {
            $this->driver($channel)->send($event, $notifiable, $payload, $priority);
        }
    }

    /**
     * Same as notify() but respects only the channels enabled in the admin panel.
     * Falls back to [InApp] when settings()->notificationChannels() is empty.
     *
     * Wire up: settings()->notificationChannels() when the Notification settings
     * page is persisted to the database (Sprint 19.x).
     */
    public function notifyViaEnabledChannels(
        NotificationEventEnum     $event,
        Model                     $notifiable,
        array                     $payload   = [],
        ?NotificationPriorityEnum $priority  = null,
    ): void {
        $channels = settings()->notificationChannels();

        $this->notify($event, $notifiable, $payload, $channels, $priority);
    }

    // ── Internal ─────────────────────────────────────────────────────────────

    private function driver(NotificationChannelEnum $channel): NotificationDriver
    {
        return app("notification.driver.{$channel->value}");
    }
}
