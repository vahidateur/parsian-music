<?php

namespace App\Enums;

enum NotificationChannelEnum: string
{
    case InApp    = 'in_app';
    case Email    = 'email';
    case Telegram = 'telegram';
    case Push     = 'push';
    case SMS      = 'sms';

    /**
     * @deprecated Since Sprint 19.3b — the driver is now resolved via the service container.
     *
     * The new architecture uses app("notification.driver.{$channel->value}") in
     * {@see \App\Services\NotificationService}. This method was only consumed by
     * the deprecated {@see \App\Notifications\AppNotification::via()}.
     *
     * Retained until AppNotification is deleted in RC1.
     */
    public function driver(): ?string
    {
        return match ($this) {
            self::InApp    => 'database',
            self::Email    => 'mail',
            self::Telegram => null,
            self::Push     => null,
            self::SMS      => null,
        };
    }

    /**
     * @deprecated Since Sprint 19.3b — see driver() above.
     */
    public function isImplemented(): bool
    {
        return $this->driver() !== null;
    }

    public function label(): string
    {
        return match ($this) {
            self::InApp    => 'درون‌برنامه',
            self::Email    => 'ایمیل',
            self::Telegram => 'تلگرام',
            self::Push     => 'پوش',
            self::SMS      => 'پیامک',
        };
    }

}
