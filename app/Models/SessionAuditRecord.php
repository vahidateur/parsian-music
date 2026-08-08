<?php

declare(strict_types=1);

namespace App\Models;

use LogicException;

/** Immutable scheduling-specific view of the shared append-only audit table. */
class SessionAuditRecord extends AuditRecord
{
    public const EVENT_TYPE = 'scheduling_session_mutation';

    protected static function booted(): void
    {
        static::updating(static function (): void {
            throw new LogicException('Session audit records are append-only.');
        });

        static::deleting(static function (): void {
            throw new LogicException('Session audit records are append-only.');
        });
    }
}
