<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\FilterContext;
use App\DTOs\ResolvedRelationPath;
use App\DTOs\SessionEditResource;
use App\DTOs\SessionEditViewData;
use App\Domain\Scheduling\ProposalSource;
use App\Domain\Scheduling\ScheduleProposal;
use App\Domain\Scheduling\SchedulingDecisionCode;
use App\Domain\Scheduling\SchedulingDomain;
use App\Domain\Scheduling\SchedulingMutationException;
use App\Domain\Scheduling\SchedulingValidationException;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Models\User;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * Preserved edit adapter. Relation/UI preparation remains local; the
 * authoritative scheduling decision and session write belong to the domain.
 */
final class SessionEditService
{
    public function __construct(
        private readonly RelationPathResolver $relationResolver,
        private readonly RoomResolver $roomResolver,
        private readonly RoomOptionProvider $roomOptions,
        private readonly SchedulingDomain $scheduling,
    ) {}

    public function prepare(ClassSession $session, mixed $returnContext = null): SessionEditViewData
    {
        $persisted = $this->loadSession($session->getKey());
        $path = $this->resolvePath($persisted);
        $roomName = $persisted->getRawOriginal('room');

        return new SessionEditViewData(
            session_id: $persisted->getKey(),
            values: $this->editableValues($persisted),
            relation_options: [
                'students' => Student::query()->orderBy('full_name')->get(),
                'teachers' => Teacher::query()->orderBy('full_name')->get(),
                'instruments' => Instrument::query()->orderBy('name_fa')->orderBy('name')->get(),
                'relation' => $path->toArray(),
            ],
            room_options: $this->roomOptions->forSessionInput(),
            room_resolution: $this->roomResolver->resolve($roomName),
            policy_flags: ['update' => true],
            return_context: $returnContext instanceof FilterContext ? $returnContext : null,
        );
    }

    /** @param array<string, mixed> $attributes */
    public function update(ClassSession $session, array $attributes, User $actor): SessionEditResource
    {
        try {
            $persisted = $this->loadSession($session->getKey());
            $oldPath = $this->resolvePath($persisted);
            $proposal = $this->scheduling->normalizeForSession(
                $this->proposalInput($attributes, $persisted),
                $persisted,
                $this->timezone(),
            );

            $result = $this->scheduling->mutate(
                $actor,
                $proposal,
                function (ClassSession $_, ScheduleProposal $accepted) use ($oldPath): void {
                    $this->syncSubscriptionCounters(
                        $oldPath,
                        (int) $accepted->relationPath->studentId,
                        (int) $accepted->relationPath->teacherId,
                        (int) $accepted->relationPath->instrumentId,
                    );
                },
            );

            return $this->resource($this->loadSession($result->session->getKey()));
        } catch (SchedulingValidationException $exception) {
            throw $this->validationException($exception);
        } catch (SchedulingMutationException $exception) {
            throw $this->rejectionException($exception);
        }
    }

    private function loadSession(int|string $id): ClassSession
    {
        $session = ClassSession::query()
            ->with([
                'enrollment.student', 'enrollment.teacher', 'enrollment.instrument',
                'student', 'teacher', 'instrument',
            ])
            ->whereKey($id)
            ->first();

        if ($session instanceof ClassSession) {
            return $session;
        }

        throw (new ModelNotFoundException())->setModel(ClassSession::class, [$id]);
    }

