<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\RoleEnum;
use App\Http\Requests\Admin\AdminFormRequest;
use App\Models\Instrument;
use App\Models\Room;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Record_Form validation contract: every owned admin form validates through a
 * Form Request before any write, invalid input comes back with the submitted
 * values retained and a localized message bound to the field.
 *
 * Requirements: 6.5, 6.7
 */
class AdminFormRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    /** controller => [action, ...] — every owned mutation that reads form input. */
    private const OWNED_FORM_ACTIONS = [
        \App\Http\Controllers\Admin\TeacherController::class => ['store', 'update', 'attachInstrument', 'detachInstrument'],
        \App\Http\Controllers\Admin\StudentController::class => ['store', 'update'],
        \App\Http\Controllers\Admin\InstrumentController::class => ['store', 'update'],
        \App\Http\Controllers\Admin\RoomController::class => ['store', 'update'],
        \App\Http\Controllers\Admin\StudentEnrollmentController::class => ['store', 'update'],
        \App\Http\Controllers\Admin\InvoiceController::class => ['store', 'update'],
        \App\Http\Controllers\Admin\InvoicePaymentController::class => ['store'],
        \App\Http\Controllers\Admin\LeadController::class => ['store', 'update', 'assign', 'scheduleFollowUp', 'updateStatus', 'convert'],
        \App\Http\Controllers\Admin\UserController::class => ['store', 'update'],
    ];

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => RoleEnum::ADMIN]);
    }

    public function test_every_owned_form_action_receives_a_form_request(): void
    {
        foreach (self::OWNED_FORM_ACTIONS as $controller => $actions) {
            foreach ($actions as $action) {
                $parameters = (new ReflectionMethod($controller, $action))->getParameters();
                $types = [];

                foreach ($parameters as $parameter) {
                    $type = $parameter->getType();

                    if ($type !== null && method_exists($type, 'getName')) {
                        $types[] = $type->getName();
                    }
                }

                $formRequests = array_filter(
                    $types,
                    static fn (string $type): bool => is_a($type, AdminFormRequest::class, true)
                );

                $this->assertCount(
                    1,
                    $formRequests,
                    sprintf('%s@%s must declare exactly one admin Form Request parameter.', $controller, $action)
                );
            }
        }
    }

    public function test_owned_controllers_contain_no_inline_validation(): void
    {
        foreach (array_keys(self::OWNED_FORM_ACTIONS) as $controller) {
            $file = (new \ReflectionClass($controller))->getFileName();
            $this->assertIsString($file);

            $source = (string) file_get_contents($file);

            $this->assertStringNotContainsString(
                '$request->validate(',
                $source,
                sprintf('%s still validates inline instead of through a Form Request.', $controller)
            );
        }
    }

    public function test_invalid_record_form_returns_old_input_and_localized_field_errors(): void
    {
        $existing = Teacher::factory()->create();

        $response = $this->actingAs($this->admin)
            ->from(route('admin.teachers.create'))
            ->post(route('admin.teachers.store'), [
                'full_name' => '',
                'phone' => $existing->phone,
                'status' => 'not-a-status',
            ]);

        $response->assertRedirect(route('admin.teachers.create'))
            ->assertSessionHasErrors(['full_name', 'phone', 'status'])
            ->assertSessionHasInput('phone', $existing->phone);

        $errors = session('errors');

        foreach (['full_name', 'phone', 'status'] as $field) {
            $this->assertMatchesRegularExpression(
                '/[\x{0600}-\x{06FF}]/u',
                (string) $errors->first($field),
                sprintf('The error message for "%s" is not localized.', $field)
            );
        }

        $this->assertSame(1, Teacher::count());
    }

    public function test_enum_membership_and_relationship_existence_are_enforced(): void
    {
        $student = Student::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.students.update', $student), [
                'full_name' => $student->full_name,
                'phone' => $student->phone,
                'status' => 'archived-forever',
            ])
            ->assertSessionHasErrors('status');

        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.store'), [
                'student_id' => $student->id,
                'teacher_id' => 9999,
                'instrument_id' => Instrument::factory()->create()->id,
            ])
            ->assertSessionHasErrors('teacher_id');
    }

    public function test_numeric_bounds_and_uniqueness_are_enforced_on_room_form(): void
    {
        $room = Room::factory()->create(['name' => 'A101']);

        $this->actingAs($this->admin)
            ->post(route('admin.rooms.store'), ['name' => 'A101', 'capacity' => 4])
            ->assertSessionHasErrors('name');

        $this->actingAs($this->admin)
            ->post(route('admin.rooms.store'), ['name' => 'B201', 'capacity' => 2147483648])
            ->assertSessionHasErrors('capacity');

        $this->actingAs($this->admin)
            ->put(route('admin.rooms.update', $room), ['name' => 'A101', 'capacity' => 6])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Room::count());
        $this->assertSame(6, (int) $room->refresh()->capacity);
    }

    /**
     * The persisted English name is `name ?: name_fa` and the `name` column is
     * unique in the database, so every path that would collide there must fail
     * validation with a localized field error instead of reaching the constraint.
     */
    public function test_english_instrument_name_uniqueness_is_enforced_including_the_persian_fallback(): void
    {
        $existing = Instrument::create([
            'name' => 'Setar',
            'name_fa' => 'سه‌تار',
            'slug' => 'setar',
            'is_active' => true,
        ]);

        // (a) duplicate English name on create: rejected, nothing written.
        $response = $this->actingAs($this->admin)
            ->from(route('admin.instruments.index'))
            ->post(route('admin.instruments.store'), [
                'name' => 'Setar',
                'name_fa' => 'سه تار دیگر',
                'is_active' => '1',
            ]);

        $response->assertSessionHasErrors('name');
        $this->assertMatchesRegularExpression(
            '/[\x{0600}-\x{06FF}]/u',
            (string) session('errors')->first('name'),
            'The duplicate English name error is not localized.',
        );
        $this->assertSame(1, Instrument::count());

        // (b) update resubmitting the record's own English name passes.
        $this->actingAs($this->admin)
            ->put(route('admin.instruments.update', $existing), [
                'name' => 'Setar',
                'name_fa' => 'سه‌تار',
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();

        // (c) fallback path: blank English name whose Persian name would be
        // persisted into the unique `name` column of another record.
        $fallbackCollision = Instrument::create([
            'name' => 'کمانچه',
            'name_fa' => 'کمانچه قدیمی',
            'slug' => 'kamancheh',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->from(route('admin.instruments.index'))
            ->post(route('admin.instruments.store'), [
                'name' => '   ',
                'name_fa' => 'کمانچه',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('name_fa');

        $this->assertSame(2, Instrument::count());
        $this->assertSame('کمانچه', $fallbackCollision->refresh()->name);
    }
}
