<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatusEnum;
use App\Enums\EnrollmentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\LeadPriorityEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\LeadStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\SessionStatusEnum;
use App\Enums\SkillLevelEnum;
use App\Enums\StudentStatusEnum;
use App\Enums\TeacherStatusEnum;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Lead;
use App\Models\Room;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\TeacherInstrument;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Deterministic, idempotent seeder for automated tests.
 *
 * Contract (Requirements 1.7, 4.8, 4.10):
 * - Every record is addressed by a stable natural key, so a second run updates
 *   the same rows instead of inserting duplicates (no duplicate-key failure,
 *   no unbounded growth).
 * - No randomness and no `now()`/`today()` dependent values: the same input
 *   state always yields the same record set.
 * - Fully independent of `DemoSeeder` and of any development data: it never
 *   reads existing rows other than its own prefixed records.
 *
 * Not registered in `DatabaseSeeder`; tests call it explicitly with
 * `$this->seed(TestDataSeeder::class)`.
 */
class TestDataSeeder extends Seeder
{
    /** Prefix used by every natural key this seeder owns. */
    public const KEY_PREFIX = 'TSEED';

    /** Fixed anchor date, so seeded dates never depend on the current day. */
    public const ANCHOR_DATE = '2026-01-05';

    /** Shared plaintext password of every seeded account. */
    public const PASSWORD = 'password';

    /** Existing centralized browser-test credential contract. */
    public const E2E_PHONE_ENV = 'TEST_ADMIN_PHONE';

    public const E2E_PASSWORD_ENV = 'TEST_ADMIN_PASSWORD';

    /** Non-sensitive marker used to distinguish the fixture from real users. */
    public const E2E_EMAIL = 'e2e-admin@parsian-music.test';

    /**
     * Create or refresh only the canonical browser-suite admin account.
     *
     * The canonical email is the stable fixture identity. The configured phone
     * is a mutable login credential, so an email-owned fixture may be reconciled
     * when its phone has changed. Existing records are reconciled only when the
     * canonical email and fixture marker prove ownership; every other identity
     * collision fails before any record is changed.
     *
     * This entry point deliberately does not call run(): browser tests need one
     * account and must not seed demo or broad test data into a local database.
     *
     * @throws \RuntimeException when the environment is not explicitly allowed,
     *                            credentials are absent, or an identity conflict
     *                            cannot be proven to belong to this fixture.
     */
    public function seedE2EAdmin(): User
    {
        $environment = (string) config('app.env');

        if (! in_array($environment, ['local', 'testing', 'e2e'], true)) {
            throw new \RuntimeException('E2E admin fixture seeding is allowed only in local, testing, or e2e environments.');
        }

        $phone = trim((string) env(self::E2E_PHONE_ENV));
        $password = (string) env(self::E2E_PASSWORD_ENV);

        if ($phone === '' || $password === '') {
            throw new \RuntimeException('E2E admin fixture requires TEST_ADMIN_PHONE and TEST_ADMIN_PASSWORD configuration.');
        }

        return DB::transaction(function () use ($phone, $password): User {
            $canonicalUser = User::query()->where('email', self::E2E_EMAIL)->first();
            $phoneUser = User::query()->where('phone', $phone)->first();

            if ($canonicalUser !== null && ! $this->isE2EOwned($canonicalUser)) {
                throw new \RuntimeException('Canonical E2E admin fixture email belongs to an unrelated user; no account was changed.');
            }

            if ($phoneUser !== null && ($canonicalUser === null || ! $phoneUser->is($canonicalUser))) {
                throw new \RuntimeException('Configured E2E admin phone belongs to an unrelated user; no account was changed.');
            }

            $user = $canonicalUser ?? new User();
            $attributes = [
                'full_name' => 'E2E Admin Fixture',
                'phone' => $phone,
                'email' => self::E2E_EMAIL,
                'role' => RoleEnum::ADMIN->value,
                'is_active' => true,
                'force_password_change' => false,
                'login_attempts' => 0,
                'locked_until' => null,
            ];

            $storedPassword = (string) $user->getRawOriginal('password');
            if ($storedPassword === '' || ! Hash::check($password, $storedPassword)) {
                $attributes['password'] = Hash::make($password);
            }

            $user->forceFill($attributes)->save();

            return $user->refresh();
        });
    }

    /**
     * The marker is deliberately independent of the mutable phone credential.
     */
    private function isE2EOwned(User $user): bool
    {
        return $user->email === self::E2E_EMAIL
            && $user->full_name === 'E2E Admin Fixture'
            && $user->role === RoleEnum::ADMIN;
    }

