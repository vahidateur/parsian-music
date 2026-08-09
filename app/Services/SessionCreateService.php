<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Scheduling\ProposalSource;
use App\Domain\Scheduling\RelationPath;
use App\Domain\Scheduling\RelationPathType;
use App\Domain\Scheduling\ScheduleProposal;
use App\Domain\Scheduling\SchedulingDomain;
use App\Domain\Scheduling\SchedulingMutationException;
use App\Domain\Scheduling\SchedulingValidationException;
use App\Enums\SessionStatusEnum;
use App\Models\ClassSession;
use App\Models\Subscription;
use App\Models\User;
use DateTimeZone;
use Illuminate\Validation\ValidationException;

/** Preserves manual-create input and subscription contracts through SchedulingDomain. */
final class SessionCreateService
{
    public function __construct(
        private readonly RoomResolver $roomResolver,
        private readonly SchedulingDomain $scheduling,
    ) {}

    /** @param array<string, mixed> $validated */
    public function create(array $validated, ?User $actor = null): ClassSession
    {
        $validated['room'] = $this->roomResolver->normalize($validated['room'] ?? null);

        try {
            $proposal = $this->proposal($validated);
        } catch (SchedulingValidationException $exception) {
            throw $this->validationException($exception);
        }

        try {
            return $this->scheduling->create(
                $actor,
                $proposal,
                afterSessionPersisted: function (ClassSession $_, ScheduleProposal $accepted): void {
                    $subscription = Subscription::query()->where([
                        'student_id' => $accepted->relationPath->studentId,
                        'teacher_id' => $accepted->relationPath->teacherId,
                        'instrument_id' => $accepted->relationPath->instrumentId,
                    ])->lockForUpdate()->first();

                    if ($subscription !== null) {
                        $subscription->increment('sessions_used');
                    }
                },
            )->session;
        } catch (SchedulingMutationException $exception) {
            throw $this->rejectionException($exception);
        }
    }

    /** @param array<string, mixed> $validated */
    private function proposal(array $validated): ScheduleProposal
    {
        return $this->scheduling->normalize([
            ...$validated,
            'status' => SessionStatusEnum::Scheduled->value,
            'source' => ProposalSource::Form->value,
        ], new RelationPath(
            RelationPathType::Direct,
            null,
            (int) $validated['student_id'],
            (int) $validated['teacher_id'],
            (int) $validated['instrument_id'],
        ), $this->timezone());
    }

    private function timezone(): DateTimeZone
    {
        return new DateTimeZone((string) config('app.timezone', 'Asia/Tehran'));
    }

    private function validationException(SchedulingValidationException $exception): ValidationException
    {
        $errors = [];
        foreach ($exception->errors as $field => $code) {
            $errors[$field] = $code === 'protected_field'
                ? __('admin.session_edit_protected_field')
                : __('validation.invalid', ['attribute' => $field]);
        }

        return ValidationException::withMessages($errors);
    }

    private function rejectionException(SchedulingMutationException $exception): ValidationException
    {
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
}
