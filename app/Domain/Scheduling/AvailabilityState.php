<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

/** The only possible outcome categories for a scheduling decision. */
enum AvailabilityState: string
{
    case Available = 'AVAILABLE';
    case Conflict = 'CONFLICT';
    case Invalid = 'INVALID';
}
