<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\DTOs\SessionDisplayData;
use App\DTOs\SessionEditResource;
use App\DTOs\SessionEditViewData;
use App\Models\ClassSession;
use DateTimeZone;

/**
 * Transport-neutral scheduling entry point. Task 2.1 only normalizes intent;
 * later domain tasks attach evaluation and mutation behind this façade.
 */
final readonly class SchedulingDomain
{
    public function __construct(private ScheduleProposalNormalizer $normalizer, private ?AvailabilityEvaluator $evaluator = null) {}

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
}
