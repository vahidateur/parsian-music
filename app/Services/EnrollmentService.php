<?php

namespace App\Services;

use App\Enums\EnrollmentStatusEnum;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Enrollment domain service.
 *
 * Extracted from StudentEnrollmentController. Encapsulates the business
 * invariants of creating an enrollment:
 *   1. No duplicate active enrollment for the same student + instrument.
 *   2. The assigned teacher must teach the selected instrument.
 *   3. started_at and status auto-defaults.
 */
class EnrollmentService
{
    /**
     * Create a new enrollment after enforcing all business rules.
     *
     * @param  array{student_id: int, instrument_id: int, teacher_id: int, skill_level?: string, status?: string, notes?: string}  $data
     * @return StudentEnrollment
     *
     * @throws ValidationException When a business invariant is violated.
     */
    public function createEnrollment(array $data): StudentEnrollment
    {
        $this->guardAgainstDuplicateActive(
            $data['student_id'],
            $data['instrument_id'],
        );

        $this->guardTeacherTeachesInstrument(
            $data['teacher_id'],
            $data['instrument_id'],
        );

        // Auto-set started_at and default status.
        $data['started_at'] = now()->toDateString();
        $data['status'] = $data['status'] ?? EnrollmentStatusEnum::Active->value;

        return StudentEnrollment::create($data);
    }

    /**
     * Prevent two active enrollments for the same student + instrument.
     *
     * @throws ValidationException
     */
    protected function guardAgainstDuplicateActive(int $studentId, int $instrumentId): void
    {
        $duplicate = StudentEnrollment::where('student_id', $studentId)
            ->where('instrument_id', $instrumentId)
            ->active()
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'instrument_id' => 'This student already has an active enrollment for this instrument.',
            ]);
        }
    }

    /**
     * Verify the teacher is assigned to the instrument via teacher_instruments.
     *
     * @throws ValidationException
     */
    public function guardTeacherTeachesInstrument(int $teacherId, int $instrumentId): void
    {
        $teacherTeachesInstrument = DB::table('teacher_instruments')
            ->where('teacher_id', $teacherId)
            ->where('instrument_id', $instrumentId)
            ->exists();

        if (! $teacherTeachesInstrument) {
            throw ValidationException::withMessages([
                'teacher_id' => 'This teacher does not teach the selected instrument.',
            ]);
        }
    }
}