    public function run(): void
    {
        DB::transaction(function (): void {
            $users = $this->seedUsers();
            $instruments = $this->seedInstruments();
            $this->seedRooms();
            $teachers = $this->seedTeachers();
            $students = $this->seedStudents();

            $this->seedTeacherInstruments($teachers, $instruments);
            $enrollments = $this->seedEnrollments($students, $teachers, $instruments);
            $sessions = $this->seedSessions($enrollments);
            $this->seedAttendances($sessions, $students, $users);
            $this->seedInvoices($students, $enrollments, $users);
            $this->seedLeads($instruments, $teachers, $users);
        });
    }

    /**
     * @return array<string, User>
     */
    private function seedUsers(): array
    {
        $definitions = [
            'super-admin' => ['09000000001', 'Seed Super Admin', RoleEnum::SUPER_ADMIN],
            'admin' => ['09000000002', 'Seed Admin', RoleEnum::ADMIN],
            'teacher' => ['09000000003', 'Seed Teacher User', RoleEnum::TEACHER],
            'student' => ['09000000004', 'Seed Student User', RoleEnum::STUDENT],
        ];

        $users = [];

        foreach ($definitions as $key => [$phone, $fullName, $role]) {
            $users[$key] = $this->persist(User::query()->firstWhere('phone', $phone) ?? new User(), [
                'full_name' => $fullName,
                'phone' => $phone,
                'email' => $key . '@' . self::KEY_PREFIX . '.test',
                'role' => $role->value,
                'is_active' => true,
            ], fn (User $user): array => [
                'password' => $user->password ?: Hash::make(self::PASSWORD),
            ]);
        }

        return $users;
    }

    /**
     * @return array<string, Instrument>
     */
    private function seedInstruments(): array
    {
        $definitions = [
            'piano' => ['Seed Piano', 'پیانوی تستی'],
            'violin' => ['Seed Violin', 'ویولن تستی'],
        ];

        $instruments = [];

        foreach ($definitions as $key => [$name, $nameFa]) {
            $slug = $this->key('instrument', $key);

            $instruments[$key] = $this->persist(
                Instrument::query()->firstWhere('slug', $slug) ?? new Instrument(),
                [
                    'name' => $name,
                    'name_fa' => $nameFa,
                    'slug' => $slug,
                    'is_active' => true,
                ]
            );
        }

        return $instruments;
    }

    /**
     * @return array<string, Room>
     */
    private function seedRooms(): array
    {
        $rooms = [];

        foreach (['a' => 12, 'b' => 4] as $key => $capacity) {
            $name = $this->key('room', $key);

            $rooms[$key] = $this->persist(
                Room::query()->firstWhere('name', $name) ?? new Room(),
                [
                    'name' => $name,
                    'capacity' => $capacity,
                    'is_active' => true,
                ]
            );
        }

        return $rooms;
    }

    /**
     * @return array<string, Teacher>
     */
    private function seedTeachers(): array
    {
        $definitions = [
            'active' => ['Seed Teacher Active', '09110000001', TeacherStatusEnum::Active],
            'inactive' => ['Seed Teacher Inactive', '09110000002', TeacherStatusEnum::Inactive],
        ];

        $teachers = [];

        foreach ($definitions as $key => [$fullName, $phone, $status]) {
            $code = $this->key('teacher', $key);

            $teachers[$key] = $this->persist(
                Teacher::query()->firstWhere('teacher_code', $code) ?? new Teacher(),
                [
                    'teacher_code' => $code,
                    'full_name' => $fullName,
                    'phone' => $phone,
                    'status' => $status->value,
                    'bio' => 'Seeded teacher profile.',
                    'hire_date' => self::ANCHOR_DATE,
                ]
            );
        }

        return $teachers;
    }

    /**
     * @return array<string, Student>
     */
    private function seedStudents(): array
    {
        $definitions = [
            'active' => ['Seed Student Active', '09120000001', StudentStatusEnum::Active],
            'paused' => ['Seed Student Paused', '09120000002', StudentStatusEnum::Paused],
            'inactive' => ['Seed Student Inactive', '09120000003', StudentStatusEnum::Inactive],
        ];

        $students = [];

        foreach ($definitions as $key => [$fullName, $phone, $status]) {
            $code = $this->key('student', $key);

            $students[$key] = $this->persist(
                Student::query()->firstWhere('student_code', $code) ?? new Student(),
                [
                    'student_code' => $code,
                    'full_name' => $fullName,
                    'phone' => $phone,
                    'parent_phone' => null,
                    'status' => $status->value,
                    'join_date' => self::ANCHOR_DATE,
                    'notes' => null,
                ]
            );
        }

        return $students;
    }

