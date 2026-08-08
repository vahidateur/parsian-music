<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/** The sole Task 4.1 transaction boundary for accepted existing-session mutations. */
final class SchedulingMutationCoordinator
{
    /** @param (\Closure(array<string, int>): void)|null $invalidateAvailability */
    public function __construct(
        private readonly SchedulingAuthorization $authorization,
        private readonly AvailabilityEvaluator $evaluator,
        private readonly SchedulingLockManager $locks,
        private readonly SessionVersionManager $versions,
        private readonly ResourceVersionManager $resourceVersions,
        private readonly SessionAuditWriter $audits,
        private readonly ?\Closure $invalidateAvailability = null,
    ) {}

    public function mutate(User $actor, ScheduleProposal $proposal): ScheduleMutationResult
    {
        if ($proposal->sessionId === null || $proposal->sessionVersion === null) {
            throw new SchedulingMutationException(SchedulingDecisionCode::StaleVersion);
        }

        return DB::transaction(function () use ($actor, $proposal): ScheduleMutationResult {
            $scope = $this->locks->lock($proposal);
            $this->authorization->authorizeSession($actor, SchedulingAbility::Update, $scope->session);
            if (! $this->versions->matches($scope->session, $proposal->sessionVersion)) {
                throw SchedulingMutationException::stale($scope->session);
            }

            if ($proposal->override !== null) {
                try {
                    $this->authorization->authorizeSession($actor, SchedulingAbility::Override, $scope->session);
                } catch (AuthorizationException) {
                    throw new SchedulingMutationException(SchedulingDecisionCode::UnauthorizedOverride, latest: $scope->session);
                }
            }

            $availability = $this->evaluate($proposal);
            if (! $this->accepted($availability, $proposal)) {
                throw new SchedulingMutationException($availability->code, $availability, $scope->session);
            }

            $prior = $this->versions->current($scope->session);
            if ($prior === null) {
                throw SchedulingMutationException::stale($scope->session);
            }

            $before = $this->snapshot($scope->session);
            $next = $this->versions->next();
            $scope->session->forceFill([...$this->changes($scope->session, $proposal), 'session_version' => $next->value])->saveOrFail();
            $after = $this->snapshot($scope->session);
            $resourceVersions = $this->resourceVersions->advance($scope->resourceKeys);
            if ($this->invalidateAvailability !== null) {
                ($this->invalidateAvailability)($resourceVersions);
            }
            $audit = $this->audits->append($scope->session, $actor, $proposal, $prior, $next, $before, $after, $availability);

            return new ScheduleMutationResult($scope->session, $next, $availability, $audit, $resourceVersions);
        });
    }

    private function evaluate(ScheduleProposal $proposal): AvailabilityResult
    {
        $decision = $this->evaluator->evaluate($proposal);

        return new AvailabilityResult($decision->state, $proposal, $decision->code, $decision->details);
    }

    private function accepted(AvailabilityResult $availability, ScheduleProposal $proposal): bool
    {
        if ($availability->state === AvailabilityState::Available) {
            return true;
        }
        if ($proposal->override === null || $availability->state === AvailabilityState::Invalid) {
            return false;
        }

        $report = $availability->details['conflicts'] ?? null;
        if (! $report instanceof ConflictReport) {
            return false;
        }
        $blocking = array_filter($report->conflicts, static fn (SchedulingConflict $conflict): bool => $conflict->blocks());

        return $blocking !== [] && array_reduce($blocking, static fn (bool $accepted, SchedulingConflict $conflict): bool => $accepted && ! $conflict->invalid && $conflict->classification === 'soft', true);
    }

    /** @return array<string, mixed> */
    private function changes(ClassSession $session, ScheduleProposal $proposal): array
    {
        if ($proposal->relationPath->type === RelationPathType::Enrollment && (string) $session->enrollment_id !== (string) $proposal->relationPath->enrollmentId) {
            throw new SchedulingMutationException(SchedulingDecisionCode::HardConstraint, latest: $session);
        }
        if ($proposal->relationPath->type === RelationPathType::Direct && $session->enrollment_id !== null) {
            throw new SchedulingMutationException(SchedulingDecisionCode::HardConstraint, latest: $session);
        }

        $changes = [
            'session_date' => $proposal->timeRange->start->format('Y-m-d'),
            'start_time' => $proposal->timeRange->start->format('H:i:s'),
            'duration_minutes' => $proposal->timeRange->durationMinutes(),
            'status' => $proposal->status->value,
            'room' => $proposal->room,
            'notes' => $proposal->notes,
        ];
        if ($proposal->relationPath->type === RelationPathType::Direct) {
            $changes += [
                'student_id' => $proposal->relationPath->studentId,
                'teacher_id' => $proposal->relationPath->teacherId,
                'instrument_id' => $proposal->relationPath->instrumentId,
            ];
        }

        return $changes;
    }

    /** @return array<string, mixed> */
    private function snapshot(ClassSession $session): array
    {
        $status = $session->status;

        return [
            'student_id' => $session->student_id,
            'teacher_id' => $session->teacher_id,
            'instrument_id' => $session->instrument_id,
            'enrollment_id' => $session->enrollment_id,
            'session_date' => $session->session_date?->format('Y-m-d'),
            'start_time' => $session->start_time?->format('H:i:s'),
            'duration_minutes' => (int) $session->duration_minutes,
            'status' => $status instanceof \BackedEnum ? $status->value : (string) $status,
            'room' => $session->getRawOriginal('room'),
            'notes' => $session->notes,
        ];
    }
}