    private function resolvePath(ClassSession $session): ResolvedRelationPath
    {
        return $this->relationResolver->resolve($session);
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    private function proposalInput(array $attributes, ClassSession $session): array
    {
        unset($attributes['return_context'], $attributes['return_page']);
        $attributes['session_version'] = $attributes['updated_at'] ?? $session->updated_at?->toISOString();
        unset($attributes['updated_at']);
        $attributes['source'] = ProposalSource::Form->value;

        return $attributes;
    }

    private function timezone(): DateTimeZone
    {
        return new DateTimeZone((string) config('app.timezone', 'Asia/Tehran'));
    }

    private function syncSubscriptionCounters(
        ResolvedRelationPath $oldPath,
        int $studentId,
        int $teacherId,
        int $instrumentId,
    ): void {
        $oldKey = implode(':', [$oldPath->student_id, $oldPath->teacher_id, $oldPath->instrument_id]);
        $newKey = implode(':', [$studentId, $teacherId, $instrumentId]);
        if ($oldKey === $newKey) {
            return;
        }

        $subscriptions = [
            $oldKey => [$oldPath->student_id, $oldPath->teacher_id, $oldPath->instrument_id, 'old'],
            $newKey => [$studentId, $teacherId, $instrumentId, 'new'],
        ];
        ksort($subscriptions);

        foreach ($subscriptions as [$student, $teacher, $instrument, $direction]) {
            $subscription = Subscription::query()
                ->where('student_id', $student)
                ->where('teacher_id', $teacher)
                ->where('instrument_id', $instrument)
                ->lockForUpdate()
                ->first();

            if ($subscription === null) {
                continue;
            }

            $subscription->sessions_used = $direction === 'old'
                ? max(0, (int) $subscription->sessions_used - 1)
                : (int) $subscription->sessions_used + 1;
            $subscription->saveOrFail();
        }
    }

    private function validationException(SchedulingValidationException $exception): ValidationException
    {
        $errors = [];
        foreach ($exception->errors as $field => $code) {
            $errors[$field] = match ($code) {
                'protected_field' => __('admin.session_edit_protected_field'),
                'unexpected_field' => __('admin.session_edit_unexpected_field'),
                'relation_conflict' => __('admin.session_relation_conflict'),
                'required', 'invalid' => __('validation.invalid', ['attribute' => $field]),
                default => __('admin.session_relation_conflict'),
            };
        }

        return ValidationException::withMessages($errors);
    }

    private function rejectionException(SchedulingMutationException $exception): ValidationException
    {
        if ($exception->decisionCode === SchedulingDecisionCode::StaleVersion) {
            return ValidationException::withMessages(['updated_at' => __('admin.session_edit_stale')]);
        }

        $conflicts = $exception->availability?->details['conflicts']?->conflicts ?? [];
        $invalidRoom = array_filter($conflicts, static fn (mixed $conflict): bool => $conflict instanceof \App\Domain\Scheduling\SchedulingConflict
            && $conflict->resource === 'room'
            && in_array($conflict->code, ['ROOM_REQUIRED', 'ROOM_NAME_INVALID', 'ROOM_UNAVAILABLE', 'ROOM_INCOMPATIBLE'], true)) !== [];

        return ValidationException::withMessages([
            $invalidRoom ? 'room' : 'start_time' => $invalidRoom
                ? __('admin.room_not_available')
                : __('admin.session_conflict_error'),
        ]);
    }

    /** @return array<string, mixed> */
    private function editableValues(ClassSession $session): array
    {
        return [
            'student_id' => $session->student_id ?? $session->enrollment?->student_id,
            'teacher_id' => $session->teacher_id ?? $session->enrollment?->teacher_id,
            'instrument_id' => $session->instrument_id ?? $session->enrollment?->instrument_id,
            'session_date' => $session->session_date?->toDateString(),
            'start_time' => $this->timeValue($session->start_time),
            'duration_minutes' => (int) $session->duration_minutes,
            'status' => $session->status instanceof \BackedEnum ? $session->status->value : (string) $session->status,
            'room' => $session->getRawOriginal('room'),
            'notes' => $session->notes,
            'updated_at' => $session->updated_at?->toISOString(),
        ];
    }

    private function timeValue(mixed $time): string
    {
        return $time instanceof DateTimeInterface ? $time->format('H:i') : substr((string) $time, 0, 5);
    }

    private function resource(ClassSession $session): SessionEditResource
    {
        $path = $this->resolvePath($session);
        $roomName = $session->getRawOriginal('room');
        $room = $this->roomResolver->find($roomName);

        return new SessionEditResource(
            session_id: $session->getKey(),
            student_id: $path->student_id,
            teacher_id: $path->teacher_id,
            instrument_id: $path->instrument_id,
            session_date: $session->session_date?->toDateString() ?? '',
            start_time: $this->timeValue($session->start_time),
            duration_minutes: (int) $session->duration_minutes,
            status: $session->status,
            room: $roomName,
            notes: $session->notes,
            relation: [
                ...$path->toArray(),
                'student_name' => $path->student->full_name,
                'teacher_name' => $path->teacher->full_name,
                'instrument_name' => $path->instrument->display_name,
            ],
            room_resolution: $this->roomResolver->resolve($roomName),
            room_id: $room?->getKey(),
            updated_at: $session->updated_at?->toISOString(),
        );
    }
}
