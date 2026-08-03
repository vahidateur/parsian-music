<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatusEnum;
use App\Enums\EnrollmentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\LeadStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\SessionStatusEnum;
use App\Enums\StudentStatusEnum;
use App\Enums\TeacherStatusEnum;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Lead;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Factory state contract: one state per Enum value, explicit parent states and
 * separate independent / deletion-dependency states.
 */
class AdminFactoryStatesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * State names are the camelCase form of the Enum value, except for values
     * colliding with a framework method (`Factory::new()`).
     *
     * @var array<string, string>
     */
    private const STATE_ALIASES = [
        LeadStatusEnum::class . '::new' => 'statusNew',
    ];

    /**
     * @return array<string, array{0: class-string, 1: class-string, 2: string}>
     */
    public static function enumStateProvider(): array
    {
        return [
            'student status' => [Student::class, StudentStatusEnum::class, 'status'],
            'teacher status' => [Teacher::class, TeacherStatusEnum::class, 'status'],
            'enrollment status' => [StudentEnrollment::class, EnrollmentStatusEnum::class, 'status'],
            'session status' => [ClassSession::class, SessionStatusEnum::class, 'status'],
            'attendance status' => [ClassAttendance::class, AttendanceStatusEnum::class, 'status'],
            'invoice status' => [Invoice::class, InvoiceStatusEnum::class, 'status'],
            'payment status' => [InvoicePayment::class, PaymentStatusEnum::class, 'status'],
            'lead status' => [Lead::class, LeadStatusEnum::class, 'status'],
            'user role' => [User::class, RoleEnum::class, 'role'],
        ];
    }

    /**
     * @param  class-string  $modelClass
     * @param  class-string  $enumClass
     */
    #[DataProvider('enumStateProvider')]
    public function test_factory_exposes_one_state_per_enum_value(
        string $modelClass,
        string $enumClass,
        string $column
    ): void {
        foreach ($enumClass::cases() as $case) {
            $state = self::STATE_ALIASES[$enumClass . '::' . $case->value] ?? Str::camel($case->value);
            $factory = $modelClass::factory();

            $this->assertTrue(
                method_exists($factory, $state),
                $modelClass . ' factory is missing the [' . $state . '] state.'
            );

            $record = $factory->{$state}()->create();

            $this->assertDatabaseHas($record->getTable(), [
                $record->getKeyName() => $record->getKey(),
                $column => $case->value,
            ]);
        }
    }

    public function test_new_parent_state_creates_every_required_parent(): void
    {
        $enrollment = StudentEnrollment::factory()->withNewParents()->create();

        $this->assertDatabaseHas('students', ['id' => $enrollment->student_id]);
        $this->assertDatabaseHas('teachers', ['id' => $enrollment->teacher_id]);
        $this->assertDatabaseHas('instruments', ['id' => $enrollment->instrument_id]);
    }

    public function test_supplied_parents_are_reused_without_duplicates(): void
    {
        $student = Student::factory()->create();
        $teacher = Teacher::factory()->create();
        $instrument = Instrument::factory()->create();

        $enrollment = StudentEnrollment::factory()->withParents([
            'student' => $student,
            'teacher' => $teacher,
            'instrument' => $instrument,
        ])->create();

        $this->assertSame($student->id, $enrollment->student_id);
        $this->assertSame($teacher->id, $enrollment->teacher_id);
        $this->assertSame($instrument->id, $enrollment->instrument_id);
        $this->assertSame(1, Student::count());
        $this->assertSame(1, Teacher::count());
        $this->assertSame(1, Instrument::count());
    }

    public function test_deletion_dependency_and_independent_states_are_separate(): void
    {
        $this->assertTrue(Teacher::factory()->withDeletionDependency()->create()->enrollments()->exists());
        $this->assertFalse(Teacher::factory()->independent()->create()->enrollments()->exists());

        $this->assertTrue(Student::factory()->withDeletionDependency()->create()->enrollments()->exists());
        $this->assertFalse(Student::factory()->independent()->create()->enrollments()->exists());

        $this->assertTrue(Instrument::factory()->withDeletionDependency()->create()->teachers()->exists());
        $this->assertFalse(Instrument::factory()->independent()->create()->teachers()->exists());

        $this->assertTrue(StudentEnrollment::factory()->withDeletionDependency()->create()->classSessions()->exists());
        $this->assertFalse(StudentEnrollment::factory()->independent()->create()->classSessions()->exists());

        $this->assertTrue(ClassSession::factory()->withDeletionDependency()->create()->attendances()->exists());
        $this->assertFalse(ClassSession::factory()->independent()->create()->attendances()->exists());

        $this->assertTrue(Invoice::factory()->withDeletionDependency()->create()->payments()->exists());
        $this->assertFalse(Invoice::factory()->independent()->create()->payments()->exists());

        $this->assertNotNull(User::factory()->withDeletionDependency()->create()->teacher);
        $this->assertNull(User::factory()->independent()->create()->teacher);
    }
}
