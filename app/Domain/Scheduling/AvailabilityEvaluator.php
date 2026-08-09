<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

/** Pure decision composer. Only SchedulingDomain converts its decision to AvailabilityResult. */
final readonly class AvailabilityEvaluator
{
    public function __construct(private ConflictFactsProvider $facts, private ConflictClassifier $classifier, private AcademyRulesProvider $rules, private RoomSuitabilityService $rooms) {}

    public function evaluate(ScheduleProposal $proposal): AvailabilityDecision
    {
        try { $rules = $this->rules->effective(); } catch (\InvalidArgumentException) { return new AvailabilityDecision(AvailabilityState::Invalid, SchedulingDecisionCode::HardConstraint, ['conflicts' => new ConflictReport([$this->classifier->hardRule('RULES_INVALID')])]); }
        $room = $this->rooms->normalizedRoom($proposal->room);
        $intervalFacts = $this->facts->forProposal($proposal, $rules, $room);
        $roomFacts = $this->rooms->facts($proposal, $rules, $intervalFacts);
        $conflicts = [...$this->classifier->classify($intervalFacts)->conflicts, ...$this->ruleConflicts($proposal, $rules), ...$roomFacts['conflicts']];
        $report = new ConflictReport($conflicts);
        $state = $report->hasInvalidConstraint() ? AvailabilityState::Invalid : ($report->hasBlockingConflict() ? AvailabilityState::Conflict : AvailabilityState::Available);
        $code = $state === AvailabilityState::Available ? SchedulingDecisionCode::Available : ($state === AvailabilityState::Invalid ? SchedulingDecisionCode::HardConstraint : SchedulingDecisionCode::Conflict);
        return new AvailabilityDecision($state, $code, ['conflicts' => $report, 'rules' => $rules, 'buffers' => ['teacher_before' => $rules->teacherBufferBefore, 'teacher_after' => $rules->teacherBufferAfter], 'eligible_rooms' => $roomFacts['eligible_rooms']]);
    }

    /** @return list<SchedulingConflict> */
    private function ruleConflicts(ScheduleProposal $proposal, EffectiveSchedulingRules $rules): array
    {
        $range = $proposal->timeRange;
        $start = ((int) $range->start->format('G') * 60) + (int) $range->start->format('i');
        $end = ((int) $range->end->format('G') * 60) + (int) $range->end->format('i');
        $conflicts = [];
        if (! in_array((int) $range->start->format('N'), $rules->enabledWeekdays, true)) { $conflicts[] = $this->classifier->hardRule('DISABLED_WEEKDAY'); }
        if ($start < $rules->openingMinute || $end > $rules->closingMinute) { $conflicts[] = $this->classifier->hardRule('OUTSIDE_WORKING_HOURS'); }
        if ($range->durationMinutes() < $rules->minimumDuration || $range->durationMinutes() > $rules->maximumDuration) { $conflicts[] = $this->classifier->hardRule('INVALID_DURATION'); }
        if ($rules->lunch !== null && $start < $rules->lunch['end'] && $rules->lunch['start'] < $end) { $conflicts[] = $this->classifier->hardRule('LUNCH_OVERLAP'); }
        $day = array_values(array_filter($this->facts->teacherDayFacts($proposal, $rules), static fn (ConflictFact $fact): bool => $fact->status?->value !== 'cancelled' && $fact->range !== null));
        if (count($day) + 1 > $rules->dailySessionLimit) { $conflicts[] = $this->classifier->hardRule('DAILY_SESSION_LIMIT', ['limit' => $rules->dailySessionLimit]); }
        if ($this->consecutiveCount($range, $day) > $rules->consecutiveSessionLimit) { $conflicts[] = $this->classifier->hardRule('CONSECUTIVE_SESSION_LIMIT', ['limit' => $rules->consecutiveSessionLimit]); }
        return $conflicts;
    }

    /** @param list<ConflictFact> $facts */
    private function consecutiveCount(TimeRange $proposal, array $facts): int
    {
        $connected = [$proposal];
        for ($index = 0; isset($connected[$index]); $index++) { foreach ($facts as $fact) { if ($fact->range !== null && ! in_array($fact->range, $connected, true) && array_filter($connected, fn (TimeRange $range): bool => $range->overlaps($fact->range) || $range->isAdjacentTo($fact->range)) !== []) { $connected[] = $fact->range; } } }
        return count($connected);
    }
}

/** @internal Immutable evaluation output, intentionally not an API result. */
final readonly class AvailabilityDecision
{
    /** @param array<string, mixed> $details */
    public function __construct(public AvailabilityState $state, public SchedulingDecisionCode $code, public array $details) {}
}
