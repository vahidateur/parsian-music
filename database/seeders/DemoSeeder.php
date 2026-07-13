<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatusEnum;
use App\Enums\EnrollmentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\LeadPriorityEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\LeadStatusEnum;
use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationEventEnum;
use App\Enums\NotificationPriorityEnum;
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
use App\Models\RecurringSchedule;
use App\Models\Room;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    private const SEED = 20260709;

    private Carbon $today;

    // Lookup caches populated during seeding
    private Collection $instruments;
    private Collection $teachers;
    private Collection $students;
    private Collection $enrollments;
    private Collection $rooms;

    // ─────────────────────────────────────────────────────────────────────────
    // ENTRY POINT
    // ─────────────────────────────────────────────────────────────────────────

    public function run(): void
    {
        fake()->seed(self::SEED);
        mt_srand(self::SEED);

        $this->today = Carbon::today();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        echo "\n📊 Seeding Demo Database...\n";
        echo str_repeat('━', 44) . "\n";

        $this->seedRooms();
        $this->instruments = $this->seedInstruments();
        $this->seedAdminUsers();
        $this->teachers   = $this->seedTeachers();
        $this->students   = $this->seedStudents();
        $this->seedUserLinks();
        $this->enrollments = $this->seedEnrollments();
        $this->seedRecurringSchedules();
        $this->seedClassSessions();
        $this->seedCalendarSessions();   // guarantee today + next 7 days coverage
        $this->seedAttendance();
        $this->seedSubscriptions();
        $this->seedInvoices();
        $this->seedLeads();
        $this->seedNotifications();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->runIntegrityCheck();
        $this->printSummary();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ROOMS
    // ─────────────────────────────────────────────────────────────────────────

    private function seedRooms(): void
    {
        $rooms = [
            ['name' => 'اتاق A101', 'capacity' => 1],
            ['name' => 'اتاق A102', 'capacity' => 1],
            ['name' => 'اتاق A103', 'capacity' => 2],
            ['name' => 'اتاق B201', 'capacity' => 1],
            ['name' => 'اتاق B202', 'capacity' => 1],
            ['name' => 'اتاق B203', 'capacity' => 3],
            ['name' => 'سالن C301', 'capacity' => 8],
            ['name' => 'سالن C302', 'capacity' => 10],
            ['name' => 'استودیو D1',  'capacity' => 4],
        ];

        foreach ($rooms as $r) {
            Room::firstOrCreate(['name' => $r['name']], ['capacity' => $r['capacity'], 'is_active' => true]);
        }

        $this->rooms = Room::where('is_active', true)->get();
        echo "  ✓ Rooms:               {$this->rooms->count()}\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INSTRUMENTS  (35 total)
    // ─────────────────────────────────────────────────────────────────────────

    private function seedInstruments(): Collection
    {
        $data = [
            // Persian
            ['name' => 'Tar',            'name_fa' => 'تار',            'slug' => 'tar'],
            ['name' => 'Setar',          'name_fa' => 'سه‌تار',         'slug' => 'setar'],
            ['name' => 'Santoor',        'name_fa' => 'سنتور',          'slug' => 'santoor'],
            ['name' => 'Kamancheh',      'name_fa' => 'کمانچه',         'slug' => 'kamancheh'],
            ['name' => 'Tonbak',         'name_fa' => 'تنبک',           'slug' => 'tonbak'],
            ['name' => 'Daf',            'name_fa' => 'دف',             'slug' => 'daf'],
            ['name' => 'Ney',            'name_fa' => 'نی',             'slug' => 'ney'],
            ['name' => 'Qanun',          'name_fa' => 'قانون',          'slug' => 'qanun'],
            ['name' => 'Oud',            'name_fa' => 'عود',            'slug' => 'oud'],
            ['name' => 'Violin Iranian', 'name_fa' => 'ویولن ایرانی',   'slug' => 'violin-iranian'],
            // Classical / International
            ['name' => 'Piano',          'name_fa' => 'پیانو',          'slug' => 'piano'],
            ['name' => 'Keyboard',       'name_fa' => 'کیبورد',         'slug' => 'keyboard'],
            ['name' => 'Violin',         'name_fa' => 'ویولن',          'slug' => 'violin'],
            ['name' => 'Cello',          'name_fa' => 'چلو',            'slug' => 'cello'],
            ['name' => 'Double Bass',    'name_fa' => 'کنترباس',        'slug' => 'double-bass'],
            ['name' => 'Classical Guitar','name_fa' => 'گیتار کلاسیک',  'slug' => 'classical-guitar'],
            ['name' => 'Electric Guitar','name_fa' => 'گیتار الکتریک',  'slug' => 'electric-guitar'],
            ['name' => 'Bass Guitar',    'name_fa' => 'گیتار باس',      'slug' => 'bass-guitar'],
            ['name' => 'Ukulele',        'name_fa' => 'اوکلله',         'slug' => 'ukulele'],
            ['name' => 'Flute',          'name_fa' => 'فلوت',           'slug' => 'flute'],
            ['name' => 'Clarinet',       'name_fa' => 'کلارینت',        'slug' => 'clarinet'],
            ['name' => 'Oboe',           'name_fa' => 'اوبوا',          'slug' => 'oboe'],
            ['name' => 'Saxophone',      'name_fa' => 'ساکسیفون',       'slug' => 'saxophone'],
            ['name' => 'Trumpet',        'name_fa' => 'ترومپت',         'slug' => 'trumpet'],
            ['name' => 'Trombone',       'name_fa' => 'ترومبون',        'slug' => 'trombone'],
            ['name' => 'French Horn',    'name_fa' => 'هورن فرانسوی',   'slug' => 'french-horn'],
            ['name' => 'Tuba',           'name_fa' => 'توبا',           'slug' => 'tuba'],
            ['name' => 'Drums',          'name_fa' => 'درام',           'slug' => 'drums'],
            ['name' => 'Percussion',     'name_fa' => 'کوبه‌ای',        'slug' => 'percussion'],
            ['name' => 'Harp',           'name_fa' => 'هارپ',           'slug' => 'harp'],
            ['name' => 'Voice',          'name_fa' => 'آواز',           'slug' => 'voice'],
            ['name' => 'Choir',          'name_fa' => 'گروه کُر',       'slug' => 'choir'],
            ['name' => 'Music Theory',   'name_fa' => 'تئوری موسیقی',   'slug' => 'music-theory'],
            ['name' => 'Composition',    'name_fa' => 'آهنگ‌سازی',      'slug' => 'composition'],
            ['name' => 'Ear Training',   'name_fa' => 'تربیت شنوایی',   'slug' => 'ear-training'],
        ];

        foreach ($data as $row) {
            Instrument::firstOrCreate(
                ['slug' => $row['slug']],
                array_merge($row, ['is_active' => true])
            );
        }

        $result = Instrument::where('is_active', true)->get();
        echo "  ✓ Instruments:         {$result->count()}\n";
        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN USERS  (1 super_admin + 3 admin)
    // ─────────────────────────────────────────────────────────────────────────

    private function seedAdminUsers(): void
    {
        $admins = [
            ['phone' => 'YOUR_PHONE_HERE', 'full_name' => 'YOUR_NAME_HERE', 'role' => RoleEnum::SUPER_ADMIN],
            ['phone' => '09120000001', 'full_name' => 'علی رضایی',   'role' => RoleEnum::ADMIN],
            ['phone' => '09120000002', 'full_name' => 'مریم صادقی',  'role' => RoleEnum::ADMIN],
            ['phone' => '09120000003', 'full_name' => 'حسین موسوی',  'role' => RoleEnum::ADMIN],
        ];

        foreach ($admins as $a) {
            User::firstOrCreate(
                ['phone' => $a['phone']],
                [
                    'full_name'             => $a['full_name'],
                    'password'              => Hash::make('YOUR_PASSWORD_HERE'),
                    'role'                  => $a['role'],
                    'is_active'             => true,
                    'force_password_change' => false,
                ]
            );
        }

        echo "  ✓ Admin users:         4\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEACHERS  (15)
    // ─────────────────────────────────────────────────────────────────────────

    private function seedTeachers(): Collection
    {
        $data = [
            [
                'full_name' => 'محمدرضا کریمی',  'phone' => '09121000001',
                'instruments' => ['piano', 'music-theory'],
                'hire_months' => 72, 'status' => TeacherStatusEnum::Active,
                'bio' => 'استاد پیانو با بیش از ۱۵ سال تجربه. فارغ‌التحصیل کنسرواتوار تهران.',
            ],
            [
                'full_name' => 'فاطمه علیزاده', 'phone' => '09121000002',
                'instruments' => ['classical-guitar', 'electric-guitar'],
                'hire_months' => 48, 'status' => TeacherStatusEnum::Active,
                'bio' => 'گیتاریست حرفه‌ای، برنده چند جشنواره ملی موسیقی.',
            ],
            [
                'full_name' => 'علی محمودی',     'phone' => '09121000003',
                'instruments' => ['violin', 'cello'],
                'hire_months' => 84, 'status' => TeacherStatusEnum::Active,
                'bio' => 'ویولنیست با سابقه اجرا در ارکستر سمفونیک تهران.',
            ],
            [
                'full_name' => 'نسرین رستمی',    'phone' => '09121000004',
                'instruments' => ['santoor', 'tar'],
                'hire_months' => 36, 'status' => TeacherStatusEnum::Active,
                'bio' => 'نوازنده و مدرس موسیقی سنتی ایرانی.',
            ],
            [
                'full_name' => 'حمید اسلامی',    'phone' => '09121000005',
                'instruments' => ['daf', 'tonbak', 'percussion'],
                'hire_months' => 24, 'status' => TeacherStatusEnum::Active,
                'bio' => 'متخصص سازهای کوبه‌ای ایرانی و جهانی.',
            ],
            [
                'full_name' => 'مریم سعیدی',     'phone' => '09121000006',
                'instruments' => ['voice', 'choir'],
                'hire_months' => 60, 'status' => TeacherStatusEnum::Active,
                'bio' => 'مدرس آواز کلاسیک و پاپ با مدرک فوق‌لیسانس موسیقی.',
            ],
            [
                'full_name' => 'آرمان طاهری',    'phone' => '09121000007',
                'instruments' => ['setar', 'ney'],
                'hire_months' => 54, 'status' => TeacherStatusEnum::Active,
                'bio' => 'نوازنده سه‌تار و نی، دکترای موسیقی از دانشگاه هنر.',
            ],
            [
                'full_name' => 'پریسا قاسمی',    'phone' => '09121000008',
                'instruments' => ['flute', 'clarinet'],
                'hire_months' => 18, 'status' => TeacherStatusEnum::Active,
                'bio' => 'نوازنده فلوت و کلارینت، عضو ارکستر سمفونیک تهران.',
            ],
            [
                'full_name' => 'سیاوش جعفری',    'phone' => '09121000009',
                'instruments' => ['drums', 'bass-guitar'],
                'hire_months' => 30, 'status' => TeacherStatusEnum::Active,
                'bio' => 'درامر حرفه‌ای با تجربه همکاری با گروه‌های موسیقی راک و پاپ.',
            ],
            [
                'full_name' => 'گلناز حیدری',    'phone' => '09121000010',
                'instruments' => ['kamancheh', 'violin-iranian'],
                'hire_months' => 42, 'status' => TeacherStatusEnum::Active,
                'bio' => 'نوازنده کمانچه و متخصص موسیقی دستگاهی ایران.',
            ],
            [
                'full_name' => 'داریوش مهدوی',   'phone' => '09121000011',
                'instruments' => ['oud', 'qanun'],
                'hire_months' => 20, 'status' => TeacherStatusEnum::Active,
                'bio' => 'نوازنده عود و قانون، سابقه تدریس در ایران و لبنان.',
            ],
            [
                'full_name' => 'شادی سلیمانی',   'phone' => '09121000012',
                'instruments' => ['keyboard', 'composition'],
                'hire_months' => 12, 'status' => TeacherStatusEnum::Active,
                'bio' => 'آهنگ‌ساز و مدرس کیبورد، فعال در حوزه موسیقی فیلم.',
            ],
            [
                'full_name' => 'رضا احمدی',      'phone' => '09121000013',
                'instruments' => ['saxophone', 'trumpet'],
                'hire_months' => 16, 'status' => TeacherStatusEnum::Active,
                'bio' => 'نوازنده ساکسیفون و ترومپت با سابقه اجرا در جاز.',
            ],
            [
                'full_name' => 'زهرا کاظمی',     'phone' => '09121000014',
                'instruments' => ['ear-training', 'music-theory'],
                'hire_months' => 8, 'status' => TeacherStatusEnum::Active,
                'bio' => 'مدرس تئوری موسیقی و تربیت شنوایی.',
            ],
            [
                'full_name' => 'بهزاد نظری',     'phone' => '09121000015',
                'instruments' => ['ukulele', 'classical-guitar'],
                'hire_months' => 6, 'status' => TeacherStatusEnum::Inactive,
                'bio' => 'مدرس گیتار و اوکلله، در حال تحصیل در خارج از کشور.',
            ],
        ];

        $bySlug = $this->instruments->keyBy('slug');

        foreach ($data as $row) {
            $teacher = Teacher::firstOrCreate(
                ['phone' => $row['phone']],
                [
                    'full_name'  => $row['full_name'],
                    'status'     => $row['status'],
                    'bio'        => $row['bio'],
                    'hire_date'  => $this->today->copy()->subMonths($row['hire_months'])->toDateString(),
                ]
            );

            // Attach instruments (skip if already attached)
            $existingSlugs = $teacher->instruments->pluck('slug')->all();
            foreach ($row['instruments'] as $idx => $slug) {
                if (in_array($slug, $existingSlugs, true)) {
                    continue;
                }
                $instrument = $bySlug->get($slug);
                if (! $instrument) {
                    continue;
                }
                $teacher->instruments()->attach($instrument->id, [
                    'skill_level' => $idx === 0 ? SkillLevelEnum::Expert->value : SkillLevelEnum::Advanced->value,
                    'is_primary'  => $idx === 0,
                ]);
            }
        }

        $result = Teacher::with('instruments')->get();
        echo "  ✓ Teachers:            {$result->count()}\n";
        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STUDENTS  (120)
    // ─────────────────────────────────────────────────────────────────────────

    private function seedStudents(): Collection
    {
        // 120 deterministic students: children/teens/adults, mixed gender, varied status
        $firstNames = [
            'علی','محمد','حسین','مهدی','رضا','امیر','سامان','آرین','بهزاد','کمال',
            'جواد','محسن','صالح','مسعود','هیربد','بهرام','آرمان','کاوه','داریوش','شاهین',
            'پویا','نیما','سعید','وحید','بابک','کیارش','پارسا','ایمان','فرزاد','نادر',
            'زهرا','فاطمه','مریم','الهام','شیرین','آزاده','نیلوفر','پریا','یاسمن','نرگس',
            'دنیا','ساره','سپیده','لیلا','زیبا','مهناز','نسرین','پریسا','شقایق','گلناز',
            'نازنین','بهناز','شادی','آتنا','مهسا','رویا','صبا','سوسن','هانیه','درسا',
        ];

        $lastNames = [
            'احمدی','رحمانی','حسنی','کریمی','توکلی','موسوی','شریفی','رضایی','جمشیدی','صادقی',
            'میرزایی','اکبری','علیخانی','رزاقی','شاکری','باقری','سلطانی','کاظمی','شهبازی','مرادی',
            'فرجام','درویشی','هاشمی','بهرامی','اسدی','منصوری','زارعی','طاهری','محمودی','نظری',
            'قاسمی','جعفری','حیدری','محمدی','رجبی','مهدوی','سلیمانی','عباسی','خانی','حبیبی',
            'تهرانی','اصفهانی','مشهدی','تبریزی','کرمانی','یزدی','شیرازی','گیلانی','مازندرانی','خراسانی',
        ];

        // Status distribution: 85 active, 15 paused, 12 inactive, 8 graduated
        $statusMap = array_merge(
            array_fill(0, 85,  StudentStatusEnum::Active->value),
            array_fill(0, 15,  StudentStatusEnum::Paused->value),
            array_fill(0, 12,  StudentStatusEnum::Inactive->value),
            array_fill(0, 8,   StudentStatusEnum::Graduated->value),
        );

        for ($i = 0; $i < 120; $i++) {
            $phone = '0913' . str_pad((string)(2000000 + $i), 7, '0', STR_PAD_LEFT);

            // Age: children 7-12 (idx 0-29), teens 13-17 (idx 30-59), adults 18-45 (idx 60-119)
            if ($i < 30) {
                $joinAgo = fake()->numberBetween(3, 24);  // children: newer
            } elseif ($i < 60) {
                $joinAgo = fake()->numberBetween(6, 36);
            } else {
                $joinAgo = fake()->numberBetween(1, 48);
            }

            // Last 15 students joined very recently (for dashboard "new students" widget)
            if ($i >= 105) {
                $joinDate = $this->today->copy()->subDays(fake()->numberBetween(1, 14))->toDateString();
            } else {
                $joinDate = $this->today->copy()->subMonths($joinAgo)->toDateString();
            }

            $firstName = $firstNames[$i % count($firstNames)];
            $lastName  = $lastNames[($i * 7 + 3) % count($lastNames)];
            $fullName  = $firstName . ' ' . $lastName;

            // Parent phone for children/teens
            $parentPhone = ($i < 60)
                ? ('0911' . str_pad((string)(3000000 + $i), 7, '0', STR_PAD_LEFT))
                : null;

            Student::firstOrCreate(
                ['phone' => $phone],
                [
                    'full_name'    => $fullName,
                    'parent_phone' => $parentPhone,
                    'status'       => $statusMap[$i],
                    'join_date'    => $joinDate,
                    'notes'        => ($i % 8 === 0) ? 'هنرجوی مستعد، پیشرفت سریع' : null,
                ]
            );
        }

        $result = Student::all();
        echo "  ✓ Students:            {$result->count()}\n";
        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // USER LINKS  — create User accounts for teachers and students
    // ─────────────────────────────────────────────────────────────────────────

    private function seedUserLinks(): void
    {
        $usedPhones = User::pluck('phone')->flip()->all();

        foreach ($this->teachers as $teacher) {
            if ($teacher->user_id) {
                $usedPhones[$teacher->phone] = true;
                continue;
            }
            $phone = $this->resolvePhone($teacher->phone, $usedPhones, 'teacher', $teacher->id);
            $usedPhones[$phone] = true;

            $user = User::firstOrCreate(
                ['phone' => $phone],
                [
                    'full_name'             => $teacher->full_name,
                    'password'              => Hash::make('12345678'),
                    'role'                  => RoleEnum::TEACHER,
                    'is_active'             => $teacher->status === TeacherStatusEnum::Active,
                    'force_password_change' => false,
                ]
            );
            $teacher->update(['user_id' => $user->id]);
        }

        foreach ($this->students as $student) {
            if ($student->user_id) {
                $usedPhones[$student->phone] = true;
                continue;
            }
            $phone = $this->resolvePhone($student->phone, $usedPhones, 'student', $student->id);
            $usedPhones[$phone] = true;

            $user = User::firstOrCreate(
                ['phone' => $phone],
                [
                    'full_name'             => $student->full_name,
                    'password'              => Hash::make('12345678'),
                    'role'                  => RoleEnum::STUDENT,
                    'is_active'             => $student->status === StudentStatusEnum::Active,
                    'force_password_change' => false,
                ]
            );
            $student->update(['user_id' => $user->id]);
        }

        echo "  ✓ Users total:         " . User::count() . "\n";
    }

    private function resolvePhone(?string $phone, array &$used, string $type, int $id): string
    {
        if ($phone && ! isset($used[$phone])) {
            return $phone;
        }
        $prefix   = $type === 'teacher' ? '0900' : '0901';
        $fallback = $prefix . str_pad((string) $id, 7, '0', STR_PAD_LEFT);
        if (isset($used[$fallback])) {
            throw new \RuntimeException("Cannot resolve unique phone for {$type} #{$id}");
        }
        return $fallback;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENROLLMENTS
    // Each active teacher gets 6-12 enrollments. Each student gets 1-3.
    // ─────────────────────────────────────────────────────────────────────────

    private function seedEnrollments(): Collection
    {
        $activeStudents = $this->students
            ->filter(fn ($s) => $s->status === StudentStatusEnum::Active->value
                             || $s->status === StudentStatusEnum::Active)
            ->values();

        $allStudents   = $this->students->values();
        $activeTeachers = $this->teachers->filter(fn ($t) => $t->status === TeacherStatusEnum::Active)->values();

        $existing = StudentEnrollment::withTrashed()
            ->select('student_id', 'teacher_id', 'instrument_id')
            ->get()
            ->map(fn ($e) => "{$e->student_id}-{$e->teacher_id}-{$e->instrument_id}")
            ->flip()
            ->all();

        $skillCycle = [
            SkillLevelEnum::Beginner->value,
            SkillLevelEnum::Beginner->value,
            SkillLevelEnum::Intermediate->value,
            SkillLevelEnum::Intermediate->value,
            SkillLevelEnum::Advanced->value,
            SkillLevelEnum::Expert->value,
        ];

        $created = 0;

        foreach ($activeTeachers as $tIdx => $teacher) {
            $instruments = $teacher->instruments;
            if ($instruments->isEmpty()) {
                continue;
            }

            // Target 8-14 active enrollments per teacher
            $target = 8 + ($tIdx % 7);

            for ($i = 0; $i < $target; $i++) {
                $student    = $activeStudents[($tIdx * 17 + $i * 13) % $activeStudents->count()];
                $instrument = $instruments[$i % $instruments->count()];
                $key        = "{$student->id}-{$teacher->id}-{$instrument->id}";

                if (isset($existing[$key])) {
                    continue;
                }
                $existing[$key] = true;

                $monthsAgo = 1 + (($tIdx + $i) % 18);
                StudentEnrollment::create([
                    'student_id'    => $student->id,
                    'teacher_id'    => $teacher->id,
                    'instrument_id' => $instrument->id,
                    'skill_level'   => $skillCycle[($tIdx + $i) % count($skillCycle)],
                    'status'        => EnrollmentStatusEnum::Active->value,
                    'started_at'    => $this->today->copy()->subMonths($monthsAgo)->toDateString(),
                    'ended_at'      => null,
                    'notes'         => null,
                ]);
                $created++;
            }
        }

        // Add ~40 completed/cancelled enrollments for historical data
        foreach ($allStudents->take(40) as $sIdx => $student) {
            $teacher    = $activeTeachers[$sIdx % $activeTeachers->count()];
            $instrument = $teacher->instruments->first();
            if (! $instrument) {
                continue;
            }

            $key = "{$student->id}-{$teacher->id}-{$instrument->id}";
            if (isset($existing[$key])) {
                continue;
            }
            $existing[$key] = true;

            $startAgo = 12 + ($sIdx % 24);
            $endAgo   = 1  + ($sIdx % 6);
            $status   = ($sIdx % 3 === 0)
                ? EnrollmentStatusEnum::Cancelled->value
                : EnrollmentStatusEnum::Completed->value;

            StudentEnrollment::create([
                'student_id'    => $student->id,
                'teacher_id'    => $teacher->id,
                'instrument_id' => $instrument->id,
                'skill_level'   => SkillLevelEnum::Intermediate->value,
                'status'        => $status,
                'started_at'    => $this->today->copy()->subMonths($startAgo)->toDateString(),
                'ended_at'      => $this->today->copy()->subMonths($endAgo)->toDateString(),
                'notes'         => null,
            ]);
            $created++;
        }

        $result = StudentEnrollment::withTrashed()->get();
        echo "  ✓ Enrollments:         {$result->count()}\n";
        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RECURRING SCHEDULES
    // ─────────────────────────────────────────────────────────────────────────

    private function seedRecurringSchedules(): void
    {
        $activeEnrollments = $this->enrollments
            ->filter(fn ($e) => $e->status === EnrollmentStatusEnum::Active->value
                             || $e->status === EnrollmentStatusEnum::Active)
            ->values();

        $existingIds = RecurringSchedule::pluck('enrollment_id')->flip()->all();

        $roomNames  = $this->rooms->pluck('name')->values()->all();
        $times      = ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00', '18:00'];
        $durations  = [45, 60, 60, 90, 45, 60];

        // Track teacher slot usage to avoid overlaps: teacher_id => weekday => [times]
        $teacherSlots = [];

        foreach ($activeEnrollments as $idx => $enrollment) {
            if (isset($existingIds[$enrollment->id])) {
                continue;
            }

            $weekday  = ($idx * 3 + 1) % 6; // 0=Sun … 5=Fri (avoid Friday=6)
            $time     = $times[$idx % count($times)];
            $room     = $roomNames[$idx % count($roomNames)];
            $duration = $durations[$idx % count($durations)];

            // Avoid teacher time clash by shifting
            $tid = $enrollment->teacher_id;
            $slotKey = "{$weekday}-{$time}";
            $attempt = 0;
            while (isset($teacherSlots[$tid][$slotKey]) && $attempt < 8) {
                $time    = $times[($idx + $attempt + 1) % count($times)];
                $slotKey = "{$weekday}-{$time}";
                $attempt++;
            }
            $teacherSlots[$tid][$slotKey] = true;

            RecurringSchedule::create([
                'enrollment_id'    => $enrollment->id,
                'weekday'          => $weekday,
                'start_time'       => $time,
                'duration_minutes' => $duration,
                'room'             => $room,
                'is_active'        => true,
            ]);
        }

        echo "  ✓ Recurring Schedules: " . RecurringSchedule::count() . "\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CLASS SESSIONS  (60 days back + 60 days forward)
    // ─────────────────────────────────────────────────────────────────────────

    private function seedClassSessions(): void
    {
        $rangeStart = $this->today->copy()->subDays(60);
        $rangeEnd   = $this->today->copy()->addDays(60);

        $schedules = RecurringSchedule::with('enrollment')->where('is_active', true)->get();

        // Collect existing session keys to skip duplicates
        $existingKeys = DB::table('class_sessions')
            ->select('enrollment_id', 'session_date')
            ->get()
            ->map(fn ($r) => "{$r->enrollment_id}-{$r->session_date}")
            ->flip()
            ->all();

        $rows = [];
        $now  = now()->toDateTimeString();

        $fees = [400000, 500000, 600000, 750000, 900000, 1000000, 1200000];

        foreach ($schedules as $schedule) {
            $enrollment = $schedule->enrollment;
            if (! $enrollment) {
                continue;
            }

            // Walk to first occurrence of this weekday at or after rangeStart
            $cursor = $rangeStart->copy();
            while ((int) $cursor->dayOfWeek !== (int) $schedule->weekday) {
                $cursor->addDay();
            }

            while ($cursor->lte($rangeEnd)) {
                // Respect enrollment date boundaries
                if ($enrollment->started_at && $cursor->lt(Carbon::parse($enrollment->started_at))) {
                    $cursor->addWeek();
                    continue;
                }
                if ($enrollment->ended_at && $cursor->gt(Carbon::parse($enrollment->ended_at))) {
                    $cursor->addWeek();
                    continue;
                }

                $dateStr = $cursor->toDateString();
                $key     = "{$enrollment->id}-{$dateStr}";

                if (isset($existingKeys[$key])) {
                    $cursor->addWeek();
                    continue;
                }
                $existingKeys[$key] = true;

                $isPast     = $cursor->lt($this->today);
                $isToday    = $cursor->isSameDay($this->today);
                $isTomorrow = $cursor->isSameDay($this->today->copy()->addDay());

                $status = $this->resolveSessionStatus(
                    $enrollment->teacher_id, $isPast, $isToday, $isTomorrow, $enrollment->id + $cursor->dayOfYear
                );

                $feeIdx = ($enrollment->id + $cursor->dayOfYear) % count($fees);

                $rows[] = [
                    'enrollment_id'         => $enrollment->id,
                    'student_id'            => $enrollment->student_id,
                    'teacher_id'            => $enrollment->teacher_id,
                    'instrument_id'         => $enrollment->instrument_id,
                    'recurring_schedule_id' => $schedule->id,
                    'session_date'          => $dateStr,
                    'start_time'            => $schedule->start_time,
                    'duration_minutes'      => $schedule->duration_minutes,
                    'status'                => $status,
                    'room'                  => $schedule->room,
                    'session_fee'           => $fees[$feeIdx],
                    'discount'              => ($enrollment->id % 10 === 0) ? 50000 : 0,
                    'notes'                 => ($enrollment->id % 15 === 0) ? 'یادداشت جلسه' : null,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ];

                $cursor->addWeek();
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('class_sessions')->insert($chunk);
        }

        echo "  ✓ Class Sessions:      " . count($rows) . "\n";
    }

    /**
     * Guarantee every day from today through the next 7 days has sessions.
     * Takes active enrollments and distributes them across the day slots.
     * Idempotent: skips enrollment+date combos that already exist.
     */
    private function seedCalendarSessions(): void
    {
        $activeEnrollments = StudentEnrollment::where('status', \App\Enums\EnrollmentStatusEnum::Active->value)
            ->with('teacher')
            ->take(80)
            ->get();

        if ($activeEnrollments->isEmpty()) {
            return;
        }

        $existingKeys = DB::table('class_sessions')
            ->select('enrollment_id', 'session_date')
            ->get()
            ->map(fn ($r) => "{$r->enrollment_id}-{$r->session_date}")
            ->flip()
            ->all();

        $roomNames = $this->rooms->pluck('name')->values()->all();
        $slots     = ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00', '18:00'];
        $fees      = [400000, 500000, 600000, 750000, 900000, 1000000];
        $durations = [45, 60, 60, 90];

        $rows = [];
        $now  = now()->toDateTimeString();

        // For today + next 14 days: distribute enrollments across each day
        for ($dayOffset = 0; $dayOffset <= 14; $dayOffset++) {
            $date    = $this->today->copy()->addDays($dayOffset)->toDateString();
            $isToday = $dayOffset === 0;
            $isPast  = false;

            // Pick ~12 enrollments per day (rotating through the pool)
            $base = $dayOffset * 12;
            for ($i = 0; $i < 12; $i++) {
                $enrollment = $activeEnrollments[($base + $i) % $activeEnrollments->count()];
                $key        = "{$enrollment->id}-{$date}";

                if (isset($existingKeys[$key])) {
                    continue;
                }
                $existingKeys[$key] = true;

                $status = $isToday
                    ? (($i % 4 === 0) ? SessionStatusEnum::Completed->value : SessionStatusEnum::Scheduled->value)
                    : SessionStatusEnum::Scheduled->value;

                $rows[] = [
                    'enrollment_id'         => $enrollment->id,
                    'student_id'            => $enrollment->student_id,
                    'teacher_id'            => $enrollment->teacher_id,
                    'instrument_id'         => $enrollment->instrument_id,
                    'recurring_schedule_id' => null,
                    'session_date'          => $date,
                    'start_time'            => $slots[($i + $dayOffset) % count($slots)],
                    'duration_minutes'      => $durations[($i + $dayOffset) % count($durations)],
                    'status'                => $status,
                    'room'                  => $roomNames[($i + $dayOffset) % count($roomNames)],
                    'session_fee'           => $fees[($i + $dayOffset) % count($fees)],
                    'discount'              => 0,
                    'notes'                 => null,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ];
            }
        }

        // Also seed 45 days back with 8 sessions/day for history charts
        for ($dayOffset = 1; $dayOffset <= 45; $dayOffset++) {
            $date = $this->today->copy()->subDays($dayOffset)->toDateString();
            $base = $dayOffset * 8;
            for ($i = 0; $i < 8; $i++) {
                $enrollment = $activeEnrollments[($base + $i) % $activeEnrollments->count()];
                $key        = "{$enrollment->id}-{$date}";
                if (isset($existingKeys[$key])) {
                    continue;
                }
                $existingKeys[$key] = true;

                $seed = $enrollment->id + $dayOffset + $i;
                $r    = ($seed * 7) % 100;
                $status = match (true) {
                    $r < 75 => SessionStatusEnum::Completed->value,
                    $r < 88 => SessionStatusEnum::Missed->value,
                    default => SessionStatusEnum::Cancelled->value,
                };

                $rows[] = [
                    'enrollment_id'         => $enrollment->id,
                    'student_id'            => $enrollment->student_id,
                    'teacher_id'            => $enrollment->teacher_id,
                    'instrument_id'         => $enrollment->instrument_id,
                    'recurring_schedule_id' => null,
                    'session_date'          => $date,
                    'start_time'            => $slots[($i + $dayOffset) % count($slots)],
                    'duration_minutes'      => $durations[$i % count($durations)],
                    'status'                => $status,
                    'room'                  => $roomNames[($i + $dayOffset) % count($roomNames)],
                    'session_fee'           => $fees[$i % count($fees)],
                    'discount'              => 0,
                    'notes'                 => null,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('class_sessions')->insert($chunk);
        }

        echo "  ✓ Calendar Sessions:   " . count($rows) . " extra\n";
    }

    private function resolveSessionStatus(int $teacherId, bool $isPast, bool $isToday, bool $isTomorrow, int $seed): string
    {
        if ($isTomorrow) {
            return SessionStatusEnum::Scheduled->value;
        }

        if ($isToday) {
            // 70% scheduled (still upcoming today), 25% completed, 5% cancelled
            $r = $seed % 100;
            if ($r < 5)  return SessionStatusEnum::Cancelled->value;
            if ($r < 30) return SessionStatusEnum::Completed->value;
            return SessionStatusEnum::Scheduled->value;
        }

        if ($isPast) {
            // Per-teacher deterministic profile: high-id = weaker performance
            $r = ($seed * 7 + $teacherId) % 100;
            if ($r < 75) return SessionStatusEnum::Completed->value;
            if ($r < 88) return SessionStatusEnum::Missed->value;
            return SessionStatusEnum::Cancelled->value;
        }

        // Future: mostly scheduled, small cancellation rate
        $r = ($seed * 3) % 100;
        return $r < 5
            ? SessionStatusEnum::Cancelled->value
            : SessionStatusEnum::Scheduled->value;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ATTENDANCE
    // ─────────────────────────────────────────────────────────────────────────

    private function seedAttendance(): void
    {
        // Only mark attendance for past completed/missed sessions
        $sessions = DB::table('class_sessions')
            ->whereIn('status', [SessionStatusEnum::Completed->value, SessionStatusEnum::Missed->value])
            ->select('id', 'student_id', 'status', 'session_date', 'teacher_id')
            ->orderBy('id')
            ->get();

        // Skip sessions that already have attendance
        $existing = DB::table('class_attendances')
            ->pluck('class_session_id')
            ->flip()
            ->all();

        $rows = [];
        $now  = now()->toDateTimeString();

        foreach ($sessions as $session) {
            if (isset($existing[$session->id])) {
                continue;
            }

            if ($session->status === SessionStatusEnum::Missed->value) {
                $status = AttendanceStatusEnum::Absent->value;
            } else {
                // ~90% present, 4% late, 3% excused, 3% absent
                $r = ($session->id * 7 + $session->student_id) % 100;
                $status = match (true) {
                    $r < 3  => AttendanceStatusEnum::Absent->value,
                    $r < 6  => AttendanceStatusEnum::Excused->value,
                    $r < 10 => AttendanceStatusEnum::Late->value,
                    default  => AttendanceStatusEnum::Present->value,
                };
            }

            $rows[] = [
                'class_session_id' => $session->id,
                'student_id'       => $session->student_id,
                'status'           => $status,
                'note'             => ($session->id % 12 === 0) ? 'تأخیر ترافیکی' : null,
                'marked_by'        => null,
                'marked_at'        => Carbon::parse($session->session_date)->setTime(19, 0)->toDateTimeString(),
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('class_attendances')->insert($chunk);
        }

        echo "  ✓ Attendance:          " . (DB::table('class_attendances')->count()) . "\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SUBSCRIPTIONS
    // ─────────────────────────────────────────────────────────────────────────

    private function seedSubscriptions(): void
    {
        $activeEnrollments = $this->enrollments
            ->filter(fn ($e) => $e->status === EnrollmentStatusEnum::Active->value
                             || $e->status === EnrollmentStatusEnum::Active)
            ->values();

        $monthlyFees = [2000000, 2500000, 3000000, 3500000, 4000000, 5000000];

        // Payment distribution: 65% paid, 20% unpaid, 15% overdue
        $paymentPlan = array_merge(
            array_fill(0, 65, 'paid'),
            array_fill(0, 20, 'unpaid'),
            array_fill(0, 15, 'overdue'),
        );

        $completedCounts = DB::table('class_sessions')
            ->where('status', SessionStatusEnum::Completed->value)
            ->whereIn('enrollment_id', $activeEnrollments->pluck('id'))
            ->selectRaw('enrollment_id, COUNT(*) as cnt')
            ->groupBy('enrollment_id')
            ->pluck('cnt', 'enrollment_id');

        $inserted = 0;
        foreach ($activeEnrollments as $idx => $enrollment) {
            // Skip if already exists
            $exists = DB::table('subscriptions')
                ->where('student_id', $enrollment->student_id)
                ->where('teacher_id', $enrollment->teacher_id)
                ->where('instrument_id', $enrollment->instrument_id)
                ->exists();
            if ($exists) {
                continue;
            }

            $sessionsUsed      = (int) ($completedCounts[$enrollment->id] ?? 0);
            $sessionsAllocated = max($sessionsUsed + ($idx % 4), 4);
            $paymentStatus     = $paymentPlan[$idx % count($paymentPlan)];

            $renewalDate = match ($paymentStatus) {
                'overdue' => $this->today->copy()->subDays(1 + ($idx % 15))->toDateString(),
                'unpaid'  => $this->today->copy()->addDays(2 + ($idx % 8))->toDateString(),
                default   => $this->today->copy()->addDays(10 + ($idx % 20))->toDateString(),
            };

            DB::table('subscriptions')->insert([
                'student_id'         => $enrollment->student_id,
                'teacher_id'         => $enrollment->teacher_id,
                'instrument_id'      => $enrollment->instrument_id,
                'sessions_allocated' => $sessionsAllocated,
                'sessions_used'      => $sessionsUsed,
                'monthly_fee'        => $monthlyFees[$idx % count($monthlyFees)],
                'payment_status'     => $paymentStatus,
                'renewal_date'       => $renewalDate,
                'notes'              => null,
                'created_at'         => now()->toDateTimeString(),
                'updated_at'         => now()->toDateTimeString(),
            ]);
            $inserted++;
        }

        echo "  ✓ Subscriptions:       " . DB::table('subscriptions')->count() . "\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INVOICES + ITEMS + PAYMENTS
    // ─────────────────────────────────────────────────────────────────────────

    private function seedInvoices(): void
    {
        $activeEnrollments = $this->enrollments
            ->filter(fn ($e) => $e->status === EnrollmentStatusEnum::Active->value
                             || $e->status === EnrollmentStatusEnum::Active)
            ->values();

        // Skip enrollments that already have invoices
        $existingEnrollmentIds = DB::table('invoices')
            ->whereNotNull('enrollment_id')
            ->pluck('enrollment_id')
            ->flip()
            ->all();

        $statusCycle = [
            InvoiceStatusEnum::Paid->value,
            InvoiceStatusEnum::Paid->value,
            InvoiceStatusEnum::Paid->value,
            InvoiceStatusEnum::PartiallyPaid->value,
            InvoiceStatusEnum::Issued->value,
            InvoiceStatusEnum::Overdue->value,
            InvoiceStatusEnum::Draft->value,
            InvoiceStatusEnum::Cancelled->value,
        ];

        $methodCycle = [
            PaymentMethodEnum::Cash->value,
            PaymentMethodEnum::Card->value,
            PaymentMethodEnum::BankTransfer->value,
            PaymentMethodEnum::Cash->value,
            PaymentMethodEnum::Card->value,
        ];

        $now = now()->toDateTimeString();
        $invoiceCount = 0;

        foreach ($activeEnrollments as $idx => $enrollment) {
            if (isset($existingEnrollmentIds[$enrollment->id])) {
                continue;
            }

            // Generate 1-3 monthly invoices per enrollment
            $invoiceCount_for_enrollment = 1 + ($idx % 3);

            for ($m = 0; $m < $invoiceCount_for_enrollment; $m++) {
                $monthsAgo   = $m + ($idx % 4);
                $issueDate   = $this->today->copy()->subMonths($monthsAgo)->startOfMonth();
                $dueDate     = $issueDate->copy()->addDays(15);
                $statusValue = $statusCycle[($idx + $m) % count($statusCycle)];

                $unitPrice = 3000000 + (($idx % 8) * 500000);
                $discount  = ($idx % 5 === 0) ? 200000 : 0;
                $tax       = (int) round(($unitPrice - $discount) * 0.09);
                $subtotal  = $unitPrice;
                $total     = $subtotal - $discount + $tax;

                $invoice = Invoice::create([
                    'student_id'    => $enrollment->student_id,
                    'enrollment_id' => $enrollment->id,
                    'issue_date'    => $issueDate->toDateString(),
                    'due_date'      => $dueDate->toDateString(),
                    'subtotal'      => $subtotal,
                    'discount'      => $discount,
                    'tax'           => $tax,
                    'total'         => $total,
                    'currency'      => 'IRR',
                    'status'        => $statusValue,
                    'notes'         => null,
                ]);

                InvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'title'       => 'شهریه ماهانه — ' . $enrollment->instrument?->name_fa,
                    'description' => null,
                    'quantity'    => 1,
                    'unit_price'  => $unitPrice,
                    'discount'    => $discount,
                    'sort_order'  => 0,
                ]);

                // Generate payments for paid/partially-paid invoices
                if ($statusValue === InvoiceStatusEnum::Paid->value) {
                    InvoicePayment::create([
                        'invoice_id' => $invoice->id,
                        'amount'     => $total,
                        'paid_at'    => $dueDate->copy()->subDays(2)->toDateTimeString(),
                        'method'     => $methodCycle[($idx + $m) % count($methodCycle)],
                        'status'     => PaymentStatusEnum::Completed->value,
                        'reference'  => 'REF-' . strtoupper(Str::random(8)),
                        'notes'      => null,
                        'created_by' => null,
                    ]);
                } elseif ($statusValue === InvoiceStatusEnum::PartiallyPaid->value) {
                    $partial = (int) round($total * 0.5);
                    InvoicePayment::create([
                        'invoice_id' => $invoice->id,
                        'amount'     => $partial,
                        'paid_at'    => $dueDate->copy()->subDays(1)->toDateTimeString(),
                        'method'     => $methodCycle[$idx % count($methodCycle)],
                        'status'     => PaymentStatusEnum::Completed->value,
                        'reference'  => 'REF-' . strtoupper(Str::random(8)),
                        'notes'      => 'پرداخت جزئی',
                        'created_by' => null,
                    ]);
                }

                $invoiceCount++;
            }
        }

        echo "  ✓ Invoices:            " . DB::table('invoices')->count() . "\n";
        echo "  ✓ Invoice Payments:    " . DB::table('invoice_payments')->count() . "\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LEADS  (40)
    // ─────────────────────────────────────────────────────────────────────────

    private function seedLeads(): void
    {
        $adminUser = User::where('role', RoleEnum::ADMIN)->first();

        $leadData = [
            ['full_name' => 'امیر حسین نجفی',   'phone' => '09351000001', 'age' => 12,
             'source' => LeadSourceEnum::Instagram,   'status' => LeadStatusEnum::New,
             'priority' => LeadPriorityEnum::High,    'instrument_slug' => 'piano'],
            ['full_name' => 'سارا محمدی',        'phone' => '09351000002', 'age' => 25,
             'source' => LeadSourceEnum::Website,     'status' => LeadStatusEnum::Contacted,
             'priority' => LeadPriorityEnum::Medium,  'instrument_slug' => 'violin'],
            ['full_name' => 'علیرضا کرمی',       'phone' => '09351000003', 'age' => 35,
             'source' => LeadSourceEnum::Referral,    'status' => LeadStatusEnum::Interested,
             'priority' => LeadPriorityEnum::High,    'instrument_slug' => 'tar'],
            ['full_name' => 'نازنین رحیمی',      'phone' => '09351000004', 'age' => 16,
             'source' => LeadSourceEnum::Telegram,    'status' => LeadStatusEnum::TrialScheduled,
             'priority' => LeadPriorityEnum::High,    'instrument_slug' => 'classical-guitar'],
            ['full_name' => 'محمد حسینی',        'phone' => '09351000005', 'age' => 28,
             'source' => LeadSourceEnum::WalkIn,      'status' => LeadStatusEnum::Registered,
             'priority' => LeadPriorityEnum::Medium,  'instrument_slug' => 'drums'],
            ['full_name' => 'فریده امیری',       'phone' => '09351000006', 'age' => 40,
             'source' => LeadSourceEnum::Phone,       'status' => LeadStatusEnum::Lost,
             'priority' => LeadPriorityEnum::Low,     'instrument_slug' => 'voice'],
            ['full_name' => 'داود شکوهی',        'phone' => '09351000007', 'age' => 9,
             'source' => LeadSourceEnum::Instagram,   'status' => LeadStatusEnum::New,
             'priority' => LeadPriorityEnum::Medium,  'instrument_slug' => 'keyboard'],
            ['full_name' => 'پگاه بهاری',        'phone' => '09351000008', 'age' => 22,
             'source' => LeadSourceEnum::Website,     'status' => LeadStatusEnum::Contacted,
             'priority' => LeadPriorityEnum::Medium,  'instrument_slug' => 'flute'],
            ['full_name' => 'کاوه منصور',        'phone' => '09351000009', 'age' => 14,
             'source' => LeadSourceEnum::Referral,    'status' => LeadStatusEnum::Interested,
             'priority' => LeadPriorityEnum::High,    'instrument_slug' => 'saxophone'],
            ['full_name' => 'مینا جلالی',        'phone' => '09351000010', 'age' => 31,
             'source' => LeadSourceEnum::Telegram,    'status' => LeadStatusEnum::TrialScheduled,
             'priority' => LeadPriorityEnum::High,    'instrument_slug' => 'santoor'],
            ['full_name' => 'شهریار قربانی',     'phone' => '09351000011', 'age' => 19,
             'source' => LeadSourceEnum::WalkIn,      'status' => LeadStatusEnum::New,
             'priority' => LeadPriorityEnum::Low,     'instrument_slug' => 'setar'],
            ['full_name' => 'آذین سهرابی',       'phone' => '09351000012', 'age' => 8,
             'source' => LeadSourceEnum::Instagram,   'status' => LeadStatusEnum::Contacted,
             'priority' => LeadPriorityEnum::Medium,  'instrument_slug' => 'piano'],
            ['full_name' => 'بردیا طاهری',       'phone' => '09351000013', 'age' => 17,
             'source' => LeadSourceEnum::Phone,       'status' => LeadStatusEnum::Lost,
             'priority' => LeadPriorityEnum::Low,     'instrument_slug' => 'electric-guitar'],
            ['full_name' => 'ترانه مرادی',       'phone' => '09351000014', 'age' => 45,
             'source' => LeadSourceEnum::Referral,    'status' => LeadStatusEnum::Interested,
             'priority' => LeadPriorityEnum::Medium,  'instrument_slug' => 'voice'],
            ['full_name' => 'سهند ایمانی',       'phone' => '09351000015', 'age' => 11,
             'source' => LeadSourceEnum::Website,     'status' => LeadStatusEnum::New,
             'priority' => LeadPriorityEnum::High,    'instrument_slug' => 'ney'],
            ['full_name' => 'دلارام خدادادی',    'phone' => '09351000016', 'age' => 26,
             'source' => LeadSourceEnum::Instagram,   'status' => LeadStatusEnum::Registered,
             'priority' => LeadPriorityEnum::High,    'instrument_slug' => 'cello'],
            ['full_name' => 'آرشام کیانی',       'phone' => '09351000017', 'age' => 33,
             'source' => LeadSourceEnum::Telegram,    'status' => LeadStatusEnum::Contacted,
             'priority' => LeadPriorityEnum::Medium,  'instrument_slug' => 'oud'],
            ['full_name' => 'روژان صالحی',       'phone' => '09351000018', 'age' => 15,
             'source' => LeadSourceEnum::WalkIn,      'status' => LeadStatusEnum::TrialScheduled,
             'priority' => LeadPriorityEnum::High,    'instrument_slug' => 'daf'],
            ['full_name' => 'پارسا یوسفی',       'phone' => '09351000019', 'age' => 20,
             'source' => LeadSourceEnum::Referral,    'status' => LeadStatusEnum::New,
             'priority' => LeadPriorityEnum::Low,     'instrument_slug' => 'tonbak'],
            ['full_name' => 'آیسان نوروزی',      'phone' => '09351000020', 'age' => 38,
             'source' => LeadSourceEnum::Website,     'status' => LeadStatusEnum::Lost,
             'priority' => LeadPriorityEnum::Low,     'instrument_slug' => 'music-theory'],
            // 20 more for variety
            ['full_name' => 'کیان احمدی',        'phone' => '09351000021', 'age' => 10, 'source' => LeadSourceEnum::Instagram,   'status' => LeadStatusEnum::New,           'priority' => LeadPriorityEnum::Medium, 'instrument_slug' => 'piano'],
            ['full_name' => 'شبنم رضایی',        'phone' => '09351000022', 'age' => 24, 'source' => LeadSourceEnum::Website,     'status' => LeadStatusEnum::Interested,    'priority' => LeadPriorityEnum::High,   'instrument_slug' => 'violin'],
            ['full_name' => 'دانیار محمدی',      'phone' => '09351000023', 'age' => 29, 'source' => LeadSourceEnum::Telegram,    'status' => LeadStatusEnum::Contacted,     'priority' => LeadPriorityEnum::Medium, 'instrument_slug' => 'tar'],
            ['full_name' => 'آناهیتا کریمی',     'phone' => '09351000024', 'age' => 13, 'source' => LeadSourceEnum::Referral,    'status' => LeadStatusEnum::TrialScheduled,'priority' => LeadPriorityEnum::High,   'instrument_slug' => 'classical-guitar'],
            ['full_name' => 'پژمان اکبری',       'phone' => '09351000025', 'age' => 42, 'source' => LeadSourceEnum::WalkIn,      'status' => LeadStatusEnum::New,           'priority' => LeadPriorityEnum::Low,    'instrument_slug' => 'keyboard'],
            ['full_name' => 'هستی باقری',        'phone' => '09351000026', 'age' => 18, 'source' => LeadSourceEnum::Phone,       'status' => LeadStatusEnum::Registered,    'priority' => LeadPriorityEnum::High,   'instrument_slug' => 'flute'],
            ['full_name' => 'ارسلان توکلی',      'phone' => '09351000027', 'age' => 50, 'source' => LeadSourceEnum::Instagram,   'status' => LeadStatusEnum::Lost,          'priority' => LeadPriorityEnum::Low,    'instrument_slug' => 'drums'],
            ['full_name' => 'ندا حسینی',         'phone' => '09351000028', 'age' => 23, 'source' => LeadSourceEnum::Website,     'status' => LeadStatusEnum::Contacted,     'priority' => LeadPriorityEnum::Medium, 'instrument_slug' => 'saxophone'],
            ['full_name' => 'سروش منصوری',       'phone' => '09351000029', 'age' => 16, 'source' => LeadSourceEnum::Referral,    'status' => LeadStatusEnum::Interested,    'priority' => LeadPriorityEnum::High,   'instrument_slug' => 'santoor'],
            ['full_name' => 'ملیسا زارعی',       'phone' => '09351000030', 'age' => 27, 'source' => LeadSourceEnum::Telegram,    'status' => LeadStatusEnum::New,           'priority' => LeadPriorityEnum::Medium, 'instrument_slug' => 'voice'],
            ['full_name' => 'میلاد شاکری',       'phone' => '09351000031', 'age' => 11, 'source' => LeadSourceEnum::WalkIn,      'status' => LeadStatusEnum::TrialScheduled,'priority' => LeadPriorityEnum::High,   'instrument_slug' => 'setar'],
            ['full_name' => 'سانیا خان‌زاده',    'phone' => '09351000032', 'age' => 36, 'source' => LeadSourceEnum::Instagram,   'status' => LeadStatusEnum::Lost,          'priority' => LeadPriorityEnum::Low,    'instrument_slug' => 'piano'],
            ['full_name' => 'نوید قادری',        'phone' => '09351000033', 'age' => 21, 'source' => LeadSourceEnum::Phone,       'status' => LeadStatusEnum::New,           'priority' => LeadPriorityEnum::Medium, 'instrument_slug' => 'cello'],
            ['full_name' => 'ویانا نعمتی',       'phone' => '09351000034', 'age' => 14, 'source' => LeadSourceEnum::Website,     'status' => LeadStatusEnum::Contacted,     'priority' => LeadPriorityEnum::Medium, 'instrument_slug' => 'violin'],
            ['full_name' => 'رادین صمدی',        'phone' => '09351000035', 'age' => 30, 'source' => LeadSourceEnum::Referral,    'status' => LeadStatusEnum::Interested,    'priority' => LeadPriorityEnum::High,   'instrument_slug' => 'oud'],
            ['full_name' => 'آرتا منفرد',        'phone' => '09351000036', 'age' => 9,  'source' => LeadSourceEnum::Telegram,    'status' => LeadStatusEnum::New,           'priority' => LeadPriorityEnum::Low,    'instrument_slug' => 'keyboard'],
            ['full_name' => 'گیسو حیدری',        'phone' => '09351000037', 'age' => 44, 'source' => LeadSourceEnum::Instagram,   'status' => LeadStatusEnum::Registered,    'priority' => LeadPriorityEnum::High,   'instrument_slug' => 'music-theory'],
            ['full_name' => 'داریوش نبوی',       'phone' => '09351000038', 'age' => 17, 'source' => LeadSourceEnum::WalkIn,      'status' => LeadStatusEnum::TrialScheduled,'priority' => LeadPriorityEnum::High,   'instrument_slug' => 'electric-guitar'],
            ['full_name' => 'آناسیا علوی',       'phone' => '09351000039', 'age' => 33, 'source' => LeadSourceEnum::Phone,       'status' => LeadStatusEnum::Contacted,     'priority' => LeadPriorityEnum::Medium, 'instrument_slug' => 'daf'],
            ['full_name' => 'تارا رستمی',        'phone' => '09351000040', 'age' => 19, 'source' => LeadSourceEnum::Website,     'status' => LeadStatusEnum::New,           'priority' => LeadPriorityEnum::Low,    'instrument_slug' => 'ear-training'],
        ];

        $bySlug = $this->instruments->keyBy('slug');

        foreach ($leadData as $idx => $row) {
            Lead::firstOrCreate(
                ['phone' => $row['phone']],
                [
                    'full_name'               => $row['full_name'],
                    'age'                     => $row['age'],
                    'source'                  => $row['source'],
                    'status'                  => $row['status'],
                    'priority'                => $row['priority'],
                    'preferred_instrument_id' => $bySlug->get($row['instrument_slug'])?->id,
                    'assigned_to'             => $adminUser?->id,
                    'notes'                   => ($idx % 5 === 0) ? 'مشتری بالقوه مشتاق' : null,
                    'next_follow_up_at'       => in_array($row['status']->value, ['new', 'contacted', 'interested'])
                        ? $this->today->copy()->addDays(1 + ($idx % 7))->toDateTimeString()
                        : null,
                ]
            );
        }

        echo "  ✓ Leads:               " . Lead::count() . "\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NOTIFICATIONS
    // ─────────────────────────────────────────────────────────────────────────

    private function seedNotifications(): void
    {
        // Get target users: admins + teachers + students (sample of 30)
        $adminUsers   = User::whereIn('role', [RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value])->get();
        $teacherUsers = User::where('role', RoleEnum::TEACHER->value)->take(10)->get();
        $studentUsers = User::where('role', RoleEnum::STUDENT->value)->take(20)->get();

        $targets = $adminUsers->concat($teacherUsers)->concat($studentUsers);

        // Skip users who already have notifications
        $existing = DB::table('notifications')
            ->selectRaw('notifiable_id, notifiable_type')
            ->get()
            ->map(fn ($n) => "{$n->notifiable_type}-{$n->notifiable_id}")
            ->flip()
            ->all();

        $now  = now()->toDateTimeString();
        $rows = [];

        $templates = [
            [
                'type'    => 'session_reminder',
                'title'   => 'یادآوری جلسه',
                'body'    => 'جلسه موسیقی شما فردا برگزار می‌شود.',
                'read_at' => null,
            ],
            [
                'type'    => 'payment_due',
                'title'   => 'سررسید پرداخت',
                'body'    => 'شهریه این ماه شما هنوز پرداخت نشده است.',
                'read_at' => null,
            ],
            [
                'type'    => 'session_cancelled',
                'title'   => 'لغو جلسه',
                'body'    => 'جلسه امروز لغو شد.',
                'read_at' => $this->today->copy()->subDays(2)->toDateTimeString(),
            ],
            [
                'type'    => 'lead_follow_up',
                'title'   => 'پیگیری سرنخ',
                'body'    => 'پیگیری یک سرنخ جدید نیاز به توجه دارد.',
                'read_at' => $this->today->copy()->subDays(1)->toDateTimeString(),
            ],
            [
                'type'    => 'payment_received',
                'title'   => 'دریافت پرداخت',
                'body'    => 'پرداخت شهریه با موفقیت ثبت شد.',
                'read_at' => $this->today->copy()->subHours(3)->toDateTimeString(),
            ],
        ];

        foreach ($targets as $idx => $user) {
            $key = "App\\Models\\User-{$user->id}";
            if (isset($existing[$key])) {
                continue;
            }
            $existing[$key] = true;

            // Each user gets 2-3 notifications
            $count = 2 + ($idx % 2);
            for ($n = 0; $n < $count; $n++) {
                $tpl = $templates[($idx + $n) % count($templates)];

                $rows[] = [
                    'id'              => (string) Str::uuid(),
                    'type'            => 'App\\Notifications\\' . ucfirst(str_replace('_', '', $tpl['type'])) . 'Notification',
                    'notifiable_type' => 'App\\Models\\User',
                    'notifiable_id'   => $user->id,
                    'data'            => json_encode([
                        'title'   => $tpl['title'],
                        'body'    => $tpl['body'],
                        'icon'    => 'bell',
                    ]),
                    'read_at'    => $tpl['read_at'],
                    'created_at' => $this->today->copy()->subDays($idx % 10)->toDateTimeString(),
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('notifications')->insert($chunk);
        }

        echo "  ✓ Notifications:       " . DB::table('notifications')->count() . "\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INTEGRITY CHECK
    // ─────────────────────────────────────────────────────────────────────────

    private function runIntegrityCheck(): void
    {
        $teachersWithoutUser = Teacher::whereNull('user_id')->count();
        $studentsWithoutUser = Student::whereNull('user_id')->count();

        $duplicatePhones = (int) DB::table('users')
            ->groupBy('phone')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        $inactiveUsers = User::where('is_active', false)->count();

        echo "\n  Integrity Check\n";
        echo "  " . str_repeat('-', 30) . "\n";
        echo sprintf("  Teachers without User: %d\n",  $teachersWithoutUser);
        echo sprintf("  Students without User: %d\n",  $studentsWithoutUser);
        echo sprintf("  Duplicate Phones:      %d\n",  $duplicatePhones);
        echo sprintf("  Inactive Users:        %d\n",  $inactiveUsers);

        $errors = [];
        if ($teachersWithoutUser > 0) {
            $errors[] = "  ✗ {$teachersWithoutUser} teacher(s) have no linked User.";
        }
        if ($studentsWithoutUser > 0) {
            $errors[] = "  ✗ {$studentsWithoutUser} student(s) have no linked User.";
        }
        if ($duplicatePhones > 0) {
            $errors[] = "  ✗ {$duplicatePhones} duplicate phone(s) found in users.";
        }

        if (! empty($errors)) {
            echo "\n❌ Integrity FAILED:\n" . implode("\n", $errors) . "\n";
            throw new \RuntimeException("DemoSeeder integrity check failed:\n" . implode("\n", $errors));
        }

        echo "  ✓ All checks passed.\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SUMMARY
    // ─────────────────────────────────────────────────────────────────────────

    private function printSummary(): void
    {
        $todaySessions    = ClassSession::whereDate('session_date', $this->today)->count();
        $tomorrowSessions = ClassSession::whereDate('session_date', $this->today->copy()->addDay())->count();

        echo "\n🎉 Demo Seeding Complete!\n";
        echo str_repeat('━', 44) . "\n";
        echo sprintf("  Instruments:       %d\n", Instrument::count());
        echo sprintf("  Users:             %d\n", User::count());
        echo sprintf("  Teachers:          %d\n", Teacher::count());
        echo sprintf("  Students:          %d\n", Student::count());
        echo sprintf("  Enrollments:       %d\n", StudentEnrollment::count());
        echo sprintf("  Schedules:         %d\n", RecurringSchedule::count());
        echo sprintf("  Sessions:          %d\n", ClassSession::count());
        echo sprintf("  Attendance:        %d\n", ClassAttendance::count());
        echo sprintf("  Subscriptions:     %d\n", Subscription::count());
        echo sprintf("  Invoices:          %d\n", Invoice::count());
        echo sprintf("  Invoice Payments:  %d\n", DB::table('invoice_payments')->count());
        echo sprintf("  Leads:             %d\n", Lead::count());
        echo sprintf("  Notifications:     %d\n", DB::table('notifications')->count());
        echo str_repeat('─', 44) . "\n";
        echo sprintf("  Today sessions:    %d\n", $todaySessions);
        echo sprintf("  Tomorrow sessions: %d\n", $tomorrowSessions);
        echo sprintf("  Overdue invoices:  %d\n", Invoice::where('status', InvoiceStatusEnum::Overdue)->count());
        echo sprintf("  Overdue subs:      %d\n", Subscription::where('payment_status', 'overdue')->count());
        echo sprintf("  Active leads:      %d\n", Lead::whereNotIn('status', [LeadStatusEnum::Registered->value, LeadStatusEnum::Lost->value])->count());
        echo str_repeat('━', 44) . "\n\n";
    }
}
