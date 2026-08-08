<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Domain\Scheduling\BusinessCodeOwner;
use App\Enums\TeacherStatusEnum;
use App\Models\Teacher;
use App\Support\PersianTextNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Teacher mutations.
 *
 * Owns the teacher business rules that used to live in the controller: the
 * default status and the canonical form of every persisted text field. Deletion
 * cascades to the instrument assignments of the teacher, so it runs inside one
 * transaction and a partial delete can never be committed.
 *
 * Requirements: 6.4, 6.6, 6.9, 6.10, 6.13, 16.3
 */
final class TeacherAction
{
    /**
     * Canonical form of every persisted text field, shared with TeacherRequest.
     *
     * @var array<string, string>
     */
    public const NORMALIZED_FIELDS = [
        'full_name' => PersianTextNormalizer::TEXT,
        'phone' => PersianTextNormalizer::TEXT,
        'bio' => PersianTextNormalizer::MULTILINE,
    ];

    public function __construct(private readonly BusinessCodeOwner $codes) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Teacher
    {
        return $this->codes->createTeacher($this->attributes($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Teacher $teacher, array $data): Teacher
    {
        $teacher->update($this->attributes($data));

        return $teacher;
    }

    public function backfill(Teacher $teacher): Teacher
    {
        return $this->codes->backfillTeacher($teacher);
    }

    public function delete(Teacher $teacher): void
    {
        DB::transaction(static function () use ($teacher): void {
            $teacher->delete();
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

        $data['status'] = $data['status'] ?? TeacherStatusEnum::Active->value;

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function rejectCodeMutation(array $data): void
    {
        if (array_key_exists('teacher_code', $data)) {
            throw ValidationException::withMessages(['teacher_code' => __('validation.missing')]);
        }
    }
}
