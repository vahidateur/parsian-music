<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\RoleEnum;
use App\Models\Instrument;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Record_Detail contract of `admin.teachers.show`.
 *
 * **Validates: Requirements 2.1, 2.2, 6.1, 6.11, 7.1**
 */
class TeacherDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => RoleEnum::ADMIN]);
    }

    public function test_detail_renders_name_status_and_persisted_profile_values(): void
    {
        $teacher = Teacher::factory()->create([
            'full_name' => 'استاد آزمون',
            'teacher_code' => 'T-90001',
            'phone' => '09121110001',
            'status' => 'active',
            'bio' => 'رزومه ثبت‌شده',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.teachers.show', $teacher));

        $response->assertOk();
        $response->assertSee('استاد آزمون');
        $response->assertSee(__('admin.statuses.active'));
        $response->assertSee('T-90001');
        $response->assertSee('09121110001');
        $response->assertSee('رزومه ثبت‌شده');
        $response->assertSee('id="teacher_profile"', false);
        $response->assertSee('data-section="teacher_profile"', false);
    }

    public function test_detail_renders_related_operational_data(): void
    {
        $teacher = Teacher::factory()->create(['status' => 'active']);
        $instrument = Instrument::create([
            'name' => 'Violin',
            'name_fa' => 'ویولن',
            'slug' => 'violin-detail-test',
            'is_active' => true,
        ]);
        $student = Student::factory()->create(['full_name' => 'هنرجوی آزمون']);

        $teacher->instruments()->attach($instrument->id, [
            'skill_level' => 'advanced',
            'is_primary' => true,
        ]);

        StudentEnrollment::create([
            'student_id' => $student->id,
            'instrument_id' => $instrument->id,
            'teacher_id' => $teacher->id,
            'skill_level' => 'beginner',
            'status' => 'active',
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.teachers.show', $teacher));

        $response->assertOk();
        $response->assertSee('id="teacher_instruments"', false);
        $response->assertSee('id="teacher_enrollments"', false);
        $response->assertSee('ویولن');
        $response->assertSee(__('admin.skill_levels.advanced'));
        $response->assertSee(__('admin.primary'));
        $response->assertSee('هنرجوی آزمون');
    }

    public function test_absent_values_render_the_localized_placeholder(): void
    {
        $teacher = Teacher::factory()->create([
            'status' => 'active',
            'bio' => null,
            'hire_date' => null,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.teachers.show', $teacher));

        $response->assertOk();
        $response->assertSee(__('admin.value_not_provided'));
    }

    public function test_empty_related_sections_render_the_shared_empty_state(): void
    {
        $teacher = Teacher::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->admin)->get(route('admin.teachers.show', $teacher));

        $response->assertOk();
        $response->assertSee('ui-empty-state', false);
        $response->assertSee(__('admin.no_instruments_assigned_yet'));
        $response->assertSee(__('admin.no_enrollments_yet'));
    }

    public function test_unknown_identifier_returns_not_found(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.teachers.show', 999999));

        $response->assertNotFound();
    }

    public function test_detail_renders_exactly_one_h1(): void
    {
        $teacher = Teacher::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->admin)->get(route('admin.teachers.show', $teacher));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
    }
}
