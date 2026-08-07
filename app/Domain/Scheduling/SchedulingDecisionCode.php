<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

/** Stable, transport-neutral reasons associated with an availability state. */
enum SchedulingDecisionCode: string
{
    case Available = 'AVAILABLE';
    case Conflict = 'CONFLICT';
    case Invalid = 'INVALID';
    case HardConstraint = 'HARD_CONSTRAINT';
    case UnauthorizedOverride = 'UNAUTHORIZED_OVERRIDE';
    case StaleVersion = 'STALE_VERSION';

    public function supports(AvailabilityState $state): bool
    {
        return match ($this) {
            self::Available => $state === AvailabilityState::Available,
            self::Conflict => $state === AvailabilityState::Conflict,
            self::Invalid, self::UnauthorizedOverride, self::StaleVersion => $state === AvailabilityState::Invalid,
            self::HardConstraint => in_array($state, [AvailabilityState::Conflict, AvailabilityState::Invalid], true),
        };
    }
}
