<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Domain\Scheduling\BusinessCodeOwner;
use App\Enums\StudentStatusEnum;
use App\Models\Student;
use App\Support\PersianTextNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Student mutations.
 *
 * Deletion cascades to the enrollments, invoices and attendance rows of the
 * student, so it runs inside one transaction with full rollback.
 *
 * Requirements: 6.4, 6.6, 6.9, 6.10, 6.13, 16.3
 */
final class StudentAction
{
    /**
     * Canonical form of every persisted text field, shared with StudentRequest.
     *
     * @var array<string, string>
     */
    public const NORMALIZED_FIELDS = [
        'full_name' => PersianTextNormalizer::TEXT,
        'phone' => PersianTextNormalizer::TEXT,
        'parent_phone' => PersianTextNormalizer::TEXT,
        'notes' => PersianTextNormalizer::MULTILINE,
    ];

    public function __construct(private readonly BusinessCodeOwner $codes) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Student
    {
        return $this->codes->createStudent($this->attributes($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Student $student, array $data): Student
    {
        $student->update($this->attributes($data));

        return $student;
    }

    public function backfill(Student $student): Student
    {
        return $this->codes->backfillStudent($student);
    }

    public function delete(Student $student): void
    {
        DB::transaction(static function () use ($student): void {
            $student->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $this->rejectCodeMutation($data);
        $data = PersianTextNormalizer::fields($data, self::NORMALIZED_FIELDS);

        $data['status'] = $data['status'] ?? StudentStatusEnum::Active->value;

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function rejectCodeMutation(array $data): void
    {
        if (array_key_exists('student_code', $data)) {
            throw ValidationException::withMessages(['student_code' => __('validation.missing')]);
        }
    }
}
