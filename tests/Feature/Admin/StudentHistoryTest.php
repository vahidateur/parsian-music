<?php

namespace Tests\Feature\Admin;

use App\DTOs\RecordDetailData;
use App\DTOs\RecordDetailSection;
use App\Enums\AttendanceStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\SessionStatusEnum;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Details\StudentDetailQuery;
use App\Services\StudentHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Student $student;
    private StudentEnrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => RoleEnum::ADMIN]);

        $this->student = Student::forceCreate([
            'student_code' => 'S-00001',
            'full_name' => 'Test Student',
            'phone' => '09120000001',
            'status' => 'active',
            'join_date' => now(),
        ]);

        $teacher = Teacher::forceCreate([
            'teacher_code' => 'T-00001',
            'full_name' => 'Test Teacher',
            'phone' => '09120000002',
            'status' => 'active',
        ]);

        $instrument = Instrument::create([
            'name' => 'Piano',
            'name_fa' => 'پیانو',
            'slug' => 'piano',
            'is_active' => true,
        ]);

        $this->enrollment = StudentEnrollment::create([
            'student_id' => $this->student->id,
            'instrument_id' => $instrument->id,
            'teacher_id' => $teacher->id,
            'skill_level' => 'beginner',
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    /** Timeline always has at least student_created. */
    public function test_timeline_contains_student_created(): void
    {
        $service = new StudentHistoryService();
        $timeline = $service->buildTimeline($this->student);

        $this->assertNotEmpty($timeline);
        $types = $timeline->pluck('type');
        $this->assertContains('student_created', $types);
    }

    /** Enrollment created event appears in timeline. */
    public function test_timeline_contains_enrollment_created(): void
    {
        $service = new StudentHistoryService();
        $timeline = $service->buildTimeline($this->student);

        $this->assertContains('enrollment_created', $timeline->pluck('type'));
    }

    /** Completed session appears in timeline. */
    public function test_timeline_contains_session_completed(): void
    {
        ClassSession::create([
            'enrollment_id' => $this->enrollment->id,
            'session_date' => now()->subDay(),
            'start_time' => '16:00',
            'duration_minutes' => 60,
            'room' => 'A101',
            'status' => SessionStatusEnum::Completed->value,
        ]);

        $service = new StudentHistoryService();
        $timeline = $service->buildTimeline($this->student);

        $this->assertContains('session_completed', $timeline->pluck('type'));
    }

    /** Absent attendance appears in timeline. */
    public function test_timeline_contains_attendance_absent(): void
    {
        $session = ClassSession::create([
            'enrollment_id' => $this->enrollment->id,
            'session_date' => now()->subDay(),
            'start_time' => '16:00',
            'duration_minutes' => 60,
            'room' => 'A101',
            'status' => SessionStatusEnum::Completed->value,
        ]);

        ClassAttendance::create([
            'class_session_id' => $session->id,
            'student_id' => $this->student->id,
            'status' => AttendanceStatusEnum::Absent->value,
        ]);

        $service = new StudentHistoryService();
        $timeline = $service->buildTimeline($this->student);

        $this->assertContains('attendance_marked', $timeline->pluck('type'));
    }

    /** Timeline events are sorted newest-first. */
    public function test_timeline_is_sorted_newest_first(): void
    {
        $service = new StudentHistoryService();
        $timeline = $service->buildTimeline($this->student);

        $timestamps = $timeline->pluck('timestamp')->map(fn ($ts) => $ts->timestamp)->values();
        $sorted = $timestamps->sortDesc()->values();
        $this->assertEquals($sorted->toArray(), $timestamps->toArray());
    }

    /** Student show page renders 200 and includes timeline section. */
    public function test_student_show_page_renders_timeline(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.students.show', $this->student));

        $response->assertOk();
        // The timeline heading renders the LOCALIZED value of admin.student_history,
        // never the raw key. Both expected strings are plain Persian text with no
        // HTML-escapable characters, so the escaping-aware default assertSee is used.
        $response->assertSee(__('admin.student_history'));
        // The student_created event renders its localized event-type label.
        $response->assertSee(__('admin.history_event_types.student_created'));
    }

    /** The history section exposes the stable machine-readable identifier. */
    public function test_student_show_exposes_stable_history_section_identifier(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.students.show', $this->student));

        $response->assertOk();
        $response->assertSee('id="student_history"', false);
        $response->assertSee('data-section="student_history"', false);
    }

    /** Events sharing a timestamp keep one deterministic order across requests. */
    public function test_history_order_is_deterministic_for_equal_timestamps(): void
    {
        $sharedMoment = now()->subHour();

        foreach ([1, 2, 3] as $ignored) {
            ClassSession::create([
                'enrollment_id' => $this->enrollment->id,
                'session_date' => now()->subDay(),
                'start_time' => '16:00',
                'duration_minutes' => 60,
                'room' => 'A101',
                'status' => SessionStatusEnum::Completed->value,
                'created_at' => $sharedMoment,
                'updated_at' => $sharedMoment,
            ]);
        }

        $service = new StudentHistoryService();
        $firstKeys = $service->buildTimeline($this->student)->pluck('key')->all();
        $secondKeys = $service->buildTimeline($this->student->fresh())->pluck('key')->all();

        $this->assertSame($firstKeys, $secondKeys);
        $this->assertSame(array_values(array_unique($firstKeys)), $firstKeys);

        // Rendered order matches the resolved order of persisted events.
        $response = $this->actingAs($this->admin)
            ->get(route('admin.students.show', $this->student));

        $response->assertOk();
        preg_match_all('/data-history-key="([^"]+)"/', $response->getContent(), $matches);
        $this->assertSame($firstKeys, $matches[1]);
    }

    /** Rendered history order is identical across repeated requests. */
    public function test_rendered_history_order_is_identical_across_repeated_requests(): void
    {
        $sharedMoment = now()->subHour();

        foreach ([1, 2, 3] as $ignored) {
            ClassSession::create([
                'enrollment_id' => $this->enrollment->id,
                'session_date' => now()->subDay(),
                'start_time' => '16:00',
                'duration_minutes' => 60,
                'room' => 'A101',
                'status' => SessionStatusEnum::Completed->value,
                'created_at' => $sharedMoment,
                'updated_at' => $sharedMoment,
            ]);
        }

        $renderedKeys = function (): array {
            $response = $this->actingAs($this->admin)
                ->get(route('admin.students.show', $this->student));

            $response->assertOk();
            preg_match_all('/data-history-key="([^"]+)"/', $response->getContent(), $matches);

            return $matches[1];
        };

        $first = $renderedKeys();
        $second = $renderedKeys();

        $this->assertNotEmpty($first);
        $this->assertSame($first, $second);
        $this->assertSame(array_values(array_unique($first)), $first);
    }

    /** Absent history renders the shared Empty_State component. */
    public function test_empty_history_renders_shared_empty_state(): void
    {
        $detail = new RecordDetailData(
            entity: 'students',
            id: $this->student->id,
            label: (string) $this->student->full_name,
            sections: [
                new RecordDetailSection(
                    id: StudentDetailQuery::SECTION_HISTORY,
                    title: __('admin.student_history'),
                    empty_message: __('admin.no_history_events'),
                ),
            ],
            placeholder: __('admin.value_not_provided'),
        );

        $rendered = view('admin.partials.timeline', [
            'detail' => $detail,
            'section' => $detail->section(StudentDetailQuery::SECTION_HISTORY),
        ])->render();

        $this->assertStringContainsString('ui-empty-state', $rendered);
        $this->assertStringContainsString(__('admin.no_history_events'), $rendered);
        $this->assertStringContainsString('id="student_history"', $rendered);
        $this->assertStringContainsString('data-section="student_history"', $rendered);
    }

    /** Absent persisted values render the localized placeholder. */
    public function test_absent_values_render_the_localized_placeholder(): void
    {
        $this->student->update(['parent_phone' => null, 'notes' => null]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.students.show', $this->student));

        $response->assertOk();
        $response->assertSee('id="student_profile"', false);
        $response->assertSee(__('admin.value_not_provided'));
    }

    /** The detail screen keeps exactly one h1. */
    public function test_detail_renders_exactly_one_h1(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.students.show', $this->student));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
    }
}
