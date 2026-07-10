<?php

namespace App\Contracts\Notifications;

use App\Enums\NotificationEventEnum;
use App\Enums\NotificationPriorityEnum;
use Illuminate\Database\Eloquent\Model;

/**
 * Contract for all notification channel drivers.
 *
 * Each driver owns exactly one delivery channel.
 * Drivers may dispatch their own jobs internally for async delivery;
 * NotificationService does not need to know about that.
 */
interface NotificationDriver
{
    /**
     * Deliver the notification to the given notifiable.
     *
     * @param  array<string, mixed>  $payload
     */
    public function send(
        NotificationEventEnum    $event,
        Model                    $notifiable,
        array                    $payload,
        NotificationPriorityEnum $priority,
    ): void;
}
