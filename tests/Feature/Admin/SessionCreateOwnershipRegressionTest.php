<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\DTOs\RoomOptionData;
use App\Enums\RoleEnum;
use App\Enums\RoomOptionModeEnum;
use App\Enums\RoomResolutionEnum;
use App\Models\Instrument;
use App\Models\Room;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherInstrument;
use App\Models\User;
use App\Services\RoomOptionProvider;
use App\Services\RoomResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SessionCreateOwnershipRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_provider_prepares_active_room_and_teacher_instrument_data(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = Teacher::factory()->active()->create(['full_name' => 'Owner Teacher']);
        $instrument = Instrument::factory()->create(['name' => 'Piano', 'name_fa' => 'پیانو', 'is_active' => true]);
        TeacherInstrument::factory()->withParents([
            'teacher' => $teacher,
            'instrument' => $instrument,
        ])->create();
        Room::factory()->named('Active Room')->active()->create();
        Room::factory()->named('Historical Room')->inactive()->create();

        $response = $this->actingAs($admin)->get(route('admin.sessions.create'));

        $response->assertOk()
            ->assertSee('Active Room')
            ->assertDontSee('Historical Room')
            ->assertSee('Owner Teacher')
            ->assertSee('teacher-instruments', false);
    }

    public function test_room_owner_contract_classifies_active_inactive_and_unresolved_values(): void
    {
        Room::factory()->named('Active Room')->active()->create();
        Room::factory()->named('Historical Room')->inactive()->create();
        $resolver = app(RoomResolver::class);

        $this->assertSame(RoomResolutionEnum::ResolvedActive, $resolver->resolve('Active Room'));
        $this->assertSame(RoomResolutionEnum::ResolvedInactive, $resolver->resolve('Historical Room'));
        $this->assertSame(RoomResolutionEnum::UnresolvedLegacy, $resolver->resolve('Legacy Room'));
        $this->assertNull($resolver->resolve(null));
    }

    public function test_room_option_provider_emits_only_persisted_active_session_options(): void
    {
        $active = Room::factory()->named('Active Room')->active()->create();
        Room::factory()->named('Historical Room')->inactive()->create();

        $options = app(RoomOptionProvider::class)->forSessionInput();

        $this->assertCount(1, $options);
        $this->assertInstanceOf(RoomOptionData::class, $options[0]);
        $this->assertSame($active->id, $options[0]->id);
        $this->assertSame(RoomOptionModeEnum::SessionInput, $options[0]->mode);
        $this->assertTrue($options[0]->is_active);
    }

    public function test_create_rejects_an_inactive_room_before_persistence(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create();
        $teacher = Teacher::factory()->active()->create();
        $instrument = Instrument::factory()->create(['is_active' => true]);
        Room::factory()->named('Historical Room')->inactive()->create();

        $response = $this->actingAs($admin)->post(route('admin.sessions.store'), [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'instrument_id' => $instrument->id,
            'session_date' => today()->addDay()->toDateString(),
            'start_time' => '16:00',
            'duration_minutes' => 60,
            'room' => 'Historical Room',
        ]);

        $response->assertSessionHasErrors('room');
        $this->assertDatabaseCount('class_sessions', 0);
    }

    public function test_non_admin_cannot_open_or_submit_session_create(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)->get(route('admin.sessions.create'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.sessions.store'), [])->assertForbidden();
    }

    public function test_create_view_has_no_query_or_session_owned_inline_script(): void
    {
        $source = (string) file_get_contents(resource_path('views/admin/sessions/create.blade.php'));
        $this->assertStringNotContainsString('DB::', $source);
        $this->assertStringNotContainsString('Illuminate\\Support\\Facades\\DB', $source);
        $this->assertStringNotContainsString('<script', $source);
        $this->assertStringNotContainsString('teacherInstrumentMap', $source);
        $this->assertStringContainsString('x-data="sessionCreate"', $source);
    }

    public function test_app_registers_exactly_one_session_create_owner_module(): void
    {
        $app = (string) file_get_contents(resource_path('js/app.js'));
        $modules = glob(resource_path('js/*session*create*.js')) ?: [];

        $this->assertSame([resource_path('js/session-create.js')], $modules);
        $this->assertSame(1, substr_count($app, "Alpine.data('sessionCreate'"));
        $this->assertStringContainsString("import sessionCreate from './session-create'", $app);
    }
}