    /**
     * @param  array<string, Teacher>  $teachers
     * @param  array<string, Instrument>  $instruments
     */
    private function seedTeacherInstruments(array $teachers, array $instruments): void
    {
        $pairs = [
            ['active', 'piano', SkillLevelEnum::Advanced, true],
            ['active', 'violin', SkillLevelEnum::Intermediate, false],
            ['inactive', 'violin', SkillLevelEnum::Beginner, true],
        ];

        foreach ($pairs as [$teacherKey, $instrumentKey, $skillLevel, $isPrimary]) {
            $teacherId = $teachers[$teacherKey]->id;
            $instrumentId = $instruments[$instrumentKey]->id;

            $existing = TeacherInstrument::query()
                ->where('teacher_id', $teacherId)
                ->where('instrument_id', $instrumentId)
                ->first();

            $this->persist($existing ?? new TeacherInstrument(), [
                'teacher_id' => $teacherId,
                'instrument_id' => $instrumentId,
                'skill_level' => $skillLevel->value,
                'is_primary' => $isPrimary,
            ]);
        }
    }

    /**
     * @param  array<string, Student>  $students
     * @param  array<string, Teacher>  $teachers
     * @param  array<string, Instrument>  $instruments
     * @return array<string, StudentEnrollment>
     */
    private function seedEnrollments(array $students, array $teachers, array $instruments): array
    {
        $definitions = [
            'active' => ['active', 'active', 'piano', EnrollmentStatusEnum::Active, null],
            'completed' => ['paused', 'active', 'violin', EnrollmentStatusEnum::Completed, '2026-02-05'],
        ];

        $enrollments = [];

        foreach ($definitions as $key => [$studentKey, $teacherKey, $instrumentKey, $status, $endedAt]) {
            $existing = StudentEnrollment::withTrashed()
                ->where('student_id', $students[$studentKey]->id)
                ->where('teacher_id', $teachers[$teacherKey]->id)
                ->where('instrument_id', $instruments[$instrumentKey]->id)
                ->first();

            $enrollments[$key] = $this->persist($existing ?? new StudentEnrollment(), [
                'student_id' => $students[$studentKey]->id,
                'teacher_id' => $teachers[$teacherKey]->id,
                'instrument_id' => $instruments[$instrumentKey]->id,
                'skill_level' => SkillLevelEnum::Beginner->value,
                'status' => $status->value,
                'started_at' => self::ANCHOR_DATE,
                'ended_at' => $endedAt,
                'deleted_at' => null,
            ]);
        }

        return $enrollments;
    }

    /**
     * One session per enrollment per date, so `(enrollment_id, session_date)`
     * is a stable natural key.
     *
     * @param  array<string, StudentEnrollment>  $enrollments
     * @return array<string, ClassSession>
     */
    private function seedSessions(array $enrollments): array
    {
        $definitions = [
            'scheduled' => ['active', '2026-01-12', '15:00:00', SessionStatusEnum::Scheduled],
            'completed' => ['active', '2026-01-19', '15:00:00', SessionStatusEnum::Completed],
            'cancelled' => ['completed', '2026-01-20', '17:30:00', SessionStatusEnum::Cancelled],
        ];

        $sessions = [];

        foreach ($definitions as $key => [$enrollmentKey, $date, $startTime, $status]) {
            $enrollment = $enrollments[$enrollmentKey];

            $existing = ClassSession::query()
                ->where('enrollment_id', $enrollment->id)
                ->whereDate('session_date', $date)
                ->first();

            $sessions[$key] = $this->persist($existing ?? new ClassSession(), [
                'enrollment_id' => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'teacher_id' => $enrollment->teacher_id,
                'instrument_id' => $enrollment->instrument_id,
                'recurring_schedule_id' => null,
                'session_date' => $date,
                'start_time' => $startTime,
                'duration_minutes' => 60,
                'status' => $status->value,
                'room' => 'A101',
                'notes' => null,
            ]);
        }

        return $sessions;
    }

    /**
     * @param  array<string, ClassSession>  $sessions
     * @param  array<string, Student>  $students
     * @param  array<string, User>  $users
     */
    private function seedAttendances(array $sessions, array $students, array $users): void
    {
        $definitions = [
            ['completed', 'active', AttendanceStatusEnum::Present],
            ['cancelled', 'paused', AttendanceStatusEnum::Excused],
        ];

        foreach ($definitions as [$sessionKey, $studentKey, $status]) {
            $sessionId = $sessions[$sessionKey]->id;
            $studentId = $students[$studentKey]->id;

            $existing = ClassAttendance::query()
                ->where('class_session_id', $sessionId)
                ->where('student_id', $studentId)
                ->first();

            $this->persist($existing ?? new ClassAttendance(), [
                'class_session_id' => $sessionId,
                'student_id' => $studentId,
                'status' => $status->value,
                'note' => null,
                'marked_by' => $users['admin']->id,
                'marked_at' => self::ANCHOR_DATE . ' 12:00:00',
            ]);
        }
    }

