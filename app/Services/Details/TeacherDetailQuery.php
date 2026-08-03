<?php

namespace App\Services\Details;

use App\DTOs\OperationalRowData;
use App\DTOs\RecordDetailData;
use App\DTOs\RecordDetailSection;
use App\Helpers\Jalalian;
use App\Models\Instrument;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Services\Lists\ListPolicyResolver;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Record_Detail query for a teacher.
 *
 * Loads every relation the detail screen renders in one pass (so Blade never
 * triggers a query), maps persisted values into stable sections and resolves the
 * display-only ability flags for the current actor.
 */
final class TeacherDetailQuery
{
    /** Stable machine-readable section identifiers. */
    public const SECTION_PROFILE = 'teacher_profile';

    public const SECTION_INSTRUMENTS = 'teacher_instruments';

    public const SECTION_ENROLLMENTS = 'teacher_enrollments';

    public function forRecord(Teacher $teacher, ?Authenticatable $actor = null): RecordDetailData
    {
        $teacher->load([
            'instruments' => fn ($query) => $query
                ->orderBy('instruments.name_fa')
                ->orderBy('instruments.id'),
            'enrollments' => fn ($query) => $query
                ->with(['student', 'instrument'])
                ->orderByDesc('student_enrollments.started_at')
                ->orderBy('student_enrollments.id'),
        ]);

        $policy = new ListPolicyResolver($actor);
        $status = $teacher->status?->value;

        return new RecordDetailData(
            entity: 'teachers',
            id: $teacher->id,
            label: (string) $teacher->full_name,
            status: $status,
            status_label: $status === null ? null : __('admin.statuses.' . $status),
            sections: [
                $this->profileSection($teacher, $status),
                $this->instrumentsSection($teacher),
                $this->enrollmentsSection($teacher),
            ],
            policy_flags: $policy->flags(['update', 'delete', 'manageInstruments'], $teacher),
            placeholder: __('admin.value_not_provided'),
        );
    }

    private function profileSection(Teacher $teacher, ?string $status): RecordDetailSection
    {
        return new RecordDetailSection(
            id: self::SECTION_PROFILE,
            title: __('admin.teacher_information'),
            fields: [
                RecordDetailSection::field(__('admin.full_name'), $teacher->full_name),
                RecordDetailSection::field(__('admin.teacher_code'), $teacher->teacher_code, 'ltr'),
                RecordDetailSection::field(__('admin.phone'), $teacher->phone, 'ltr'),
                RecordDetailSection::field(
                    __('admin.status'),
                    $status === null ? null : __('admin.statuses.' . $status)
                ),
                RecordDetailSection::field(
                    __('admin.hire_date'),
                    $teacher->hire_date === null ? null : Jalalian::fromCarbon($teacher->hire_date)
                ),
                RecordDetailSection::field(__('admin.bio'), $teacher->bio, null, true),
            ],
        );
    }

    private function instrumentsSection(Teacher $teacher): RecordDetailSection
    {
        return new RecordDetailSection(
            id: self::SECTION_INSTRUMENTS,
            title: __('admin.assigned_instruments'),
            rows: $teacher->instruments
                ->map(fn (Instrument $instrument): OperationalRowData => new OperationalRowData(
                    id: $instrument->id,
                    label: (string) $instrument->display_name,
                    fields: [
                        'skill_level' => $instrument->pivot->skill_level
                            ? __('admin.skill_levels.' . $instrument->pivot->skill_level)
                            : null,
                        'is_primary' => $instrument->pivot->is_primary ? __('admin.primary') : null,
                    ],
                ))
                ->all(),
            empty_message: __('admin.no_instruments_assigned_yet'),
        );
    }

    private function enrollmentsSection(Teacher $teacher): RecordDetailSection
    {
        return new RecordDetailSection(
            id: self::SECTION_ENROLLMENTS,
            title: __('admin.enrollments'),
            rows: $teacher->enrollments
                ->map(fn (StudentEnrollment $enrollment): OperationalRowData => new OperationalRowData(
                    id: $enrollment->id,
                    label: (string) ($enrollment->student?->full_name ?? ''),
                    fields: [
                        'skill_level' => $enrollment->skill_level
                            ? __('admin.skill_levels.' . $enrollment->skill_level->value)
                            : null,
                        'started_at' => $enrollment->started_at === null
                            ? null
                            : Jalalian::fromCarbon($enrollment->started_at),
                    ],
                    status: $enrollment->status?->value,
                    relations: [
                        'instrument' => $enrollment->instrument?->display_name,
                        'status_label' => $enrollment->status === null
                            ? null
                            : __('admin.statuses.' . $enrollment->status->value),
                    ],
                ))
                ->all(),
            empty_message: __('admin.no_enrollments_yet'),
        );
    }
}
