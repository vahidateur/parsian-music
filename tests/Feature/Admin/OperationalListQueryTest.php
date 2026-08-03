<?php

namespace Tests\Feature\Admin;

use App\DTOs\OperationalListData;
use App\Enums\RoleEnum;
use App\Enums\TeacherStatusEnum;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Lists\EnrollmentListQuery;
use App\Services\Lists\InstrumentListQuery;
use App\Services\Lists\InvoiceListQuery;
use App\Services\Lists\LeadListQuery;
use App\Services\Lists\OperationalListQuery;
use App\Services\Lists\RoomListQuery;
use App\Services\Lists\SessionListQuery;
use App\Services\Lists\StudentListQuery;
use App\Services\Lists\TeacherListQuery;
use App\Services\Lists\UserListQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Contract of the Operational_List query layer and its list DTOs.
 *
 * Covers Requirements 5.1, 5.6, 5.12, 5.13, 5.14, 5.15, 16.1 and 16.2.
 */
class OperationalListQueryTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: class-string<OperationalListQuery>, 1: string}> */
    public static function listQueryProvider(): array
    {
        return [
            'teachers' => [TeacherListQuery::class, 'teachers'],
            'students' => [StudentListQuery::class, 'students'],
            'sessions' => [SessionListQuery::class, 'sessions'],
            'enrollments' => [EnrollmentListQuery::class, 'enrollments'],
            'rooms' => [RoomListQuery::class, 'rooms'],
            'instruments' => [InstrumentListQuery::class, 'instruments'],
            'invoices' => [InvoiceListQuery::class, 'invoices'],
            'leads' => [LeadListQuery::class, 'leads'],
            'users' => [UserListQuery::class, 'users'],
        ];
    }

    /**
     * @param class-string<OperationalListQuery> $queryClass
     */
    #[DataProvider('listQueryProvider')]
    public function test_every_operational_list_runs_with_the_contracted_page_size(string $queryClass, string $entity): void
    {
        /** @var OperationalListQuery $query */
        $query = app($queryClass);

        $data = $query->forInput([], $this->admin());

        $this->assertSame($entity, $data->context->entity);
        $this->assertSame(20, $data->context->per_page);
        $this->assertSame(20, $data->paginator->perPage());
        $this->assertContains($data->context->sort, $data->sortable);
        $this->assertSame(
            $data->total > 0 ? OperationalListData::EMPTY_MODE_NONE : OperationalListData::EMPTY_MODE_NO_RECORDS,
            $data->empty_mode,
            'A default List_Context must never report the filtered empty mode.'
        );
        $this->assertFalse($data->has_active_context);
    }

    public function test_relation_based_sort_keys_execute_as_bound_sub_selects(): void
    {
        $admin = $this->admin();

        $cases = [
            SessionListQuery::class => ['student_name', 'teacher_name', 'instrument_name'],
            EnrollmentListQuery::class => ['student_name', 'teacher_name', 'instrument_name'],
            InvoiceListQuery::class => ['student_name'],
        ];

        foreach ($cases as $queryClass => $sortKeys) {
            foreach ($sortKeys as $sortKey) {
                $data = app($queryClass)->forInput(['sort' => $sortKey], $admin);

                $this->assertSame($sortKey, $data->context->sort);
                $this->assertSame(0, $data->total);
            }
        }
    }

    public function test_rejected_sort_column_falls_back_to_the_definition_default(): void
    {
        $data = app(TeacherListQuery::class)->forInput([
            'sort' => 'full_name); drop table teachers;--',
            'direction' => 'sideways',
        ], $this->admin());

        $this->assertSame('full_name', $data->context->sort);
        $this->assertSame('asc', $data->context->direction);
    }

    public function test_search_and_filter_scope_the_result_and_expose_total_count(): void
    {
        Teacher::factory()->create(['full_name' => 'نازنین حسینی', 'status' => TeacherStatusEnum::Active]);
        Teacher::factory()->create(['full_name' => 'کامران رضایی', 'status' => TeacherStatusEnum::Inactive]);
        Teacher::factory()->create(['full_name' => 'سارا مرادی', 'status' => TeacherStatusEnum::Active]);

        $data = app(TeacherListQuery::class)->forInput([
            'search' => '  نازنین  ',
            'status' => 'active',
        ], $this->admin());

        $this->assertSame(1, $data->total);
        $this->assertCount(1, $data->rows);
        $this->assertSame('نازنین حسینی', $data->rows[0]->label);
        $this->assertSame('active', $data->rows[0]->status);
        $this->assertTrue($data->has_active_context);
        $this->assertSame('نازنین', $data->context->search);
    }

    public function test_unknown_filter_is_ignored_instead_of_scoping_the_query(): void
    {
        Teacher::factory()->count(2)->create();

        $data = app(TeacherListQuery::class)->forInput([
            'unknown_filter' => 'anything',
            'status' => 'not-a-status',
        ], $this->admin());

        $this->assertSame(2, $data->total);
        $this->assertSame([], $data->context->filters);
        $this->assertFalse($data->has_active_context);
    }

    public function test_pagination_covers_every_record_exactly_once_with_a_stable_tie_breaker(): void
    {
        Teacher::factory()->count(25)->create(['full_name' => 'هم‌نام']);

        $query = app(TeacherListQuery::class);
        $admin = $this->admin();

        $firstPage = $query->forInput(['page' => 1], $admin);
        $secondPage = $query->forInput(['page' => 2], $admin);

        $this->assertSame(25, $firstPage->total);
        $this->assertCount(20, $firstPage->rows);
        $this->assertCount(5, $secondPage->rows);

        $ids = array_merge(
            array_map(fn ($row) => $row->id, $firstPage->rows),
            array_map(fn ($row) => $row->id, $secondPage->rows),
        );

        $this->assertCount(25, array_unique($ids));
        $this->assertSame(
            $ids,
            array_merge(
                array_map(fn ($row) => $row->id, $query->forInput(['page' => 1], $admin)->rows),
                array_map(fn ($row) => $row->id, $query->forInput(['page' => 2], $admin)->rows),
            ),
            'Repeating the same List_Context must return the same order.'
        );
    }

    public function test_page_beyond_the_last_page_returns_an_empty_page_without_error(): void
    {
        Teacher::factory()->count(3)->create();

        $data = app(TeacherListQuery::class)->forInput(['page' => 99], $this->admin());

        $this->assertSame(3, $data->total);
        $this->assertSame([], $data->rows);
    }

    public function test_relations_are_eager_loaded_so_query_count_does_not_grow_with_rows(): void
    {
        $query = app(TeacherListQuery::class);
        $admin = $this->admin();

        Teacher::factory()->count(2)->create();
        $baseline = $this->countQueries(fn () => $query->forInput([], $admin));

        Teacher::factory()->count(15)->create();
        $grown = $this->countQueries(fn () => $query->forInput([], $admin));

        $this->assertSame($baseline, $grown);
    }

    public function test_pagination_links_carry_the_canonical_context(): void
    {
        Teacher::factory()->count(25)->create(['full_name' => 'کامران رضایی']);

        // Arabic kaf is submitted; the canonical Persian form must reach the link.
        $data = app(TeacherListQuery::class)->forInput([
            'search' => 'كامران',
            'direction' => 'desc',
        ], $this->admin());

        $nextPageUrl = urldecode($data->paginator->nextPageUrl() ?? '');

        $this->assertSame('کامران', $data->context->search);
        $this->assertStringContainsString('direction=desc', $nextPageUrl);
        $this->assertStringContainsString('search=کامران', $nextPageUrl);
    }

    public function test_row_actions_and_policy_flags_come_from_the_authorization_layer(): void
    {
        Teacher::factory()->create();

        $data = app(TeacherListQuery::class)->forInput([], $this->admin());

        $this->assertTrue($data->allows('create'));
        $this->assertTrue($data->rows[0]->allows('update'));
        $this->assertTrue($data->rows[0]->selectable);

        $guest = app(TeacherListQuery::class)->forInput([], null);

        $this->assertFalse($guest->allows('create'));
        $this->assertSame([], $guest->rows[0]->allowed_actions);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => RoleEnum::ADMIN]);
    }

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $callback();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
