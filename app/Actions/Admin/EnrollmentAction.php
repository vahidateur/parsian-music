<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\StudentEnrollment;
use App\Services\EnrollmentService;
use App\Support\PersianTextNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Enrollment mutations.
 *
 * The enrollment invariants (no duplicate active enrollment, the teacher must
 * teach the instrument) stay in EnrollmentService; this action owns the
 * normalization and the transaction boundary, because creation reads the
 * existing enrollments and the teacher_instruments rows before it writes.
 *
 * Requirements: 6.4, 6.6, 6.9, 6.10, 6.13, 16.3
 */
final class EnrollmentAction
{
    /**
     * Canonical form of every persisted text field, shared with the enrollment requests.
     *
     * @var array<string, string>
     */
    public const NORMALIZED_FIELDS = ['notes' => PersianTextNormalizer::MULTILINE];

    public function __construct(private readonly EnrollmentService $enrollments)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException when an enrollment invariant is violated.
     */
    public function create(array $data): StudentEnrollment
    {
        $data = PersianTextNormalizer::fields($data, self::NORMALIZED_FIELDS);

        return DB::transaction(fn (): StudentEnrollment => $this->enrollments->createEnrollment($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(StudentEnrollment $enrollment, array $data): StudentEnrollment
    {
        $data = PersianTextNormalizer::fields($data, self::NORMALIZED_FIELDS);

        if ((int) $data['teacher_id'] !== (int) $enrollment->teacher_id) {
            $this->enrollments->guardTeacherTeachesInstrument(
                (int) $data['teacher_id'],
                $enrollment->instrument_id,
            );
        }

        $enrollment->update($data);

        return $enrollment;
    }

    public function delete(StudentEnrollment $enrollment): void
    {
        DB::transaction(static function () use ($enrollment): void {
            $enrollment->delete();
        });
    }
}
