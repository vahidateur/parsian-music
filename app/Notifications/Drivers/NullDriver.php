<?php

namespace App\Notifications\Drivers;

use App\Contracts\Notifications\NotificationDriver;
use App\Enums\NotificationEventEnum;
use App\Enums\NotificationPriorityEnum;
use Illuminate\Database\Eloquent\Model;

/**
 * Stub driver for channels not yet implemented (Telegram, Email, SMS, Push).
 *
 * Logs the intent so developers can verify the pipeline without real delivery.
 * Swap this binding in AppServiceProvider with a real driver when the channel is ready:
 *
 *   $this->app->bind("notification.driver.telegram", TelegramDriver::class);
 */
class NullDriver implements NotificationDriver
{
    public function send(
        NotificationEventEnum    $event,
        Model                    $notifiable,
        array                    $payload,
        NotificationPriorityEnum $priority,
    ): void {
        logger()->info('[NullDriver] notification not sent — channel not yet implemented.', [
            'event'          => $event->value,
            'notifiable'     => $notifiable::class . '#' . $notifiable->getKey(),
            'priority'       => $priority->value,
        ]);
    }
}
