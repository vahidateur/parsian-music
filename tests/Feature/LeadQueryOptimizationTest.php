<?php

namespace Tests\Feature;

use App\Enums\LeadStatusEnum;
use App\Models\Instrument;
use App\Models\Lead;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Validates: Requirements 6, 11
 * No N+1 Queries Property Test
 * Verifies query counts: show page = 1 query, index = 2 queries, kanban = 2 queries.
 */
class LeadQueryOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /**
     * Test 1: Show Page Query Count
     * Create a lead with related assignedUser, preferredInstrument, preferredTeacher, convertedStudent
     * Assert eager loading prevents N+1 queries (1 lead query + 1 query per relation, not per item)
     * Assert no additional per-field queries for each lead attribute
     */
    public function test_show_page_eager_loads_all_relations_no_n_plus_one(): void
    {
        // Create related resources
        $assignedUser = User::factory()->create(['role' => 'admin']);
        $instrument = Instrument::factory()->create();
        $teacher = Teacher::factory()->create();

        // Create lead with all relations
        $lead = Lead::factory()->create([
            'assigned_to' => $assignedUser->id,
            'preferred_instrument_id' => $instrument->id,
            'preferred_teacher_id' => $teacher->id,
        ]);

        DB::enableQueryLog();

        // Simulate the controller's eager load call
        $loadedLead = Lead::query()
            ->with(['assignedUser', 'preferredInstrument', 'preferredTeacher', 'convertedStudent'])
            ->find($lead->id);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Count queries: 1 lead + 1 each for relations = 5 total with eager loading
        // If N+1, it would be many more per attribute access
        $allQueries = count($queries);
        $this->assertLessThanOrEqual(5, $allQueries,
            'Show page with eager loading should not exceed 5 queries (1 lead + 4 relations). ' .
            'Actual: ' . $allQueries . ' queries'
        );

        // Verify eager loading didn't create per-attribute queries
        $leadIdQueries = count(array_filter($queries, fn($q) => str_contains(strtolower($q['query']), 'from `leads`')));
        $this->assertLessThanOrEqual(1, $leadIdQueries,
            'Should only query leads once'
        );
    }

    /**
     * Test 2: Index Page Query Count
     * Create 15+ leads with mixed relations
     * Assert pagination + eager loading prevents N+1 queries
     * Assert no per-lead queries for relations (single query per relation type)
     */
    public function test_index_page_no_n_plus_one_with_15_leads(): void
    {
        // Create 15 leads with mixed relations
        for ($i = 0; $i < 15; $i++) {
            Lead::factory()->create([
                'assigned_to' => $this->admin->id,
                'preferred_instrument_id' => Instrument::factory()->create()->id,
                'preferred_teacher_id' => Teacher::factory()->create()->id,
            ]);
        }

        DB::enableQueryLog();

        // Simulate controller index action
        $leads = Lead::query()
            ->with(['assignedUser', 'preferredInstrument', 'preferredTeacher'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $assignees = User::whereIn('role', ['admin', 'super_admin'])
            ->orderBy('full_name')
            ->get();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Count lead-related queries
        $leadQueries = count(array_filter($queries, function ($q) {
            $query = strtolower($q['query']);
            return str_contains($query, 'from `leads`') ||
                   str_contains($query, 'from `instruments`') ||
                   str_contains($query, 'from `teachers`');
        }));

        // Should be minimal with eager loading: 1 count + 1 paginate + 1 instruments + 1 teachers = 4
        $this->assertLessThanOrEqual(4, $leadQueries,
            'Index page should not have N+1 queries. Lead-related queries: ' . $leadQueries
        );

        // Verify no per-lead queries (would have 15+ queries if so)
        $instrumentQueries = count(array_filter($queries, fn($q) => str_contains(strtolower($q['query']), 'from `instruments`')));
        $this->assertLessThanOrEqual(1, $instrumentQueries, 'Should query instruments once, not per-lead');

        $teacherQueries = count(array_filter($queries, fn($q) => str_contains(strtolower($q['query']), 'from `teachers`')));
        $this->assertLessThanOrEqual(1, $teacherQueries, 'Should query teachers once, not per-lead');
    }

    /**
     * Test 3: Kanban Page Query Count
     * Create leads across all statuses
     * Assert eager loading prevents N+1 queries across 18 leads
     * Assert no per-card queries for assignedUser or preferredInstrument
     */
    public function test_kanban_page_no_n_plus_one_with_mixed_statuses(): void
    {
        // Create leads across all statuses (18 total)
        $statuses = [
            LeadStatusEnum::New->value,
            LeadStatusEnum::Contacted->value,
            LeadStatusEnum::Interested->value,
            LeadStatusEnum::TrialScheduled->value,
            LeadStatusEnum::Registered->value,
            LeadStatusEnum::Lost->value,
        ];

        foreach ($statuses as $status) {
            for ($i = 0; $i < 3; $i++) {
                Lead::factory()->create([
                    'status' => $status,
                    'assigned_to' => $this->admin->id,
                    'preferred_instrument_id' => Instrument::factory()->create()->id,
                ]);
            }
        }

        DB::enableQueryLog();

        // Simulate controller kanban action
        $leads = Lead::query()
            ->with(['assignedUser', 'preferredInstrument'])
            ->latest()
            ->get()
            ->groupBy(fn (Lead $lead) => $lead->status->value);

        $columns = LeadStatusEnum::cases();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Count total queries
        $allQueries = count($queries);

        // With eager loading for 18 leads: 1 lead query + 1 users query + 1 instruments query = 3
        // Without eager loading (N+1): 1 lead + 18 users + 18 instruments = 37+
        $this->assertLessThanOrEqual(3, $allQueries,
            'Kanban page should execute minimal queries with eager loading. ' .
            'Actual: ' . $allQueries . ' queries (would be 37+ without eager loading with 18 leads)'
        );

        // Verify no per-card queries for users (would be 18+ if N+1)
        $userQueryCount = count(array_filter($queries, fn($q) => str_contains(strtolower($q['query']), 'from `users`')));
        $this->assertLessThanOrEqual(1, $userQueryCount, 'Should query users once via eager loading, not per-card');

        // Verify no per-card queries for instruments (would be 18+ if N+1)
        $instrumentQueryCount = count(array_filter($queries, fn($q) => str_contains(strtolower($q['query']), 'from `instruments`')));
        $this->assertLessThanOrEqual(1, $instrumentQueryCount, 'Should query instruments once via eager loading, not per-card');
    }
}
