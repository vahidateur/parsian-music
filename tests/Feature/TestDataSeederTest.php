<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Test seeder contract: deterministic, idempotent and independent of
 * `DemoSeeder` / development data.
 *
 * Requirements: 1.7, 4.8, 4.10
 */
class TestDataSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tables owned by the seeder, in dependency order.
     *
     * @var list<string>
     */
    private const SEEDED_TABLES = [
        'users',
        'instruments',
        'rooms',
        'teachers',
        'students',
        'teacher_instruments',
        'student_enrollments',
        'class_sessions',
        'class_attendances',
        'invoices',
        'invoice_items',
        'invoice_payments',
        'leads',
    ];

    public function test_seeder_populates_every_owned_table(): void
    {
        $this->seed(TestDataSeeder::class);

        foreach (self::SEEDED_TABLES as $table) {
            $this->assertGreaterThan(
                0,
                DB::table($table)->count(),
                "Seeder produced no record for table [{$table}]."
            );
        }
    }

    public function test_second_run_produces_the_same_record_set(): void
    {
        $this->seed(TestDataSeeder::class);
        $first = $this->snapshot();

        // A duplicate-key failure would surface here as a QueryException.
        $this->seed(TestDataSeeder::class);
        $second = $this->snapshot();

        $this->assertSame($first, $second, 'Re-running the seeder changed the record set.');
    }

    public function test_repeated_runs_do_not_grow_the_record_count(): void
    {
        $this->seed(TestDataSeeder::class);
        $baseline = $this->counts();

        $this->seed(TestDataSeeder::class);
        $this->seed(TestDataSeeder::class);

        $this->assertSame($baseline, $this->counts(), 'Re-running the seeder grew the record count.');
    }

    public function test_seeded_values_are_deterministic_and_not_time_dependent(): void
    {
        $this->seed(TestDataSeeder::class);

        $student = DB::table('students')
            ->where('student_code', TestDataSeeder::KEY_PREFIX . '-student-active')
            ->first();

        $this->assertNotNull($student);
        $this->assertStringStartsWith(TestDataSeeder::ANCHOR_DATE, (string) $student->join_date);

        $invoice = DB::table('invoices')
            ->where('invoice_number', TestDataSeeder::KEY_PREFIX . '-invoice-issued')
            ->first();

        $this->assertNotNull($invoice);
        $this->assertEquals(3000000, $invoice->total);
    }

    public function test_seeder_is_independent_of_demo_and_development_data(): void
    {
        // Runs against an empty database without any other seeder.
        $this->seed(TestDataSeeder::class);

        $this->assertSame(
            0,
            DB::table('users')->where('phone', 'not like', '090000000%')->count(),
            'Seeder created accounts outside its own key range.'
        );

        $this->assertSame(
            0,
            DB::table('students')
                ->where('student_code', 'not like', TestDataSeeder::KEY_PREFIX . '%')
                ->count(),
            'Seeder created students outside its own key range.'
        );

        $source = file_get_contents(database_path('seeders/TestDataSeeder.php'));
        $this->assertIsString($source);
        $this->assertStringNotContainsString(
            'DemoSeeder::class',
            $source,
            'The test seeder must not call DemoSeeder.'
        );

        $reflection = new \ReflectionClass(DatabaseSeeder::class);
        $databaseSeederSource = file_get_contents((string) $reflection->getFileName());
        $this->assertIsString($databaseSeederSource);
        $this->assertStringNotContainsString(
            'TestDataSeeder',
            $databaseSeederSource,
            'The test seeder must not run as part of the default DatabaseSeeder.'
        );
    }

    /**
     * Every seeded row, keyed by table, excluding `updated_at` write noise.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function snapshot(): array
    {
        $snapshot = [];

        foreach (self::SEEDED_TABLES as $table) {
            $snapshot[$table] = DB::table($table)
                ->orderBy('id')
                ->get()
                ->map(function (object $row): array {
                    $attributes = (array) $row;
                    unset($attributes['updated_at'], $attributes['remember_token']);

                    return $attributes;
                })
                ->all();
        }

        return $snapshot;
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        $counts = [];

        foreach (self::SEEDED_TABLES as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        return $counts;
    }
}
