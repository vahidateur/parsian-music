<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use InvalidArgumentException;
use JsonSerializable;

/** Immutable result contract; evaluators add only authorized, safe details. */
final readonly class AvailabilityResult implements JsonSerializable
{
    /** @param array<string, mixed> $details */
    public function __construct(
        public AvailabilityState $state,
        public ScheduleProposal $proposal,
        public SchedulingDecisionCode $code,
        public array $details = [],
    ) {
        if (! $code->supports($state)) {
            throw new InvalidArgumentException('The scheduling decision code does not match its availability state.');
        }
    }

    public static function available(ScheduleProposal $proposal): self
    {
        return new self(AvailabilityState::Available, $proposal, SchedulingDecisionCode::Available);
    }

    /** @param array<string, mixed> $details */
    public static function conflict(ScheduleProposal $proposal, array $details = [], SchedulingDecisionCode $code = SchedulingDecisionCode::Conflict): self
    {
        return new self(AvailabilityState::Conflict, $proposal, $code, $details);
    }

    /** @param array<string, mixed> $details */
    public static function invalid(ScheduleProposal $proposal, array $details = [], SchedulingDecisionCode $code = SchedulingDecisionCode::Invalid): self
    {
        return new self(AvailabilityState::Invalid, $proposal, $code, $details);
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return ['state' => $this->state->value, 'code' => $this->code->value, 'proposal' => $this->proposal, 'details' => $this->details];
    }
}
