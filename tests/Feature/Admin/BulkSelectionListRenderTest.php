<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleEnum;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BulkSelectionListRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_list_integrates_bulk_components_and_one_row_control_per_selectable_row(): void
    {
        Teacher::factory()->count(2)->create();

        $response = $this->actingAs($this->admin())->get(route('admin.teachers.index'));
        $html = $response->getContent();

        $response->assertOk();
        $this->assertSame(1, substr_count($html, 'data-bulk-select-all'));
        $this->assertSame(2, substr_count($html, 'data-bulk-row'));
        $response->assertSee('data-bulk-selection-toolbar', false);
        $response->assertSee(route('admin.teachers.bulk.preview'), false);
        $response->assertSee(route('admin.teachers.bulk'), false);
        $response->assertSee('data-bulk-confirmation', false);
        $response->assertSee('ui-modal', false);
        $response->assertSee('data-bulk-result-summary', false);
    }

    public function test_student_list_preserves_bulk_context_and_localized_action_labels(): void
    {
        Student::factory()->create();

        $response = $this->actingAs($this->admin())->get(route('admin.students.index'));

        $response->assertOk();
        $response->assertSee('data-bulk-entity="student"', false);
        $response->assertSee(route('admin.students.bulk.preview'), false);
        $response->assertSee(route('admin.students.bulk'), false);
        $response->assertSee(__('admin.activate'), false);
        $response->assertSee(__('admin.deactivate'), false);
        $response->assertSee(__('admin.delete'), false);
        $response->assertSee('data-bulk-filter-context', false);
    }

    public function test_bulk_feedback_and_confirmation_contract_is_accessible(): void
    {
        Teacher::factory()->create();

        $response = $this->actingAs($this->admin())->get(route('admin.teachers.index'));

        $response->assertOk();
        $response->assertSee('type="checkbox"', false);
        $response->assertSee('data-bulk-live-result', false);
        $response->assertSee('aria-live="polite"', false);
        $response->assertSee('data-bulk-live-error', false);
        $response->assertSee('aria-live="assertive"', false);
        $response->assertSee('role="dialog"', false);
        $response->assertSee('x-trap.noscroll="show"', false);
        $response->assertSee('data-bulk-result-retry', false);
        $response->assertSee('data-bulk-result-recovery', false);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => RoleEnum::ADMIN]);
    }
}