    /**
     * @param  array<string, Student>  $students
     * @param  array<string, StudentEnrollment>  $enrollments
     * @param  array<string, User>  $users
     */
    private function seedInvoices(array $students, array $enrollments, array $users): void
    {
        $definitions = [
            'issued' => ['active', 'active', InvoiceStatusEnum::Issued, 2, 1500000, null],
            'paid' => ['paused', 'completed', InvoiceStatusEnum::Paid, 1, 900000, 900000],
        ];

        foreach ($definitions as $key => [$studentKey, $enrollmentKey, $status, $quantity, $unitPrice, $paidAmount]) {
            $number = $this->key('invoice', $key);

            $invoice = $this->persist(
                Invoice::withTrashed()->firstWhere('invoice_number', $number) ?? new Invoice(),
                [
                    'uuid' => $this->uuid($number),
                    'invoice_number' => $number,
                    'student_id' => $students[$studentKey]->id,
                    'enrollment_id' => $enrollments[$enrollmentKey]->id,
                    'issue_date' => self::ANCHOR_DATE,
                    'due_date' => '2026-02-05',
                    'subtotal' => $quantity * $unitPrice,
                    'discount' => 0,
                    'tax' => 0,
                    'currency' => 'IRR',
                    'status' => $status->value,
                    'notes' => null,
                    'deleted_at' => null,
                ]
            );

            $existingItem = InvoiceItem::query()
                ->where('invoice_id', $invoice->id)
                ->where('sort_order', 1)
                ->first();

            $this->persist($existingItem ?? new InvoiceItem(), [
                'invoice_id' => $invoice->id,
                'title' => 'Seed tuition line',
                'description' => null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount' => 0,
                'sort_order' => 1,
            ]);

            if ($paidAmount === null) {
                continue;
            }

            $reference = $this->key('payment', $key);

            $existingPayment = InvoicePayment::query()
                ->where('invoice_id', $invoice->id)
                ->where('reference', $reference)
                ->first();

            $this->persist($existingPayment ?? new InvoicePayment(), [
                'invoice_id' => $invoice->id,
                'amount' => $paidAmount,
                'paid_at' => self::ANCHOR_DATE . ' 10:00:00',
                'method' => PaymentMethodEnum::Cash->value,
                'status' => PaymentStatusEnum::Completed->value,
                'reference' => $reference,
                'notes' => null,
                'created_by' => $users['admin']->id,
            ]);
        }
    }

    /**
     * @param  array<string, Instrument>  $instruments
     * @param  array<string, Teacher>  $teachers
     * @param  array<string, User>  $users
     */
    private function seedLeads(array $instruments, array $teachers, array $users): void
    {
        $definitions = [
            'new' => ['09130000001', LeadStatusEnum::New, LeadPriorityEnum::High],
            'contacted' => ['09130000002', LeadStatusEnum::Contacted, LeadPriorityEnum::Medium],
        ];

        foreach ($definitions as $key => [$phone, $status, $priority]) {
            $existing = Lead::withTrashed()->firstWhere('phone', $phone);

            $this->persist($existing ?? new Lead(), [
                'full_name' => 'Seed Lead ' . $key,
                'phone' => $phone,
                'email' => 'lead-' . $key . '@' . self::KEY_PREFIX . '.test',
                'age' => 20,
                'preferred_instrument_id' => $instruments['piano']->id,
                'preferred_teacher_id' => $teachers['active']->id,
                'source' => LeadSourceEnum::Website->value,
                'status' => $status->value,
                'priority' => $priority->value,
                'assigned_to' => $users['admin']->id,
                'notes' => null,
                'next_follow_up_at' => self::ANCHOR_DATE . ' 09:00:00',
                'converted_at' => null,
                'converted_student_id' => null,
                'deleted_at' => null,
            ]);
        }
    }

    /**
     * Write the given attributes onto the model, bypassing `$fillable` so that
     * generated natural keys (`teacher_code`, `student_code`) stay stable.
     *
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @param  array<string, mixed>  $attributes
     * @param  null|callable(TModel): array<string, mixed>  $extra
     * @return TModel
     */
    private function persist(Model $model, array $attributes, ?callable $extra = null): Model
    {
        $model->forceFill($attributes);

        if ($extra !== null) {
            $model->forceFill($extra($model));
        }

        $model->save();

        return $model;
    }

    /** Build a stable natural key such as `TSEED-teacher-active`. */
    private function key(string $type, string $name): string
    {
        return self::KEY_PREFIX . '-' . $type . '-' . $name;
    }

    /** Deterministic UUID derived from a natural key. */
    private function uuid(string $seed): string
    {
        $hash = md5(self::KEY_PREFIX . ':' . $seed);

        return implode('-', [
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12),
        ]);
    }
}
