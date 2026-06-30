<?php

namespace App\Services;

use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

/**
 * Teacher-instrument assignment service.
 *
 * Extracted from TeacherController. Encapsulates the "only one primary
 * instrument per teacher" invariant inside a DB transaction.
 */
class TeacherInstrumentService
{
    /**
     * Assign an instrument to a teacher.
     *
     * If the new assignment is marked primary, all existing primary flags
     * for that teacher are reset to false first (transactional).
     *
     * @param  array{skill_level: string, is_primary: bool}  $attributes
     */
    public function attachInstrument(Teacher $teacher, int $instrumentId, string $skillLevel, bool $isPrimary): void
    {
        DB::transaction(function () use ($teacher, $instrumentId, $skillLevel, $isPrimary) {
            // Only one primary instrument allowed per teacher.
            if ($isPrimary) {
                DB::table('teacher_instruments')
                    ->where('teacher_id', $teacher->id)
                    ->update(['is_primary' => false]);
            }

            $teacher->instruments()->attach($instrumentId, [
                'skill_level' => $skillLevel,
                'is_primary'  => $isPrimary,
            ]);
        });
    }

    /**
     * Remove an instrument assignment from a teacher.
     */
    public function detachInstrument(Teacher $teacher, int $instrumentId): void
    {
        $teacher->instruments()->detach($instrumentId);
    }
}
