<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subscription;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;

/** Checks deletion dependencies without deleting or mutating any related row. */
final class ProtectedDependencyChecker
{
    /** @return array<int, string> */
    public function categories(Teacher|Student $record): array
    {
        $categories = [];
        $id = $record->getKey();

        if ($record instanceof Teacher) {
            if (StudentEnrollment::query()->where('teacher_id', $id)->exists()) {
                $categories[] = 'enrollment';
            }
            if (Subscription::query()->where('teacher_id', $id)->exists()) {
                $categories[] = 'subscription';
            }
            if (Invoice::query()->whereHas('enrollment', fn (Builder $query): Builder => $query->where('teacher_id', $id))->exists()) {
                $categories[] = 'invoice';
            }
            if ($this->teacherSessions($id)->exists()) {
                $categories[] = 'class_session';
            }
            if ($this->teacherAttendance($id)->exists()) {
                $categories[] = 'attendance';
            }
        } else {
            if (StudentEnrollment::query()->where('student_id', $id)->exists()) {
                $categories[] = 'enrollment';
            }
            if (Subscription::query()->where('student_id', $id)->exists()) {
                $categories[] = 'subscription';
            }
            if (Invoice::query()->where('student_id', $id)->orWhereHas('enrollment', fn (Builder $query): Builder => $query->where('student_id', $id))->exists()) {
                $categories[] = 'invoice';
            }
            if (ClassAttendance::query()->where('student_id', $id)->exists()) {
                $categories[] = 'attendance';
            }
            if ($this->studentSessions($id)->exists()) {
                $categories[] = 'class_session';
            }
            if (Lead::query()->where('converted_student_id', $id)->exists()) {
                $categories[] = 'converted_lead';
            }
        }

        return $categories;
    }

    public function hasProtectedDependency(Teacher|Student $record): bool
    {
        return $this->categories($record) !== [];
    }

    public function check(Teacher|Student $record): bool
    {
        return $this->hasProtectedDependency($record);
    }

    private function teacherSessions(int|string $id): Builder
    {
        return ClassSession::query()->where(function (Builder $query) use ($id): void {
            $query->where('teacher_id', $id)
                ->orWhereHas('enrollment', fn (Builder $enrollment): Builder => $enrollment->where('teacher_id', $id));
        });
    }

    private function teacherAttendance(int|string $id): Builder
    {
        return ClassAttendance::query()->whereHas('classSession', function (Builder $query) use ($id): void {
            $query->where(function (Builder $session) use ($id): void {
                $session->where('teacher_id', $id)
                    ->orWhereHas('enrollment', fn (Builder $enrollment): Builder => $enrollment->where('teacher_id', $id));
            });
        });
    }

    private function studentSessions(int|string $id): Builder
    {
        return ClassSession::query()->where(function (Builder $query) use ($id): void {
            $query->where('student_id', $id)
                ->orWhereHas('enrollment', fn (Builder $enrollment): Builder => $enrollment->where('student_id', $id));
        });
    }
}
