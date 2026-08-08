<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Models\ClassSession;
use RuntimeException;

/** Controlled mutation rejection with safe authoritative context for transport adapters. */
final class SchedulingMutationException extends RuntimeException
{
    public function __construct(
        public readonly SchedulingDecisionCode $decisionCode,
        public readonly ?AvailabilityResult $availability = null,
        public readonly ?ClassSession $latest = null,
    ) {
        parent::__construct($decisionCode->value);
    }

    public static function stale(ClassSession $latest): self
    {
        return new self(SchedulingDecisionCode::StaleVersion, latest: $latest);
    }
}
