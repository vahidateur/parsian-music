<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\DTOs\ListContext;
use App\DTOs\OperationalListData;
use App\Enums\RoleEnum;
use App\Models\Room;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/**
 * Shared Empty_State, Loading_State and Error_State contract across the six
 * owned Operational_List screens (teachers, students, rooms, instruments,
 * enrollments, invoices).
 *
 * **Validates: Requirements 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.8, 7.9, 7.10**
 */
class SharedStateContractTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => RoleEnum::ADMIN]);
    }

    /**
     * Requirement 7.2: no record at all + no filter → "no record exists" mode
     * with the authorized create entry point exposed.
     */
    public function test_empty_state_renders_no_records_mode_with_create_entry_point_when_the_list_is_unfiltered(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.teachers.index'));

        $response->assertOk();
        $response->assertSee('data-empty-state="no_records"', false);
        $response->assertSee('data-empty-create', false);
        $response->assertDontSee('data-empty-clear', false);
        $response->assertSee(__('admin.no_teachers_found'));
    }

    /**
     * Requirement 7.3: no record matches an applied filter/search → "no
     * matches" mode with the clear-filters control, no create entry point.
     */
    public function test_empty_state_renders_no_matches_mode_with_clear_filters_when_a_filter_is_applied(): void
    {
        Room::factory()->create(['name' => 'استودیو یک']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.rooms.index', ['search' => 'نامی-که-وجود-ندارد']));

        $response->assertOk();
        $response->assertSee('data-empty-state="no_matches"', false);
        $response->assertSee('data-empty-clear', false);
        $response->assertDontSee('data-empty-create', false);
        $response->assertSee(__('admin.no_results_for_query'));
    }

    /**
     * Requirement 7.2: the create entry point is omitted (not just hidden)
     * when the server-side policy denies `create` for the current actor, and
     * hiding it never replaces the server-side authorization check itself.
     */
    public function test_empty_state_create_entry_point_is_policy_gated(): void
    {
        $html = view('components.admin.list-empty', [
            'list' => $this->emptyListData(policyFlags: ['create' => false]),
            'route' => 'admin.teachers.index',
            'createRoute' => 'admin.teachers.create',
        ])->render();

        $this->assertStringContainsString('data-empty-state="no_records"', $html);
        $this->assertStringNotContainsString('data-empty-create', $html);

        $htmlWithCreate = view('components.admin.list-empty', [
            'list' => $this->emptyListData(policyFlags: ['create' => true]),
            'route' => 'admin.teachers.index',
            'createRoute' => 'admin.teachers.create',
        ])->render();

        $this->assertStringContainsString('data-empty-create', $htmlWithCreate);
    }

    /** @param array<string, bool> $policyFlags */
    private function emptyListData(array $policyFlags): OperationalListData
    {
        $context = new ListContext(
            entity: 'teachers',
            search: null,
            filters: [],
            sort: 'full_name',
            direction: 'asc',
            page: 1,
            per_page: 20,
            normalized_query: [],
            context_fingerprint: 'test',
        );

        return new OperationalListData(
            context: $context,
            paginator: new LengthAwarePaginator([], 0, 20, 1),
            rows: [],
            total: 0,
            filter_options: [],
            sortable: ['full_name'],
            default_sort: 'full_name',
            default_direction: 'asc',
            empty_mode: OperationalListData::EMPTY_MODE_NO_RECORDS,
            has_active_context: false,
            policy_flags: $policyFlags,
        );
    }

    /**
     * Requirement 7.1/7.4: the Loading_State markup contract is present on
     * the submit control of every owned Record_Form and wired to the shared
     * `pending` interaction state, not a per-page implementation.
     */
    public function test_loading_state_markup_and_pending_wiring_are_present_on_the_submit_control(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.teachers.create'));

        $response->assertOk();
        $response->assertSee('data-admin-form-state', false);
        $response->assertSee('data-admin-submit', false);
        $response->assertSee('x-bind:disabled="pending"', false);
        $response->assertSee('data-admin-loading', false);
        $response->assertSee('x-show="pending"', false);
        $response->assertSee('x-cloak', false);
        $response->assertSee(__('admin.state_submitting'));
    }

    /**
     * Requirement 7.6: Error_State renders a localized bounded message with a
     * retry path (defaulting to the current URL) and the failure marker.
     */
    public function test_error_state_renders_retry_path_and_failure_marker(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['error' => __('admin.payment_blocked')])
            ->get(route('admin.teachers.index'));

        $response->assertOk();
        $response->assertSee('data-error-state="failure"', false);
        $response->assertSee('data-error-retry', false);
        $response->assertSee(__('admin.state_error_retry'));
        $response->assertSee(__('admin.payment_blocked'));
    }

    /**
     * Requirement 7.6: Error_State renders a return path when the screen
     * passes one explicitly (Record_Form screens return to the index).
     */
    public function test_error_state_renders_return_path_when_a_return_url_is_configured(): void
    {
        $teacher = Teacher::factory()->create();

        $response = $this->actingAs($this->admin)
            ->withSession(['error' => __('admin.payment_blocked')])
            ->get(route('admin.teachers.edit', $teacher));

        $response->assertOk();
        $response->assertSee('data-error-return', false);
        $response->assertSee(__('admin.state_error_return'));
        $response->assertSee(route('admin.teachers.index'), false);
    }

    /**
     * Requirement 7.9: an unauthenticated request to an owned Operational_List
     * route never renders a misleading successful empty list — it redirects
     * to login.
     */
    public function test_unauthenticated_request_to_an_owned_list_route_is_redirected_to_login_not_a_misleading_empty_list(): void
    {
        foreach ($this->ownedListRoutes() as $routeName) {
            $this->get(route($routeName))->assertRedirect(route('login'));
        }
    }

    /**
     * Requirement 7.9: an authenticated actor lacking the `viewAny` ability
     * receives the documented 403, never a 200 with an empty list.
     */
    public function test_unauthorized_actor_receives_403_not_a_misleading_empty_list(): void
    {
        // Student persona holds none of the admin/teacher personas checked by
        // any owned list's viewAny ability.
        $student = User::factory()->create(['role' => RoleEnum::STUDENT]);

        foreach ($this->ownedListRoutes() as $routeName) {
            $response = $this->actingAs($student)->get(route($routeName));

            $response->assertForbidden();
            $response->assertDontSee('data-empty-state=', false);
            $response->assertDontSee('ui-empty-state', false);
        }
    }

    /** @return array<int, string> named routes of the six owned Operational_List screens. */
    private function ownedListRoutes(): array
    {
        return [
            'admin.teachers.index',
            'admin.students.index',
            'admin.rooms.index',
            'admin.instruments.index',
            'admin.enrollments.index',
            'admin.invoices.index',
        ];
    }
}
