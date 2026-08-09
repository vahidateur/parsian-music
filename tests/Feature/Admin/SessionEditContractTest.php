<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\DTOs\FilterContext;
use App\Http\Controllers\Admin\ClassSessionController;
use App\Http\Requests\Admin\SessionEditRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SessionEditContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_edit_and_update_routes_are_named_and_bound_to_the_controller(): void
    {
        $this->assertStringEndsWith('/admin/sessions/42/edit', route('admin.sessions.edit', 42));
        $this->assertStringEndsWith('/admin/sessions/42', route('admin.sessions.update', 42));

        $editRoute = app('router')->getRoutes()->getByName('admin.sessions.edit');
        $updateRoute = app('router')->getRoutes()->getByName('admin.sessions.update');

        $this->assertSame(ClassSessionController::class, $editRoute?->getControllerClass());
        $this->assertSame(ClassSessionController::class, $updateRoute?->getControllerClass());
        $this->assertSame(['PUT', 'PATCH'], $updateRoute?->methods());
    }

    public function test_session_edit_request_uses_the_canonical_filter_context_type(): void
    {
        $returnType = (new \ReflectionMethod(SessionEditRequest::class, 'returnContext'))->getReturnType();

        $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        $this->assertSame(FilterContext::class, $returnType->getName());
        $this->assertTrue($returnType->allowsNull());
    }

    public function test_session_edit_request_declares_editable_and_protected_fields(): void
    {
        $rules = SessionEditRequest::create('/admin/sessions/42', 'PUT')->rules();

        foreach (['student_id', 'teacher_id', 'instrument_id', 'session_date', 'start_time', 'duration_minutes', 'status', 'room', 'notes'] as $field) {
            $this->assertArrayHasKey($field, $rules);
        }

        foreach (['enrollment_id', 'session_fee', 'discount', 'recurring_schedule_id'] as $field) {
            $this->assertArrayHasKey($field, $rules);
            $this->assertContains('prohibited', $rules[$field]);
        }

        $this->assertArrayHasKey('updated_at', $rules);
        $this->assertArrayHasKey('return_context', $rules);
    }
}
