<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleEnum;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Room;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Lists\InstrumentListQuery;
use App\Services\Lists\RoomListQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Authorization_Layer contract: every operational entity has a registered policy
 * exposing named CRUD and non-CRUD abilities, and the secretary persona resolves
 * through the `admin` role without a new RoleEnum value.
 */
class PolicyAbilityRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** entity => [CRUD + non-CRUD named abilities] */
    private const ABILITY_MAP = [
        Teacher::class => ['viewAny', 'view', 'create', 'update', 'delete', 'manageInstruments', 'attachInstrument', 'detachInstrument', 'changeStatus'],
        Student::class => ['viewAny', 'view', 'create', 'update', 'delete', 'changeStatus', 'assign'],
        ClassSession::class => ['viewAny', 'view', 'create', 'update', 'delete', 'markAttendance', 'changeStatus', 'assign', 'generate'],
        StudentEnrollment::class => ['viewAny', 'view', 'create', 'update', 'delete', 'changeStatus', 'assign'],
        Room::class => ['viewAny', 'view', 'create', 'update', 'delete', 'toggle'],
        Instrument::class => ['viewAny', 'view', 'create', 'update', 'delete', 'toggle'],
        Invoice::class => ['viewAny', 'view', 'create', 'update', 'delete', 'issue', 'cancel', 'duplicate', 'registerPayment', 'deletePayment'],
        Lead::class => ['viewAny', 'view', 'create', 'update', 'delete', 'convert', 'assign', 'changeStatus', 'scheduleFollowUp'],
        User::class => ['viewAny', 'view', 'create', 'update', 'delete', 'toggle', 'assign', 'resetPassword'],
    ];

    public function test_every_operational_entity_has_a_registered_policy_with_named_abilities(): void
    {
        foreach (self::ABILITY_MAP as $model => $abilities) {
            $policy = Gate::getPolicyFor($model);

            $this->assertNotNull($policy, "No policy registered for {$model}.");

            foreach ($abilities as $ability) {
                $this->assertTrue(
                    method_exists($policy, $ability),
                    sprintf('%s is missing the named ability "%s".', $model, $ability)
                );
            }
        }
    }

    public function test_secretary_persona_resolves_through_the_admin_role(): void
    {
        // The secretary persona has no RoleEnum value of its own; it maps to admin.
        $this->assertNotContains('secretary', RoleEnum::values());

        $secretary = User::factory()->create(['role' => RoleEnum::ADMIN]);

        foreach ($this->records() as $model => $record) {
            $abilities = array_filter(
                self::ABILITY_MAP[$model],
                fn (string $ability): bool => $ability !== 'delete' && $ability !== 'viewAny' && $ability !== 'create'
            );

            $this->assertTrue(
                Gate::forUser($secretary)->allows('viewAny', $model),
                sprintf('Admin persona cannot viewAny %s.', $model)
            );
            $this->assertTrue(
                Gate::forUser($secretary)->allows('create', $model),
                sprintf('Admin persona cannot create %s.', $model)
            );

            foreach ($abilities as $ability) {
                $this->assertTrue(
                    Gate::forUser($secretary)->allows($ability, $record),
                    sprintf('Admin persona is denied "%s" on %s.', $ability, $model)
                );
            }
        }
    }

    public function test_student_actor_is_denied_every_operational_ability(): void
    {
        $student = User::factory()->create(['role' => RoleEnum::STUDENT]);

        foreach ($this->records() as $model => $record) {
            if ($model === User::class) {
                continue; // Self-service abilities are covered by the user hierarchy.
            }

            foreach (self::ABILITY_MAP[$model] as $ability) {
                $target = in_array($ability, ['viewAny', 'create', 'generate'], true) ? $model : $record;

                $this->assertFalse(
                    Gate::forUser($student)->allows($ability, $target),
                    sprintf('Student actor was granted "%s" on %s.', $ability, $model)
                );
            }
        }
    }

    public function test_registering_room_and_instrument_policies_keeps_admin_list_controls_visible(): void
    {
        $admin = User::factory()->create(['role' => RoleEnum::ADMIN]);
        Room::factory()->create();
        Instrument::factory()->create();

        $rooms = app(RoomListQuery::class)->forInput([], $admin);
        $instruments = app(InstrumentListQuery::class)->forInput([], $admin);

        $this->assertTrue($rooms->allows('create'));
        $this->assertContains('update', $rooms->rows[0]->allowed_actions);
        $this->assertContains('delete', $rooms->rows[0]->allowed_actions);

        $this->assertTrue($instruments->allows('create'));
        $this->assertContains('update', $instruments->rows[0]->allowed_actions);
        $this->assertContains('delete', $instruments->rows[0]->allowed_actions);
    }

    /**
     * @return array<class-string, \Illuminate\Database\Eloquent\Model>
     */
    private function records(): array
    {
        return [
            Teacher::class => Teacher::factory()->create(),
            Student::class => Student::factory()->create(),
            ClassSession::class => ClassSession::factory()->create(),
            StudentEnrollment::class => StudentEnrollment::factory()->create(),
            Room::class => Room::factory()->create(),
            Instrument::class => Instrument::factory()->create(),
            Invoice::class => Invoice::factory()->create(),
            // assigned_to stays null so the owner short-circuit cannot mask a denial.
            Lead::class => Lead::factory()->create(['assigned_to' => null]),
            User::class => User::factory()->create(['role' => RoleEnum::TEACHER]),
        ];
    }
}
