<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatusEnum;
use App\Enums\EnrollmentStatusEnum;
use App\Enums\SessionStatusEnum;
use App\Enums\SkillLevelEnum;
use App\Enums\StudentStatusEnum;
use App\Enums\TeacherStatusEnum;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\RecurringSchedule;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subscription;
use App\Models\Teacher;
use Carbon\Carbon;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    private const FAKER_SEED = 20260709;

    private Carbon $today;

    private Generator $faker;

    /** @var array<int, array{complete: int, miss: int, cancel: int}> teacher_id => past-session weights */
    private array $teacherProfiles = [];

    /** @var array<int, array{present: int, late: int, excused: int, absent: int}> student_id => attendance weights */
    private array $studentProfiles = [];

    private array $rooms = [
        'A101', 'A102', 'A103',
        'B201', 'B202', 'B203',
        'C301', 'C302', 'C303',
    ];

    private array $startTimes = [
        '09:00', '10:00', '11:00', '12:00',
        '14:00', '15:00', '16:00', '17:00', '18:00',
    ];

    private array $durations = [30, 45, 60, 90];

    private array $sessionFees = [
        300000, 400000, 500000, 600000, 750000,
        900000, 1000000, 1200000, 1500000,
    ];

    private array $monthlyFees = [
        2000000, 2500000, 3000000, 3500000, 4000000, 5000000,
    ];

    public function run(): void
    {
        $this->faker = fake();
        $this->faker->seed(self::FAKER_SEED);
        mt_srand(self::FAKER_SEED);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $this->today = Carbon::today();

        echo "\n📊 Seeding Demo Database...\n";
        echo str_repeat('━', 40) . "\n";

        $instruments = $this->seedInstruments();
        echo "  ✓ Instruments:         {$instruments->count()}\n";

        $teachers = $this->seedTeachers($instruments);
        echo "  ✓ Teachers:            {$teachers->count()}\n";

        $students = $this->seedStudents();
        echo "  ✓ Students:            {$students->count()}\n";

        $enrollments = $this->seedEnrollments($students, $teachers);
        echo "  ✓ Enrollments:         {$enrollments->count()}\n";

        $schedules = $this->seedRecurringSchedules($enrollments);
        echo "  ✓ Recurring Schedules: {$schedules->count()}\n";

        $sessionCount = $this->seedClassSessions($schedules, $enrollments);
        echo "  ✓ Class Sessions:      {$sessionCount}\n";

        $attendanceCount = $this->seedAttendance();
        echo "  ✓ Attendance Records:  {$attendanceCount}\n";

        $subCount = $this->seedSubscriptions($enrollments);
        echo "  ✓ Subscriptions:       {$subCount}\n";

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->printSummary();
    }

    // ─────────────────────────────────────────────────────
    // INSTRUMENTS  (15)
    // ─────────────────────────────────────────────────────

    private function seedInstruments(): Collection
    {
        $data = [
            ['name' => 'Piano',     'name_fa' => 'پیانو',  'slug' => 'piano'],
            ['name' => 'Guitar',    'name_fa' => 'گیتار',  'slug' => 'guitar'],
            ['name' => 'Violin',    'name_fa' => 'ویولن',  'slug' => 'violin'],
            ['name' => 'Santoor',   'name_fa' => 'سنتور',  'slug' => 'santoor'],
            ['name' => 'Tar',       'name_fa' => 'تار',    'slug' => 'tar'],
            ['name' => 'Setar',     'name_fa' => 'سه‌تار', 'slug' => 'setar'],
            ['name' => 'Daf',       'name_fa' => 'دف',     'slug' => 'daf'],
            ['name' => 'Drum',      'name_fa' => 'درام',   'slug' => 'drum'],
            ['name' => 'Ney',       'name_fa' => 'نی',     'slug' => 'ney'],
            ['name' => 'Kamancheh', 'name_fa' => 'کمانچه', 'slug' => 'kamancheh'],
            ['name' => 'Tombak',    'name_fa' => 'تنبک',   'slug' => 'tombak'],
            ['name' => 'Oud',       'name_fa' => 'عود',    'slug' => 'oud'],
            ['name' => 'Flute',     'name_fa' => 'فلوت',   'slug' => 'flute'],
            ['name' => 'Cello',     'name_fa' => 'چلو',    'slug' => 'cello'],
            ['name' => 'Vocal',     'name_fa' => 'آواز',   'slug' => 'vocal'],
        ];

        return collect($data)->map(fn (array $row) => Instrument::create([
            ...$row,
            'is_active' => true,
        ]));
    }

    // ─────────────────────────────────────────────────────
    // TEACHERS  (12) — with performance tiers
    // ─────────────────────────────────────────────────────

    private function seedTeachers(Collection $instruments): Collection
    {
        $bySlug = $instruments->keyBy('slug');

        // complete / miss / cancel weights for past sessions (sum ≈ 100)
        $performanceTiers = [
            'star'     => ['complete' => 92, 'miss' => 2,  'cancel' => 6],
            'strong'   => ['complete' => 85, 'miss' => 5,  'cancel' => 10],
            'average'  => ['complete' => 75, 'miss' => 10, 'cancel' => 15],
            'struggling' => ['complete' => 60, 'miss' => 18, 'cancel' => 22],
        ];

        $teacherData = [
            [
                'full_name'   => 'محمدرضا کریمی',
                'phone'       => '09121234567',
                'bio'         => 'استاد پیانو با بیش از ۱۵ سال تجربه تدریس در مکاتب معتبر موسیقی',
                'instruments' => ['piano'],
                'tier'        => 'star',
                'hire_months' => 60,
            ],
            [
                'full_name'   => 'فاطمه علیزاده',
                'phone'       => '09129876543',
                'bio'         => 'متخصص گیتار کلاسیک و الکتریکی، فارغ‌التحصیل دانشگاه هنر تهران',
                'instruments' => ['guitar', 'oud'],
                'tier'        => 'strong',
                'hire_months' => 48,
            ],
            [
                'full_name'   => 'علی محمودی',
                'phone'       => '09135551111',
                'bio'         => 'ویولنیست حرفه‌ای با سابقه اجرا در ارکسترهای ملی',
                'instruments' => ['violin', 'cello'],
                'tier'        => 'star',
                'hire_months' => 72,
            ],
            [
                'full_name'   => 'نسرین رستمی',
                'phone'       => '09147772222',
                'bio'         => 'معلم موسیقی سنتی ایرانی، نوازنده برجسته سنتور و تار',
                'instruments' => ['santoor', 'tar'],
                'tier'        => 'strong',
                'hire_months' => 36,
            ],
            [
                'full_name'   => 'حمید اسلامی',
                'phone'       => '09159993333',
                'bio'         => 'نوازنده دف و تنبک با سابقه همکاری با گروه‌های موسیقی نواحی',
                'instruments' => ['daf', 'tombak'],
                'tier'        => 'average',
                'hire_months' => 24,
            ],
            [
                'full_name'   => 'مریم سعیدی',
                'phone'       => '09168884444',
                'bio'         => 'خواننده و مدرس آواز کلاسیک ایرانی و پیانو',
                'instruments' => ['vocal', 'piano'],
                'tier'        => 'strong',
                'hire_months' => 42,
            ],
            [
                'full_name'   => 'آرمان طاهری',
                'phone'       => '09171112345',
                'bio'         => 'نوازنده سه‌تار و تار با مدرک دکترا از دانشگاه هنر',
                'instruments' => ['setar', 'tar'],
                'tier'        => 'star',
                'hire_months' => 54,
            ],
            [
                'full_name'   => 'پریسا قاسمی',
                'phone'       => '09182223456',
                'bio'         => 'نوازنده نی و فلوت، عضو ارکستر سمفونیک تهران',
                'instruments' => ['ney', 'flute'],
                'tier'        => 'average',
                'hire_months' => 18,
            ],
            [
                'full_name'   => 'سیاوش جعفری',
                'phone'       => '09193334567',
                'bio'         => 'درامر حرفه‌ای با تجربه اجرا در کنسرت‌های بین‌المللی',
                'instruments' => ['drum', 'tombak'],
                'tier'        => 'struggling',
                'hire_months' => 10,
            ],
            [
                'full_name'   => 'گلناز حیدری',
                'phone'       => '09104445678',
                'bio'         => 'نوازنده کمانچه و ویولن، استاد موسیقی دستگاهی ایران',
                'instruments' => ['kamancheh', 'violin'],
                'tier'        => 'strong',
                'hire_months' => 30,
            ],
            [
                'full_name'   => 'داریوش مهدوی',
                'phone'       => '09115556789',
                'bio'         => 'نوازنده عود و گیتار با سابقه تدریس در کشورهای عربی و اروپا',
                'instruments' => ['oud', 'guitar'],
                'tier'        => 'average',
                'hire_months' => 20,
            ],
            [
                'full_name'   => 'شادی سلیمانی',
                'phone'       => '09126667890',
                'bio'         => 'مدرس چلو و ویولن، عضو هیئت علمی دانشکده موسیقی',
                'instruments' => ['cello', 'violin'],
                'tier'        => 'struggling',
                'hire_months' => 8,
            ],
        ];

        return collect($teacherData)->map(function (array $data) use ($bySlug, $performanceTiers): Teacher {
            $instrumentSlugs = $data['instruments'];
            $tier = $data['tier'];
            $hireMonths = $data['hire_months'];
            unset($data['instruments'], $data['tier'], $data['hire_months']);

            $teacher = Teacher::create([
                ...$data,
                'status'    => TeacherStatusEnum::Active->value,
                'hire_date' => $this->today->copy()->subMonths($hireMonths)->toDateString(),
            ]);

            $this->teacherProfiles[$teacher->id] = $performanceTiers[$tier];

            foreach ($instrumentSlugs as $index => $slug) {
                $instrument = $bySlug->get($slug);
                if (! $instrument) {
                    continue;
                }

                $teacher->instruments()->attach($instrument->id, [
                    'skill_level' => $index === 0
                        ? SkillLevelEnum::Expert->value
                        : $this->faker->randomElement([
                            SkillLevelEnum::Advanced->value,
                            SkillLevelEnum::Expert->value,
                        ]),
                    'is_primary' => $index === 0,
                ]);
            }

            return $teacher;
        });
    }

    // ─────────────────────────────────────────────────────
    // STUDENTS  (150) — with attendance personas
    // ─────────────────────────────────────────────────────

    private function seedStudents(): Collection
    {
        $firstNames = [
            'علی', 'محمد', 'حسین', 'مهدی', 'رضا', 'امیر', 'سامان', 'آرین',
            'بهزاد', 'کمال', 'جواد', 'محسن', 'صالح', 'مسعود', 'هیربد',
            'بهرام', 'آرمان', 'سیاوش', 'کاوه', 'داریوش', 'شاهین', 'فرهاد',
            'پویا', 'نیما', 'سعید', 'وحید', 'بابک', 'کیارش', 'پارسا', 'ایمان',
            'زهرا', 'فاطمه', 'مریم', 'الهام', 'شیرین', 'آزاده', 'نیلوفر',
            'پریا', 'یاسمن', 'نرگس', 'دنیا', 'ساره', 'سپیده', 'لیلا', 'زیبا',
            'مهناز', 'نسرین', 'پریسا', 'شقایق', 'گلناز', 'نازنین', 'بهناز',
            'شادی', 'آتنا', 'مهسا', 'رویا', 'صبا', 'سوسن', 'هانیه', 'درسا',
        ];

        $lastNames = [
            'احمدی', 'رحمانی', 'حسنی', 'کریمی', 'توکلی', 'موسوی', 'شریفی',
            'رضایی', 'جمشیدی', 'صادقی', 'میرزایی', 'اکبری', 'علیخانی', 'رزاقی',
            'شاکری', 'باقری', 'حاجی‌زاده', 'سلطانی', 'کاظمی', 'شهبازی', 'مرادی',
            'فرجام', 'درویشی', 'هاشمی', 'بهرامی', 'رفاهی', 'اسدی', 'منصوری',
            'زارعی', 'طاهری', 'محمودی', 'رستمی', 'اسلامی', 'سعیدی', 'نظری',
            'قاسمی', 'جعفری', 'حیدری', 'محمدی', 'رجبی', 'مهدوی', 'سلیمانی',
            'عباسی', 'خانی', 'حبیبی', 'تهرانی', 'اصفهانی', 'مشهدی', 'تبریزی',
            'کرمانی', 'یزدی', 'شیرازی', 'گیلانی', 'مازندرانی', 'خراسانی',
        ];

        // present / late / excused / absent weights (sum = 100)
        $attendancePersonas = [
            'excellent' => ['present' => 95, 'late' => 3,  'excused' => 1, 'absent' => 1],
            'reliable'  => ['present' => 88, 'late' => 5,  'excused' => 3, 'absent' => 4],
            'typical'   => ['present' => 82, 'late' => 6,  'excused' => 4, 'absent' => 8],
            'irregular' => ['present' => 65, 'late' => 10, 'excused' => 8, 'absent' => 17],
            'chronic'   => ['present' => 45, 'late' => 12, 'excused' => 10, 'absent' => 33],
        ];

        // Distribution across 150 students: 20% excellent, 35% reliable, 25% typical, 12% irregular, 8% chronic
        $personaPlan = array_merge(
            array_fill(0, 30, 'excellent'),
            array_fill(0, 52, 'reliable'),
            array_fill(0, 38, 'typical'),
            array_fill(0, 18, 'irregular'),
            array_fill(0, 12, 'chronic'),
        );

        $students = collect();
        $used = [];

        for ($i = 0; $i < 150; $i++) {
            do {
                $name = $firstNames[$i % count($firstNames)] . ' ' .
                    $lastNames[($i * 7 + 3) % count($lastNames)];
                if (isset($used[$name])) {
                    $name .= ' ' . ($i + 1);
                }
            } while (isset($used[$name]));

            $used[$name] = true;

            // Status: 80% active, 10% paused, 7% inactive, 3% graduated
            $status = match (true) {
                $i < 120 => StudentStatusEnum::Active->value,
                $i < 135 => StudentStatusEnum::Paused->value,
                $i < 145 => StudentStatusEnum::Inactive->value,
                default  => StudentStatusEnum::Graduated->value,
            };

            // Recent joiners for dashboard: last 15 students joined within 14 days
            $joinDate = $i >= 135
                ? $this->today->copy()->subDays($this->faker->numberBetween(1, 14))
                : $this->today->copy()->subMonths($this->faker->numberBetween(1, 30));

            $student = Student::create([
                'full_name'    => $name,
                'phone'        => '0912' . str_pad((string) (1000000 + $i), 7, '0', STR_PAD_LEFT),
                'parent_phone' => $i % 2 === 0
                    ? '0911' . str_pad((string) (2000000 + $i), 7, '0', STR_PAD_LEFT)
                    : null,
                'status'       => $status,
                'join_date'    => $joinDate->toDateString(),
                'notes'        => $i % 5 === 0 ? $this->faker->sentence(6) : null,
            ]);

            $persona = $personaPlan[$i];
            $this->studentProfiles[$student->id] = $attendancePersonas[$persona];

            $students->push($student);
        }

        return $students;
    }

    // ─────────────────────────────────────────────────────
    // ENROLLMENTS  (180 active + 30 non-active)
    // ─────────────────────────────────────────────────────

    private function seedEnrollments(Collection $students, Collection $teachers): Collection
    {
        $enrollments = collect();
        $teachers->load('instruments');

        $teacherInstruments = $teachers->mapWithKeys(fn (Teacher $t) => [
            $t->id => $t->instruments,
        ])->filter(fn (Collection $instruments) => $instruments->isNotEmpty());

        if ($teacherInstruments->isEmpty()) {
            return $enrollments;
        }

        $activeStudents = $students
            ->filter(fn (Student $s) => $s->status === StudentStatusEnum::Active)
            ->values();

        $allStudents = $students->values();
        $teacherList = $teachers->values();

        // Uneven teacher load: star/strong teachers get more enrollments
        $teacherWeights = [];
        foreach ($teacherList as $index => $teacher) {
            $profile = $this->teacherProfiles[$teacher->id] ?? ['complete' => 75];
            $teacherWeights[$teacher->id] = match (true) {
                $profile['complete'] >= 90 => 22,
                $profile['complete'] >= 80 => 18,
                $profile['complete'] >= 70 => 14,
                default                    => 8,
            };
        }

        $seen = [];
        $activeTarget = 180;
        $nonActiveTarget = 30;

        // Recent enrollments (last 14 days) — first 25 active for dashboard widgets
        for ($i = 0; $i < $activeTarget + $nonActiveTarget; $i++) {
            $isActive = $i < $activeTarget;
            $isRecent = $isActive && $i < 25;

            $created = false;
            for ($attempt = 0; $attempt < 40 && ! $created; $attempt++) {
                $student = $isActive
                    ? $activeStudents[$i % max($activeStudents->count(), 1)]
                    : $allStudents[$this->faker->numberBetween(0, $allStudents->count() - 1)];

                $teacher = $this->pickWeightedTeacher($teacherList, $teacherWeights);
                $instruments = $teacherInstruments->get($teacher->id);

                if (! $instruments || $instruments->isEmpty()) {
                    continue;
                }

                $instrument = $instruments[$i % $instruments->count()];
                $key = "{$student->id}-{$teacher->id}-{$instrument->id}";

                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                if ($isActive) {
                    $status = EnrollmentStatusEnum::Active->value;
                    $endedAt = null;
                    $startedAt = $isRecent
                        ? $this->today->copy()->subDays($this->faker->numberBetween(0, 14))
                        : $this->today->copy()->subMonths($this->faker->numberBetween(1, 18));
                } else {
                    $status = $i % 2 === 0
                        ? EnrollmentStatusEnum::Completed->value
                        : EnrollmentStatusEnum::Paused->value;
                    $startedAt = $this->today->copy()->subMonths($this->faker->numberBetween(6, 24));
                    $endedAt = $status === EnrollmentStatusEnum::Completed->value
                        ? $this->today->copy()->subMonths($this->faker->numberBetween(1, 4))->toDateString()
                        : null;
                }

                // Skill distribution: 40% beginner, 30% intermediate, 20% advanced, 10% expert
                $skill = match ($i % 10) {
                    0, 1, 2, 3 => SkillLevelEnum::Beginner->value,
                    4, 5, 6    => SkillLevelEnum::Intermediate->value,
                    7, 8       => SkillLevelEnum::Advanced->value,
                    default    => SkillLevelEnum::Expert->value,
                };

                $enrollments->push(StudentEnrollment::create([
                    'student_id'    => $student->id,
                    'teacher_id'    => $teacher->id,
                    'instrument_id' => $instrument->id,
                    'skill_level'   => $skill,
                    'status'        => $status,
                    'started_at'    => $startedAt->toDateString(),
                    'ended_at'      => $endedAt,
                    'notes'         => $i % 7 === 0 ? $this->faker->sentence(5) : null,
                ]));

                $created = true;
            }
        }

        return $enrollments;
    }

    // ─────────────────────────────────────────────────────
    // RECURRING SCHEDULES
    // Guarantees coverage for today, tomorrow, and this week
    // ─────────────────────────────────────────────────────

    private function seedRecurringSchedules(Collection $enrollments): Collection
    {
        $schedules = collect();
        $active = $enrollments
            ->filter(fn (StudentEnrollment $e) => $e->status === EnrollmentStatusEnum::Active)
            ->values();

        $todayWeekday = (int) $this->today->dayOfWeek;
        $tomorrowWeekday = (int) $this->today->copy()->addDay()->dayOfWeek;

        // Weekdays that fall within the current calendar week (Sun–Sat)
        $thisWeekWeekdays = [];
        $weekCursor = $this->today->copy()->startOfWeek(Carbon::SUNDAY);
        for ($d = 0; $d < 7; $d++) {
            if ($weekCursor->gte($this->today) && $weekCursor->lte($this->today->copy()->endOfWeek(Carbon::SATURDAY))) {
                $thisWeekWeekdays[] = (int) $weekCursor->dayOfWeek;
            }
            $weekCursor->addDay();
        }
        $thisWeekWeekdays = array_values(array_unique($thisWeekWeekdays));

        $teacherSlots = [];
        $weekdayQuota = [
            $todayWeekday     => 0,
            $tomorrowWeekday  => 0,
        ];
        foreach ($thisWeekWeekdays as $wd) {
            $weekdayQuota[$wd] = $weekdayQuota[$wd] ?? 0;
        }

        // Minimum quotas so dashboards always have near-term data
        $minToday = 12;
        $minTomorrow = 10;
        $minThisWeek = 40;

        foreach ($active as $index => $enrollment) {
            $teacherId = $enrollment->teacher_id;

            // Force early schedules onto today / tomorrow / this-week weekdays
            if ($weekdayQuota[$todayWeekday] < $minToday) {
                $weekday = $todayWeekday;
            } elseif ($weekdayQuota[$tomorrowWeekday] < $minTomorrow) {
                $weekday = $tomorrowWeekday;
            } elseif (array_sum(array_intersect_key($weekdayQuota, array_flip($thisWeekWeekdays))) < $minThisWeek
                && count($thisWeekWeekdays) > 0
            ) {
                $weekday = $thisWeekWeekdays[$index % count($thisWeekWeekdays)];
            } else {
                $usedWeekdays = array_keys($teacherSlots[$teacherId] ?? []);
                $freeWeekdays = array_values(array_diff(range(0, 6), $usedWeekdays));
                $weekday = count($freeWeekdays) > 0
                    ? $freeWeekdays[$index % count($freeWeekdays)]
                    : $index % 7;
            }

            $usedTimes = $teacherSlots[$teacherId][$weekday] ?? [];
            $freeTimes = array_values(array_diff($this->startTimes, $usedTimes));
            $startTime = count($freeTimes) > 0
                ? $freeTimes[$index % count($freeTimes)]
                : $this->startTimes[$index % count($this->startTimes)];

            $teacherSlots[$teacherId][$weekday][] = $startTime;
            $weekdayQuota[$weekday] = ($weekdayQuota[$weekday] ?? 0) + 1;

            $schedules->push(RecurringSchedule::create([
                'enrollment_id'    => $enrollment->id,
                'weekday'          => $weekday,
                'start_time'       => $startTime,
                'duration_minutes' => $this->durations[$index % count($this->durations)],
                'room'             => $this->rooms[$index % count($this->rooms)],
                'is_active'        => true,
            ]));
        }

        return $schedules;
    }

    // ─────────────────────────────────────────────────────
    // CLASS SESSIONS  (90 days back + 90 days forward)
    // ─────────────────────────────────────────────────────

    private function seedClassSessions(Collection $schedules, Collection $enrollments): int
    {
        $rangeStart = $this->today->copy()->subDays(90);
        $rangeEnd = $this->today->copy()->addDays(90);
        $enrollmentById = $enrollments->keyBy('id');

        $rows = [];
        $now = now()->toDateTimeString();

        foreach ($schedules as $schedule) {
            $enrollment = $enrollmentById->get($schedule->enrollment_id);

            if (! $enrollment) {
                continue;
            }

            $cursor = $rangeStart->copy();
            while ((int) $cursor->dayOfWeek !== (int) $schedule->weekday) {
                $cursor->addDay();
            }

            while ($cursor->lte($rangeEnd)) {
                if ($enrollment->started_at && $cursor->lt(Carbon::parse($enrollment->started_at))) {
                    $cursor->addWeek();
                    continue;
                }
                if ($enrollment->ended_at && $cursor->gt(Carbon::parse($enrollment->ended_at))) {
                    $cursor->addWeek();
                    continue;
                }

                $isPast = $cursor->lt($this->today);
                $isToday = $cursor->isSameDay($this->today);
                $isTomorrow = $cursor->isSameDay($this->today->copy()->addDay());

                $status = $this->resolveSessionStatus(
                    $enrollment->teacher_id,
                    $isPast,
                    $isToday,
                    $isTomorrow,
                );

                $feeIndex = ($enrollment->id + $cursor->dayOfYear) % count($this->sessionFees);

                $rows[] = [
                    'enrollment_id'         => $enrollment->id,
                    'student_id'            => $enrollment->student_id,
                    'teacher_id'            => $enrollment->teacher_id,
                    'instrument_id'         => $enrollment->instrument_id,
                    'recurring_schedule_id' => $schedule->id,
                    'session_date'          => $cursor->toDateString(),
                    'start_time'            => $schedule->start_time,
                    'duration_minutes'      => $schedule->duration_minutes,
                    'status'                => $status,
                    'room'                  => $schedule->room,
                    'session_fee'           => $this->sessionFees[$feeIndex],
                    'discount'              => ($enrollment->id % 10 === 0) ? 50000 : 0,
                    'notes'                 => ($enrollment->id % 12 === 0) ? $this->faker->sentence(5) : null,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ];

                $cursor->addWeek();
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('class_sessions')->insert($chunk);
        }

        return count($rows);
    }

    // ─────────────────────────────────────────────────────
    // ATTENDANCE — persona-driven per student
    // ─────────────────────────────────────────────────────

    private function seedAttendance(): int
    {
        $rows = [];
        $now = now()->toDateTimeString();

        $sessions = DB::table('class_sessions')
            ->whereIn('status', [
                SessionStatusEnum::Completed->value,
                SessionStatusEnum::Missed->value,
            ])
            ->select('id', 'student_id', 'status', 'session_date')
            ->orderBy('id')
            ->get();

        foreach ($sessions as $session) {
            if ($session->status === SessionStatusEnum::Missed->value) {
                $status = AttendanceStatusEnum::Absent->value;
            } else {
                $status = $this->resolveAttendanceStatus((int) $session->student_id);
            }

            $rows[] = [
                'class_session_id' => $session->id,
                'student_id'       => $session->student_id,
                'status'           => $status,
                'note'             => ($session->id % 8 === 0) ? $this->faker->sentence(4) : null,
                'marked_by'        => null,
                'marked_at'        => Carbon::parse($session->session_date)->setTime(18, 0)->toDateTimeString(),
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('class_attendances')->insert($chunk);
        }

        return count($rows);
    }

    // ─────────────────────────────────────────────────────
    // SUBSCRIPTIONS — includes overdue for dashboard
    // ─────────────────────────────────────────────────────

    private function seedSubscriptions(Collection $enrollments): int
    {
        $rows = [];
        $now = now()->toDateTimeString();
        $seen = [];

        $active = $enrollments
            ->filter(fn (StudentEnrollment $e) => $e->status === EnrollmentStatusEnum::Active)
            ->values();

        // Pre-count completed sessions per enrollment (single query)
        $completedCounts = DB::table('class_sessions')
            ->where('status', SessionStatusEnum::Completed->value)
            ->whereIn('enrollment_id', $active->pluck('id'))
            ->selectRaw('enrollment_id, COUNT(*) as cnt')
            ->groupBy('enrollment_id')
            ->pluck('cnt', 'enrollment_id');

        // Payment distribution: 65% paid, 20% unpaid, 15% overdue
        $paymentPlan = [];
        $total = $active->count();
        $overdueCount = (int) max(1, round($total * 0.15));
        $unpaidCount = (int) max(1, round($total * 0.20));
        $paidCount = $total - $overdueCount - $unpaidCount;

        $paymentPlan = array_merge(
            array_fill(0, $paidCount, 'paid'),
            array_fill(0, $unpaidCount, 'unpaid'),
            array_fill(0, $overdueCount, 'overdue'),
        );

        foreach ($active as $index => $enrollment) {
            $key = "{$enrollment->student_id}-{$enrollment->teacher_id}-{$enrollment->instrument_id}";
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $sessionsUsed = (int) ($completedCounts[$enrollment->id] ?? 0);
            $sessionsAllocated = max($sessionsUsed + ($index % 5), 4);

            $paymentStatus = $paymentPlan[$index % count($paymentPlan)];

            $renewalDate = match ($paymentStatus) {
                'overdue' => $this->today->copy()->subDays(1 + ($index % 20)),
                'unpaid'  => $this->today->copy()->addDays(1 + ($index % 7)),
                default   => $this->today->copy()->addDays(8 + ($index % 22)),
            };

            $rows[] = [
                'student_id'         => $enrollment->student_id,
                'teacher_id'         => $enrollment->teacher_id,
                'instrument_id'      => $enrollment->instrument_id,
                'sessions_allocated' => $sessionsAllocated,
                'sessions_used'      => $sessionsUsed,
                'monthly_fee'        => $this->monthlyFees[$index % count($this->monthlyFees)],
                'payment_status'     => $paymentStatus,
                'renewal_date'       => $renewalDate->toDateString(),
                'notes'              => null,
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('subscriptions')->insertOrIgnore($chunk);
        }

        return count($rows);
    }

    // ─────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────

    private function pickWeightedTeacher(Collection $teachers, array $weights): Teacher
    {
        $pool = [];
        foreach ($teachers as $teacher) {
            $weight = $weights[$teacher->id] ?? 10;
            for ($i = 0; $i < $weight; $i++) {
                $pool[] = $teacher;
            }
        }

        return $pool[$this->faker->numberBetween(0, count($pool) - 1)];
    }

    private function resolveSessionStatus(
        int $teacherId,
        bool $isPast,
        bool $isToday,
        bool $isTomorrow,
    ): string {
        $profile = $this->teacherProfiles[$teacherId] ?? [
            'complete' => 75,
            'miss'     => 10,
            'cancel'   => 15,
        ];

        // Today & tomorrow: keep mostly scheduled so calendar/dashboard show upcoming work
        if ($isToday) {
            // Mix: ~70% still scheduled (upcoming today), ~25% completed, ~5% cancelled
            $roll = $this->faker->numberBetween(1, 100);

            return match (true) {
                $roll <= 70 => SessionStatusEnum::Scheduled->value,
                $roll <= 95 => SessionStatusEnum::Completed->value,
                default     => SessionStatusEnum::Cancelled->value,
            };
        }

        if ($isTomorrow) {
            return SessionStatusEnum::Scheduled->value;
        }

        if ($isPast) {
            $roll = $this->faker->numberBetween(1, 100);
            $complete = $profile['complete'];
            $miss = $complete + $profile['miss'];

            return match (true) {
                $roll <= $complete => SessionStatusEnum::Completed->value,
                $roll <= $miss     => SessionStatusEnum::Missed->value,
                default            => SessionStatusEnum::Cancelled->value,
            };
        }

        // Future beyond tomorrow: mostly scheduled, small cancel rate by teacher tier
        $cancelRate = (int) max(3, round($profile['cancel'] / 3));
        $roll = $this->faker->numberBetween(1, 100);

        return $roll <= (100 - $cancelRate)
            ? SessionStatusEnum::Scheduled->value
            : SessionStatusEnum::Cancelled->value;
    }

    private function resolveAttendanceStatus(int $studentId): string
    {
        $profile = $this->studentProfiles[$studentId] ?? [
            'present' => 85,
            'late'    => 5,
            'excused' => 3,
            'absent'  => 7,
        ];

        $roll = $this->faker->numberBetween(1, 100);
        $present = $profile['present'];
        $late = $present + $profile['late'];
        $excused = $late + $profile['excused'];

        return match (true) {
            $roll <= $present => AttendanceStatusEnum::Present->value,
            $roll <= $late    => AttendanceStatusEnum::Late->value,
            $roll <= $excused => AttendanceStatusEnum::Excused->value,
            default           => AttendanceStatusEnum::Absent->value,
        };
    }

    private function printSummary(): void
    {
        $todayCount = ClassSession::whereDate('session_date', $this->today)->count();
        $tomorrowCount = ClassSession::whereDate('session_date', $this->today->copy()->addDay())->count();
        $weekEnd = $this->today->copy()->endOfWeek(Carbon::SATURDAY);
        $weekCount = ClassSession::whereBetween('session_date', [
            $this->today->toDateString(),
            $weekEnd->toDateString(),
        ])->count();

        echo "\n🎉 Demo Database Seeding Complete!\n";
        echo str_repeat('━', 40) . "\n";
        echo sprintf("  Instruments:      %d\n", Instrument::count());
        echo sprintf("  Teachers:         %d\n", Teacher::count());
        echo sprintf("  Students:         %d\n", Student::count());
        echo sprintf("  Enrollments:      %d\n", StudentEnrollment::count());
        echo sprintf("  Schedules:        %d\n", RecurringSchedule::count());
        echo sprintf("  Sessions:         %d\n", ClassSession::count());
        echo sprintf("  Attendance:       %d\n", ClassAttendance::count());
        echo sprintf("  Subscriptions:    %d\n", Subscription::count());
        echo str_repeat('─', 40) . "\n";
        echo sprintf("  Today sessions:   %d\n", $todayCount);
        echo sprintf("  Tomorrow:         %d\n", $tomorrowCount);
        echo sprintf("  This week:        %d\n", $weekCount);
        echo sprintf("  Overdue subs:     %d\n", Subscription::where('payment_status', 'overdue')->count());
        echo sprintf("  Missed sessions:  %d\n", ClassSession::where('status', SessionStatusEnum::Missed)->count());
        echo sprintf("  Cancelled:        %d\n", ClassSession::where('status', SessionStatusEnum::Cancelled)->count());
        echo str_repeat('━', 40) . "\n\n";
    }
}
