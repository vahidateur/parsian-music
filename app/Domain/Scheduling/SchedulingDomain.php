<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\DTOs\SessionDisplayData;
use App\DTOs\SessionEditResource;
use App\DTOs\SessionEditViewData;
use App\Models\ClassSession;
use App\Models\User;
use DateTimeZone;

/**
 * Transport-neutral scheduling entry point. Task 2.1 only normalizes intent;
 * later domain tasks attach evaluation and mutation behind this façade.
 */
final readonly class SchedulingDomain
{
    public function __construct(
        private ScheduleProposalNormalizer $normalizer,
        private ?AvailabilityEvaluator $evaluator = null,
        private ?SchedulingMutationCoordinator $coordinator = null,
    ) {}

    /** @param array<string, mixed> $input */
    public function normalizeForSession(array $input, ClassSession $session, DateTimeZone $timezone): ScheduleProposal
    {
        return $this->normalizer->fromSession($input, $session, $timezone);
    }

    /** @param array<string, mixed> $input */
    public function normalize(array $input, RelationPath $currentPath, DateTimeZone $timezone): ScheduleProposal
    {
        return $this->normalizer->normalize($input, $currentPath, $timezone);
    }

    public function fromSessionEditResource(SessionEditResource $resource, DateTimeZone $timezone): ScheduleProposal
    {
        return $this->normalizer->fromSessionEditResource($resource, $timezone);
    }

    public function fromSessionEditViewData(SessionEditViewData $view, DateTimeZone $timezone): ScheduleProposal
    {
        return $this->normalizer->fromSessionEditViewData($view, $timezone);
    }

    public function fromSessionDisplayData(SessionDisplayData $display, RelationPath $path, SessionVersion $version, DateTimeZone $timezone): ScheduleProposal
    {
        return $this->normalizer->fromSessionDisplayData($display, $path, $version, $timezone);
    }

    public function evaluate(ScheduleProposal $proposal): AvailabilityResult
    {
        if ($this->evaluator === null) {
            throw new \LogicException('Availability evaluation requires the Task 2.2 evaluator boundary.');
        }

        $decision = $this->evaluator->evaluate($proposal);

        return new AvailabilityResult($decision->state, $proposal, $decision->code, $decision->details);
    }

    /**
     * Performs the one availability acceptance decision used by legacy write
     * adapters before their preserved persistence contracts run.
     */
    public function requireAvailable(ScheduleProposal $proposal): AvailabilityResult
    {
        $availability = $this->evaluate($proposal);
        if ($availability->state !== AvailabilityState::Available) {
            throw new SchedulingMutationException($availability->code, $availability);
        }

        return $availability;
    }

    /** @param (\Closure(ClassSession, ScheduleProposal): void)|null $afterSessionPersisted */
    public function mutate(User $actor, ScheduleProposal $proposal, ?\Closure $afterSessionPersisted = null): ScheduleMutationResult
    {
        return $this->coordinator()->mutate(
            $actor,
            $proposal,
            fn (ScheduleProposal $current, bool $overrideAuthorized): AvailabilityResult => $this->finalMutationDecision($current, $overrideAuthorized),
            $afterSessionPersisted,
        );
    }

    /**
     * @param array<string, int|string> $metadata
     * @param (\Closure(ClassSession, ScheduleProposal): void)|null $afterSessionPersisted
     */
    public function create(?User $actor, ScheduleProposal $proposal, array $metadata = [], ?\Closure $afterSessionPersisted = null): ScheduleMutationResult
    {
        return $this->coordinator()->create(
            $actor,
            $proposal,
            fn (ScheduleProposal $current, bool $overrideAuthorized): AvailabilityResult => $this->finalMutationDecision($current, $overrideAuthorized),
            $metadata,
            $afterSessionPersisted,
        );
    }

    private function finalMutationDecision(ScheduleProposal $proposal, bool $overrideAuthorized): AvailabilityResult
    {
        $availability = $this->evaluate($proposal);
        if ($availability->state === AvailabilityState::Available) {
            return $availability;
        }
        if ($proposal->override === null || ! $overrideAuthorized || $availability->state === AvailabilityState::Invalid) {
            throw new SchedulingMutationException($availability->code, $availability);
        }

        $report = $availability->details['conflicts'] ?? null;
        if (! $report instanceof ConflictReport) {
            throw new SchedulingMutationException($availability->code, $availability);
        }
        $blocking = array_filter($report->conflicts, static fn (SchedulingConflict $conflict): bool => $conflict->blocks());
        $allSoft = $blocking !== [] && array_reduce(
            $blocking,
            static fn (bool $accepted, SchedulingConflict $conflict): bool => $accepted && ! $conflict->invalid && $conflict->classification === 'soft',
            true,
        );
        if (! $allSoft) {
            throw new SchedulingMutationException($availability->code, $availability);
        }

        return $availability;
    }

    private function coordinator(): SchedulingMutationCoordinator
    {
        if ($this->coordinator === null) {
            throw new \LogicException('Scheduling mutation requires the transaction coordinator.');
        }

        return $this->coordinator;
    }
}
