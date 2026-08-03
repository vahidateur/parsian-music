<?php

namespace Tests\Unit\DTOs;

use App\DTOs\BulkCommand;
use App\DTOs\BulkItemResultData;
use App\DTOs\BulkResultData;
use App\DTOs\CalendarEventData;
use App\DTOs\FilterContext;
use App\DTOs\Filter_Context;
use App\DTOs\RoomOptionData;
use App\DTOs\SessionEditResource;
use App\DTOs\SessionEditViewData;
use App\DTOs\SessionNotesResource;
use App\Enums\BulkActionEnum;
use App\Enums\BulkEntityEnum;
use App\Enums\BulkItemResultStatusEnum;
use App\Enums\BulkResultOutcomeEnum;
use App\Enums\BulkSelectionModeEnum;
use App\Enums\RoomOptionModeEnum;
use App\Enums\RoomResolutionEnum;
use App\Enums\SessionStatusEnum;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DomainContractsTest extends TestCase
{
    public function test_bulk_command_is_immutable_and_serializable_for_each_selection_mode(): void
    {
        $context = new FilterContext(
            entity: 'teacher',
            search: ' violin ',
            filters: ['status' => 'active'],
            sort: 'name',
            direction: 'asc',
            context_fingerprint: 'context-123',
            expires_at: new DateTimeImmutable('+10 minutes'),
            signature: 'signed-context',
        );
        $allFiltered = new BulkCommand(
            entity: BulkEntityEnum::Teacher,
            action: BulkActionEnum::Activate,
            mode: BulkSelectionModeEnum::AllFiltered,
            filter_context: $context,
            actor_id: 4,
            request_fingerprint: 'request-123',
        );
        $currentPage = new BulkCommand('student', 'delete', 'current_page', [3, 7]);

        $this->assertSame('all_filtered', $allFiltered->toArray()['mode']);
        $this->assertSame(['ids' => [3, 7]], array_intersect_key($currentPage->toArray(), ['ids' => true]));
        $this->assertTrue((new \ReflectionClass(BulkCommand::class))->isReadOnly());
        $this->assertTrue((new \ReflectionClass(FilterContext::class))->isReadOnly());
        $this->assertInstanceOf(FilterContext::class, new Filter_Context('student', null, [], 'id', 'desc', 'fp'));
    }

    public function test_filter_context_rejects_pagination_and_bulk_results_conserve_counts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FilterContext('teacher', null, ['page' => 2], 'id', 'desc', 'fp');
    }

    public function test_bulk_result_contains_unique_item_reasons_and_enum_outcome(): void
    {
        $items = [
            new BulkItemResultData(10, BulkItemResultStatusEnum::Succeeded),
            new BulkItemResultData(11, 'failed', 'protected_dependency', 'Record is protected.', 'protected_dependency'),
        ];
        $result = new BulkResultData(
            entity: 'student',
            action: 'delete',
            mode: 'current_page',
            total: 2,
            succeeded: 1,
            skipped: 0,
            failed: 1,
            items: $items,
            selection_reference: 'selection-10-11',
        );

        $this->assertSame(BulkResultOutcomeEnum::PartialSuccess, $result->outcome);
        $this->assertSame('protected_dependency', $result->toArray()['items'][1]['reason']['category']);
        $this->assertSame($result->toArray(), json_decode((string) json_encode($result), true));
        $this->assertTrue((new \ReflectionClass(BulkResultData::class))->isReadOnly());
    }

    public function test_session_and_room_contracts_preserve_enum_driven_states(): void
    {
        $room = new RoomOptionData(8, 'Room A', true, RoomOptionModeEnum::SessionInput);
        $view = new SessionEditViewData(
            session_id: 20,
            values: ['room' => 'Room A'],
            room_options: [$room],
            room_resolution: RoomResolutionEnum::ResolvedActive,
            policy_flags: ['update' => true],
        );
        $edit = new SessionEditResource(
            session_id: 20,
            student_id: 2,
            teacher_id: 3,
            instrument_id: 4,
            session_date: '2026-07-14',
            start_time: '16:00',
            duration_minutes: 60,
            status: SessionStatusEnum::Scheduled,
            room: 'Room A',
            room_resolution: 'resolved_active',
            room_id: 8,
            updated_at: '2026-07-14T12:00:00Z',
        );
        $notes = new SessionNotesResource(20, 'Persisted note', 'Persisted note', 'version-1', true, 'Saved.');

        $this->assertTrue($view->allows('update'));
        $this->assertSame('scheduled', $edit->toArray()['status']);
        $this->assertSame('resolved_active', $edit->toArray()['room_resolution']);
        $this->assertSame('Persisted note', $notes->toArray()['notes']);
        $this->assertSame('session_input', $room->toArray()['mode']);
    }

    public function test_calendar_event_is_fullcalendar_serializable_without_sensitive_fields(): void
    {
        $event = new CalendarEventData(
            id: 42,
            title: 'Student — Piano',
            start: '2026-07-14T16:00:00',
            end: '2026-07-14T17:00:00',
            status: SessionStatusEnum::Scheduled,
            status_label: 'Scheduled',
            student_name: 'Student',
            teacher_name: 'Teacher',
            instrument_name: 'Piano',
            room: 'Room A',
            room_resolution: RoomResolutionEnum::ResolvedActive,
            room_id: 8,
            can_update_notes: true,
            duration_minutes: 60,
            enrollment_id: 15,
            notes: 'Practice scales',
            notes_updated_at: '2026-07-14T12:00:00Z',
            session_date: '2026-07-14',
        );
        $payload = $event->toArray();

        $this->assertSame(42, $payload['id']);
        $this->assertSame('resolved_active', $payload['roomResolution']);
        $this->assertSame('Practice scales', $payload['extendedProps']['notes']);
        $this->assertArrayNotHasKey('session_fee', $payload);
        $this->assertTrue((new \ReflectionClass(CalendarEventData::class))->isReadOnly());
    }
}
