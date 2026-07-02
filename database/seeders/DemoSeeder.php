<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatusEnum;
use App\Enums\EnrollmentStatusEnum;
use App\Enums\SessionStatusEnum;
use App\Enums\SkillLevelEnum;
use App\Enums\StudentStatusEnum;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\RecurringSchedule;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks temporarily
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        echo "\n📊 Seeding Demo Database...\n";

        // 1. Seed Instruments (8)
        echo "🎼 Seeding Instruments...";
        $instruments = $this->seedInstruments();
        echo " ✓ ({$instruments->count()} records)\n";

        // 2. Seed Teachers (6)
        echo "👨‍🏫 Seeding Teachers...";
        $teachers = $this->seedTeachers($instruments);
        echo " ✓ ({$teachers->count()} records)\n";

        // 3. Seed Students (30)
        echo "👨‍🎓 Seeding Students...";
        $students = $this->seedStudents();
        echo " ✓ ({$students->count()} records)\n";

        // 4. Seed Enrollments (40)
        echo "📋 Seeding Enrollments...";
        $enrollments = $this->seedEnrollments($students, $teachers, $instruments);
        echo " ✓ ({$enrollments->count()} records)\n";

        // 5. Seed Recurring Schedules (35)
        echo "📅 Seeding Recurring Schedules...";
        $schedules = $this->seedRecurringSchedules($enrollments, $teachers);
        echo " ✓ ({$schedules->count()} records)\n";

        // 6. Seed Class Sessions (200)
        echo "🎓 Seeding Class Sessions...";
        $sessions = $this->seedClassSessions($schedules);
        echo " ✓ ({$sessions->count()} records)\n";

        // 7. Seed Attendance Records (~200)
        echo "✅ Seeding Attendance Records...";
        $attendance = $this->seedAttendance($sessions);
        echo " ✓ ({$attendance->count()} records)\n";

        \DB::statement('SET FOREIGN_KEY_CHECKS=1');

        echo "\n🎉 Demo Database Seeding Complete!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "Instruments:      " . Instrument::count() . "\n";
        echo "Teachers:         " . Teacher::count() . "\n";
        echo "Students:         " . Student::count() . "\n";
        echo "Enrollments:      " . StudentEnrollment::count() . "\n";
        echo "Schedules:        " . RecurringSchedule::count() . "\n";
        echo "Sessions:         " . ClassSession::count() . "\n";
        echo "Attendance:       " . ClassAttendance::count() . "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * Seed 8 instruments
     */
    private function seedInstruments()
    {
        $data = [
            ['name' => 'Piano', 'slug' => 'piano'],
            ['name' => 'Guitar', 'slug' => 'guitar'],
            ['name' => 'Violin', 'slug' => 'violin'],
            ['name' => 'Santoor', 'slug' => 'santoor'],
            ['name' => 'Tar', 'slug' => 'tar'],
            ['name' => 'Daf', 'slug' => 'daf'],
            ['name' => 'Drum', 'slug' => 'drum'],
            ['name' => 'Vocal', 'slug' => 'vocal'],
        ];

        return collect($data)->map(fn($item) => Instrument::create([
            ...$item,
            'is_active' => true,
        ]));
    }

    /**
     * Seed 6 teachers with 1-3 instruments each
     */
    private function seedTeachers($instruments)
    {
        $teacherData = [
            [
                'full_name' => 'محمدرضا کریمی',
                'phone' => '09121234567',
                'bio' => 'درس‌دهنده پیانو با 15 سال تجربه',
                'instruments' => [1], // Piano
            ],
            [
                'full_name' => 'فاطمه علیزاده',
                'phone' => '09129876543',
                'bio' => 'متخصص در گیتار کلاسیک و الکتریکی',
                'instruments' => [2], // Guitar
            ],
            [
                'full_name' => 'علی محمودی',
                'phone' => '09135551111',
                'bio' => 'ویولن‌نواز حرفه‌ای',
                'instruments' => [3], // Violin
            ],
            [
                'full_name' => 'نسرین رستمی',
                'phone' => '09147772222',
                'bio' => 'معلم موسیقی سنتی و سنطور',
                'instruments' => [4, 5], // Santoor, Tar
            ],
            [
                'full_name' => 'حمید اسلامی',
                'phone' => '09159993333',
                'bio' => 'درس‌دهنده درام و ضرب‌الاء',
                'instruments' => [6, 7], // Daf, Drum
            ],
            [
                'full_name' => 'مریم سعیدی',
                'phone' => '09168884444',
                'bio' => 'متخصص در موسیقی کلاسیکی و معاصر',
                'instruments' => [1, 8], // Piano, Vocal
            ],
        ];

        return collect($teacherData)->map(function ($data) {
            $instruments = $data['instruments'];
            unset($data['instruments']);

            $teacher = Teacher::create([
                ...$data,
                'teacher_code' => 'T' . str_pad(Teacher::count() + 1, 4, '0', STR_PAD_LEFT),
                'status' => 'active',
                'hire_date' => Carbon::now()->subMonths(rand(6, 60))->toDateString(),
            ]);

            // Attach instruments with skill_level
            foreach ($instruments as $index => $instrumentId) {
                $teacher->instruments()->attach($instrumentId, [
                    'skill_level' => fake()->randomElement(['intermediate', 'advanced', 'expert']),
                    'is_primary' => $index === 0, // First instrument is primary
                ]);
            }

            return $teacher;
        });
    }

    /**
     * Seed 30 realistic Persian students
     */
    private function seedStudents()
    {
        $names = [
            'علی احمدی', 'زهرا رحمانی', 'محمد حسنی', 'فاطمه کریمی',
            'سامان توکلی', 'نیلوفر موسوی', 'ایمان شریفی', 'مهناز رضایی',
            'هیربد جمشیدی', 'الهام صادقی', 'مسعود میرزایی', 'شیرین اکبری',
            'مهدی علیخانی', 'پریا رزاقی', 'کمال شاکری', 'آزاده باقری',
            'رضا حاجی‌زاده', 'دنیا سلطانی', 'آرین کاظمی', 'یاسمن شهبازی',
            'جواد مرادی', 'نرگس فرجام', 'درویش درویشی', 'سپیده هاشمی',
            'محسن بهرامی', 'لیلا رفاهی', 'صالح اسدی', 'زیبا شریفی',
            'بهزاد منصوری', 'ساره زارعی',
        ];

        return collect($names)->map(function ($name) {
            return Student::create([
                'full_name' => $name,
                'student_code' => 'S' . str_pad(Student::count() + 1, 5, '0', STR_PAD_LEFT),
                'phone' => '0912' . rand(10000000, 99999999),
                'parent_phone' => '0912' . rand(10000000, 99999999),
                'status' => fake()->randomElement(['active', 'active', 'paused']),
                'join_date' => Carbon::now()->subMonths(rand(1, 24))->toDateString(),
                'notes' => rand(1, 100) <= 30 ? fake()->sentence() : null,
            ]);
        });
    }

    /**
     * Seed 40 enrollments
     */
    private function seedEnrollments($students, $teachers, $instruments)
    {
        $enrollments = collect();

        // Distribute 40 enrollments across students
        // Most will be active to allow more schedules
        for ($i = 0; $i < 40; $i++) {
            $student = $students->random();
            $teacher = $teachers->random();
            $instrument = $teacher->instruments()->inRandomOrder()->first() ?? $instruments->first();

            $statusRnd = rand(1, 100);
            if ($statusRnd <= 85) {
                $status = 'active'; // 85% active
            } elseif ($statusRnd <= 95) {
                $status = 'completed';
            } else {
                $status = 'paused';
            }

            $endedAt = null;
            if ($status === 'completed') {
                $endedAt = Carbon::now()->subMonths(rand(1, 3))->toDateString();
            }

            $enrollments->push(StudentEnrollment::create([
                'student_id' => $student->id,
                'instrument_id' => $instrument->id,
                'teacher_id' => $teacher->id,
                'skill_level' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
                'status' => $status,
                'started_at' => Carbon::now()->subMonths(rand(1, 18))->toDateString(),
                'ended_at' => $endedAt,
                'notes' => rand(1, 100) <= 20 ? fake()->sentence() : null,
            ]));
        }

        return $enrollments;
    }

    /**
     * Seed 35+ recurring schedules with conflict avoidance
     */
    private function seedRecurringSchedules($enrollments, $teachers)
    {
        $schedules = collect();
        $activeEnrollments = $enrollments->where('status', 'active');

        $rooms = ['A101', 'A102', 'A103', 'B201', 'B202', 'B203', 'C301', 'C302'];
        $startTimes = ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00', '18:00'];

        // Create schedules for all active enrollments (up to 35)
        foreach ($activeEnrollments->take(35) as $enrollment) {
            $teacher = $enrollment->teacher;
            $weekday = rand(0, 6); // 0-6 (Sunday-Saturday)
            $startTime = fake()->randomElement($startTimes);
            $room = fake()->randomElement($rooms);
            $duration = fake()->randomElement([30, 45, 60, 90]);

            $schedules->push(RecurringSchedule::create([
                'enrollment_id' => $enrollment->id,
                'weekday' => $weekday,
                'start_time' => $startTime,
                'duration_minutes' => $duration,
                'room' => $room,
                'is_active' => true,
            ]));
        }

        return $schedules;
    }

    /**
     * Generate 8 weeks of class sessions (~200 rows)
     */
    private function seedClassSessions($schedules)
    {
        $sessions = collect();
        $today = Carbon::now();

        foreach ($schedules as $schedule) {
            $enrollment = $schedule->enrollment;

            // Generate sessions for 8 weeks ahead
            for ($week = 0; $week < 8; $week++) {
                $sessionDate = $today->copy()
                    ->addWeeks($week)
                    ->startOfWeek()
                    ->addDays($schedule->weekday);

                // Skip if enrollment has ended
                if ($enrollment->ended_at && $sessionDate->greaterThan($enrollment->ended_at)) {
                    continue;
                }

                // Skip if enrollment hasn't started yet
                if ($sessionDate->lessThan($enrollment->started_at)) {
                    continue;
                }

                // Distribute statuses: 70% completed, 15% scheduled, 10% cancelled, 5% missed
                $statusRnd = rand(1, 100);
                if ($statusRnd <= 70) {
                    $status = 'completed';
                } elseif ($statusRnd <= 85) {
                    $status = 'scheduled';
                } elseif ($statusRnd <= 95) {
                    $status = 'cancelled';
                } else {
                    $status = 'missed';
                }

                $sessions->push(ClassSession::create([
                    'enrollment_id' => $enrollment->id,
                    'recurring_schedule_id' => $schedule->id,
                    'session_date' => $sessionDate->toDateString(),
                    'start_time' => $schedule->start_time,
                    'duration_minutes' => $schedule->duration_minutes,
                    'status' => $status,
                    'room' => $schedule->room,
                    'notes' => rand(1, 100) <= 10 ? fake()->sentence() : null,
                ]));
            }
        }

        return $sessions;
    }

    /**
     * Seed attendance records for completed sessions only
     */
    private function seedAttendance($sessions)
    {
        $attendance = collect();
        $completedSessions = $sessions->where('status', 'completed');

        foreach ($completedSessions as $session) {
            // Distribution: 70% present, 15% absent, 10% late, 5% excused
            $statusRnd = rand(1, 100);
            if ($statusRnd <= 70) {
                $status = 'present';
            } elseif ($statusRnd <= 85) {
                $status = 'absent';
            } elseif ($statusRnd <= 95) {
                $status = 'late';
            } else {
                $status = 'excused';
            }

            $attendance->push(ClassAttendance::create([
                'class_session_id' => $session->id,
                'student_id' => $session->enrollment->student_id,
                'status' => $status,
                'note' => rand(1, 100) <= 20 ? fake()->sentence() : null,
                'marked_at' => Carbon::now(),
            ]));
        }

        return $attendance;
    }
}
