<?php

namespace App\Services\Details;

use App\DTOs\OperationalRowData;
use App\DTOs\RecordDetailData;
use App\DTOs\RecordDetailSection;
use App\Helpers\Jalalian;
use App\Models\Student;
use App\Services\Lists\ListPolicyResolver;
use App\Services\StudentHistoryService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

/**
 * Record_Detail query for a student.
 *
 * Maps persisted values into stable sections so Blade never touches an Eloquent
 * relation. The history section keeps the stable machine-readable identifier
 * `student_history` and preserves the deterministic event order resolved by
 * StudentHistoryService (timestamp descending with the unique event key as
 * tie-breaker), so equal timestamps cannot reorder between requests.
 */
final class StudentDetailQuery
{
    /** Stable machine-readable section identifiers. */
    public const SECTION_PROFILE = 'student_profile';

    public const SECTION_HISTORY = 'student_history';

    public function __construct(private readonly StudentHistoryService $history) {}

    public function forRecord(Student $student, ?Authenticatable $actor = null): RecordDetailData
    {
        $status = $this->statusValue($student);

        return new RecordDetailData(
            entity: 'students',
            id: $student->id,
            label: (string) $student->full_name,
            status: $status,
            status_label: $status === null ? null : __('admin.statuses.' . $status),
            sections: [
                $this->profileSection($student, $status),
                $this->historySection($student),
            ],
            policy_flags: (new ListPolicyResolver($actor))->flags(['update', 'delete'], $student),
            placeholder: __('admin.value_not_provided'),
        );
    }

    private function profileSection(Student $student, ?string $status): RecordDetailSection
    {
        return new RecordDetailSection(
            id: self::SECTION_PROFILE,
            title: __('admin.student_information'),
            fields: [
                RecordDetailSection::field(__('admin.full_name'), $student->full_name),
                RecordDetailSection::field(__('admin.phone'), $student->phone, 'ltr'),
                RecordDetailSection::field(__('admin.parent_phone'), $student->parent_phone, 'ltr'),
                RecordDetailSection::field(
                    __('admin.status'),
                    $status === null ? null : __('admin.statuses.' . $status)
                ),
                RecordDetailSection::field(
                    __('admin.join_date'),
                    $student->join_date === null ? null : Jalalian::fromCarbon($student->join_date)
                ),
                RecordDetailSection::field(__('admin.notes'), $student->notes, null, true),
            ],
        );
    }

    private function historySection(Student $student): RecordDetailSection
    {
        return new RecordDetailSection(
            id: self::SECTION_HISTORY,
            title: __('admin.student_history'),
            rows: $this->historyRows($student),
            empty_message: __('admin.no_history_events'),
        );
    }

    /**
     * @return array<int, OperationalRowData>
     */
    private function historyRows(Student $student): array
    {
        try {
            $events = $this->history->buildTimeline($student);
        } catch (\Throwable $e) {
            Log::error('StudentHistoryService failed for student ' . $student->id . ': ' . $e->getMessage());

            return [];
        }

        return $events
            ->map(fn (array $event): OperationalRowData => new OperationalRowData(
                id: $event['key'],
                label: __('admin.history_event_types.' . $event['type']),
                fields: [
                    'event_type' => $event['type'],
                    'description' => $event['description'],
                    'timestamp' => Jalalian::fromCarbon($event['timestamp'], 'Y/m/d H:i'),
                ],
                relations: $this->metaLabels($event['meta'] ?? []),
            ))
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<string, string|null>
     */
    private function metaLabels(array $meta): array
    {
        $labels = [];

        foreach ($meta as $key => $value) {
            $labels[$key] = ($value === null || $value === '') ? null : (string) $value;
        }

        return $labels;
    }

    private function statusValue(Student $student): ?string
    {
        if ($student->status === null) {
            return null;
        }

        return $student->status instanceof \BackedEnum
            ? (string) $student->status->value
            : (string) $student->status;
    }
}
