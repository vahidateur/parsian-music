<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleEnum;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Lists\StudentListQuery;
use App\Services\Lists\TeacherListQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BulkRowListQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_list_exposes_policy_derived_bulk_rows_and_signed_context(): void
    {
        Teacher::factory()->create(['full_name' => 'Bulk Teacher']);

        $list = app(TeacherListQuery::class)->forInput(['page' => 1], $this->admin());
        $row = $list->bulk_rows[0];

        $this->assertSame('teacher', $row->entity_key);
        $this->assertSame(Teacher::query()->first()->id, $row->id);
        $this->assertSame(['activate', 'deactivate', 'delete'], $row->allowed_actions);
        $this->assertTrue($row->selectable);
        $this->assertNotNull($list->selection_context);
        $this->assertArrayNotHasKey('page', $list->selection_context->toArray());
    }

    public function test_student_list_exposes_the_same_policy_derived_bulk_contract(): void
    {
        Student::factory()->create(['full_name' => 'Bulk Student']);

        $list = app(StudentListQuery::class)->forInput([], $this->admin());
        $row = $list->bulk_rows[0];

        $this->assertSame('student', $row->entity_key);
        $this->assertSame(Student::query()->first()->id, $row->id);
        $this->assertSame(['activate', 'deactivate', 'delete'], $row->allowed_actions);
        $this->assertTrue($row->selectable);
        $this->assertSame('Bulk Student', $row->display_data['label']);
    }

    public function test_unauthorized_rows_are_not_selectable_and_list_context_is_preserved(): void
    {
        Teacher::factory()->create(['full_name' => 'Bulk Teacher']);

        $list = app(TeacherListQuery::class)->forInput(['page' => 1, 'sort' => 'created_at'], null);

        $this->assertFalse($list->bulk_rows[0]->selectable);
        $this->assertSame([], $list->bulk_rows[0]->allowed_actions);
        $this->assertSame(1, $list->context->page);
        $this->assertSame('created_at', $list->context->sort);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => RoleEnum::ADMIN]);
    }
}
