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
        private readonly SchedulingLockManager $locks,
        private readonly SessionVersionManager $versions,
        private readonly ResourceVersionManager $resourceVersions,
        private readonly SessionAuditWriter $audits,
        private readonly ?\Closure $invalidateAvailability = null,
    ) {}

    /**
     * @param \Closure(ScheduleProposal, bool): AvailabilityResult $domainDecision
     * @param (\Closure(ClassSession, ScheduleProposal): void)|null $afterSessionPersisted
     */
    public function mutate(User $actor, ScheduleProposal $proposal, \Closure $domainDecision, ?\Closure $afterSessionPersisted = null): ScheduleMutationResult
    {
        if ($proposal->sessionId === null || $proposal->sessionVersion === null) {
            throw new SchedulingMutationException(SchedulingDecisionCode::StaleVersion);
        }

        return DB::transaction(function () use ($actor, $proposal, $domainDecision, $afterSessionPersisted): ScheduleMutationResult {
            $scope = $this->locks->lock($proposal);
            if (! $scope->session instanceof ClassSession) {
                throw new \LogicException('An existing-session mutation requires a locked session.');
            }

            $this->authorization->authorizeSession($actor, SchedulingAbility::Update, $scope->session);
            if (! $this->versions->matches($scope->session, $proposal->sessionVersion)) {
                throw SchedulingMutationException::stale($scope->session);
            }

            $availability = $this->decision($domainDecision, $proposal, $this->authorizeOverride($actor, $proposal, $scope->session));
            $prior = $this->versions->current($scope->session);
            if ($prior === null) {
                throw SchedulingMutationException::stale($scope->session);
            }

            $before = $this->snapshot($scope->session);
            $next = $this->versions->next();
            $scope->session->forceFill([...$this->changes($scope->session, $proposal), 'session_version' => $next->value])->saveOrFail();
            if ($afterSessionPersisted !== null) {
                $afterSessionPersisted($scope->session, $proposal);
            }

            return $this->complete($scope->session, $actor, $proposal, $prior, $next, $before, $availability, $scope->resourceKeys);
        });
    }

    /**
     * @param \Closure(ScheduleProposal, bool): AvailabilityResult $domainDecision
     * @param array<string, int|string> $metadata
     * @param (\Closure(ClassSession, ScheduleProposal): void)|null $afterSessionPersisted
     */
    public function create(?User $actor, ScheduleProposal $proposal, \Closure $domainDecision, array $metadata = [], ?\Closure $afterSessionPersisted = null): ScheduleMutationResult
    {
        if ($proposal->sessionId !== null || $proposal->sessionVersion !== null) {
            throw new SchedulingMutationException(SchedulingDecisionCode::HardConstraint);
        }

        return DB::transaction(function () use ($actor, $proposal, $domainDecision, $metadata, $afterSessionPersisted): ScheduleMutationResult {
            $scope = $this->locks->lock($proposal);
            if ($scope->session instanceof ClassSession) {
                throw new \LogicException('A new-session mutation cannot lock an existing session.');
            }
            if ($actor !== null) {
                $this->authorization->authorizeCreation($actor, $proposal->source);
            }
            if ($proposal->override !== null) {
                throw new SchedulingMutationException(SchedulingDecisionCode::UnauthorizedOverride);
            }

            $availability = $this->decision($domainDecision, $proposal, false);
            $next = $this->versions->next();
            $session = new ClassSession;
            $session->forceFill([
                ...$this->creationChanges($proposal),
                ...$this->creationMetadata($proposal, $metadata),
                'session_version' => $next->value,
            ])->saveOrFail();
            if ($afterSessionPersisted !== null) {
                $afterSessionPersisted($session, $proposal);
            }

            return $this->complete(
                $session,
                $actor,
                $proposal,
                null,
                $next,
                [],
                $availability,
                [...$scope->resourceKeys, 'class_session:'.$session->getKey()],
            );
        });
    }

    /** @param \Closure(ScheduleProposal, bool): AvailabilityResult $domainDecision */
    private function decision(\Closure $domainDecision, ScheduleProposal $proposal, bool $overrideAuthorized): AvailabilityResult
    {
        $availability = $domainDecision($proposal, $overrideAuthorized);
        if (! $availability instanceof AvailabilityResult) {
            throw new \LogicException('The scheduling domain must return an availability result for every mutation.');
        }

        return $availability;
    }

    private function authorizeOverride(User $actor, ScheduleProposal $proposal, ClassSession $session): bool
    {
        if ($proposal->override === null) {
            return false;
        }

        try {
            $this->authorization->authorizeSession($actor, SchedulingAbility::Override, $session);
        } catch (AuthorizationException) {
            throw new SchedulingMutationException(SchedulingDecisionCode::UnauthorizedOverride, latest: $session);
        }

        return true;
    }

    /** @param array<string, mixed> $before @param list<string> $resourceKeys */
    private function complete(
        ClassSession $session,
        ?User $actor,
        ScheduleProposal $proposal,
        ?SessionVersion $prior,
        SessionVersion $next,
        array $before,
        AvailabilityResult $availability,
        array $resourceKeys,
    ): ScheduleMutationResult {
        $after = $this->snapshot($session);
        $resourceVersions = $this->resourceVersions->advance(array_values(array_unique($resourceKeys)));
        if ($this->invalidateAvailability !== null) {
            ($this->invalidateAvailability)($resourceVersions);
        }
        $audit = $this->audits->append($session, $actor, $proposal, $prior, $next, $before, $after, $availability);

        return new ScheduleMutationResult($session, $next, $availability, $audit, $resourceVersions);
    }

    /** @return array<string, mixed> */
    private function creationChanges(ScheduleProposal $proposal): array
    {
        $changes = [
            'session_date' => $proposal->timeRange->start->format('Y-m-d'),
            'start_time' => $proposal->timeRange->start->format('H:i:s'),
            'duration_minutes' => $proposal->timeRange->durationMinutes(),
            'status' => $proposal->status->value,
            'room' => $proposal->room,
            'notes' => $proposal->notes,
        ];

        return $proposal->relationPath->type === RelationPathType::Enrollment
            ? [...$changes, 'enrollment_id' => $proposal->relationPath->enrollmentId]
            : [...$changes,
                'student_id' => $proposal->relationPath->studentId,
                'teacher_id' => $proposal->relationPath->teacherId,
                'instrument_id' => $proposal->relationPath->instrumentId,
            ];
    }

    /** @param array<string, int|string> $metadata @return array<string, int|string> */
    private function creationMetadata(ScheduleProposal $proposal, array $metadata): array
    {
        if ($metadata === []) {
            return [];
        }
        if (array_keys($metadata) !== ['recurring_schedule_id']
            || $proposal->relationPath->type !== RelationPathType::Enrollment
            || ! $this->isStableId($metadata['recurring_schedule_id'])) {
            throw new SchedulingMutationException(SchedulingDecisionCode::HardConstraint);
        }

        return $metadata;
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
            'recurring_schedule_id' => $session->recurring_schedule_id,
            'session_date' => $session->session_date?->format('Y-m-d'),
            'start_time' => $session->start_time?->format('H:i:s'),
            'duration_minutes' => (int) $session->duration_minutes,
            'status' => $status instanceof \BackedEnum ? $status->value : (string) $status,
            'room' => $session->getRawOriginal('room'),
            'notes' => $session->notes,
        ];
    }

    private function isStableId(mixed $id): bool
    {
        return (is_int($id) && $id > 0) || (is_string($id) && ctype_digit($id) && (int) $id > 0);
    }
}
