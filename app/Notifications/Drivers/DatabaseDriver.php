<?php

namespace App\Notifications\Drivers;

use App\Contracts\Notifications\NotificationDriver;
use App\Enums\NotificationEventEnum;
use App\Enums\NotificationPriorityEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Writes directly to the `notifications` table.
 *
 * Does not depend on the Notifiable trait — any Model can be the recipient.
 * Synchronous by design (a DB insert is cheap); priority-based delay is only
 * meaningful for external channels (Telegram, Email, etc.) which will dispatch
 * their own queued jobs when implemented.
 */
class DatabaseDriver implements NotificationDriver
{
    public function send(
        NotificationEventEnum    $event,
        Model                    $notifiable,
        array                    $payload,
        NotificationPriorityEnum $priority,
    ): void {
        DB::table('notifications')->insert([
            'id'              => (string) Str::uuid(),
            'type'            => $event->value,
            'notifiable_type' => $notifiable->getMorphClass(),
            'notifiable_id'   => $notifiable->getKey(),
            'data'            => json_encode([
                'event'    => $event->value,
                'label'    => $event->label(),
                'priority' => $priority->value,
                'payload'  => $payload,
            ]),
            'read_at'    => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
