<?php

namespace App\Notifications;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationEventEnum;
use App\Enums\NotificationPriorityEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * @deprecated Since Sprint 19.3b — this class is no longer used.
 *
 * The notification engine was refactored to a driver pattern:
 *   - {@see \App\Contracts\Notifications\NotificationDriver}
 *   - {@see \App\Notifications\Drivers\DatabaseDriver} (InApp channel)
 *   - {@see \App\Notifications\Drivers\NullDriver}     (stub channels)
 *   - {@see \App\Services\NotificationService}         (entry point)
 *
 * This class is retained for reference only and will be removed in RC1.
 * Do NOT use or extend this class.
 */
class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly NotificationEventEnum    $event,
        private readonly array                    $payload,
        private readonly array                    $channels,
        private readonly NotificationPriorityEnum $priority,
    ) {
        $this->delay($priority->queueDelay());
    }

    // ── Routing ──────────────────────────────────────────────────────────────

    /**
     * Filter to only implemented channels so unbuilt drivers don't throw.
     *
     * @param  mixed  $notifiable
     * @return string[]
     */
    public function via(mixed $notifiable): array
    {
        return collect($this->channels)
            ->filter(fn (NotificationChannelEnum $ch) => $ch->isImplemented())
            ->map(fn (NotificationChannelEnum $ch) => $ch->driver())
            ->unique()
            ->values()
            ->all();
    }

    // ── Payloads ─────────────────────────────────────────────────────────────

    /** Stored in the `notifications` table (InApp channel). */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'event'    => $this->event->value,
            'label'    => $this->event->label(),
            'priority' => $this->priority->value,
            'payload'  => $this->payload,
        ];
    }

    /** Stub — implement when the Email settings page is wired to SMTP. */
    public function toMail(mixed $notifiable): null
    {
        return null;
    }

    // ── Accessors (for logging / testing) ────────────────────────────────────

    public function getEvent(): NotificationEventEnum
    {
        return $this->event;
    }

    public function getPriority(): NotificationPriorityEnum
    {
        return $this->priority;
    }
}
