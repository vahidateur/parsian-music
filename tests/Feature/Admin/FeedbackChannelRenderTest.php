<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\RoleEnum;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Support\Feedback\FeedbackChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rendered contract of the shared Feedback_Channel across admin screens.
 *
 * **Validates: Requirements 7.6, 7.7, 8.1, 8.2, 8.3, 8.4, 8.5, 8.11**
 */
class FeedbackChannelRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => RoleEnum::ADMIN]);
    }

    public function test_success_feedback_uses_a_status_role_with_a_dismiss_control(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['success' => __('admin.teacher_created_successfully')])
            ->get(route('admin.teachers.index'));

        $response->assertOk();
        $response->assertSee('role="status"', false);
        $response->assertSee('data-feedback="success"', false);
        $response->assertSee('data-feedback-min-visible-ms="4000"', false);
        $response->assertSee('class="ui-alert ui-alert--success admin-feedback"', false);
        $response->assertSee(__('admin.teacher_created_successfully'));
        $response->assertSee(sprintf('aria-label="%s"', __('admin.feedback_dismiss')), false);
    }

    public function test_failure_feedback_uses_an_alert_role_with_a_dismiss_control(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['error' => __('admin.instrument_in_use_error')])
            ->get(route('admin.students.index'));

        $response->assertOk();
        $response->assertSee('data-feedback="failure"', false);
        $response->assertSee('class="ui-alert ui-alert--danger admin-feedback"', false);
        $response->assertSee('role="alert"', false);
        $response->assertSee(__('admin.instrument_in_use_error'));
        $response->assertSee(sprintf('aria-label="%s"', __('admin.feedback_dismiss')), false);
    }

    public function test_localized_success_and_failure_render_the_entity_action_with_bounded_lengths(): void
    {
        $successMessage = __('admin.teacher_created_successfully');
        $failureMessage = __('admin.instrument_in_use_error');

        $response = $this->actingAs($this->admin)
            ->withSession(['success' => $successMessage, 'error' => $failureMessage])
            ->get(route('admin.teachers.index'));

        $response->assertOk();
        $response->assertSee($successMessage);
        $response->assertSee($failureMessage);
        $response->assertSee('role="status"', false);
        $response->assertSee('role="alert"', false);
        $this->assertSame(1, substr_count((string) $response->getContent(), 'data-feedback="success"'));
        $this->assertSame(1, substr_count((string) $response->getContent(), 'data-feedback="failure"'));
        $this->assertGreaterThanOrEqual(FeedbackChannel::MIN_LENGTH, mb_strlen($successMessage));
        $this->assertLessThanOrEqual(FeedbackChannel::SUCCESS_MAX_LENGTH, mb_strlen($successMessage));
        $this->assertGreaterThanOrEqual(FeedbackChannel::MIN_LENGTH, mb_strlen($failureMessage));
        $this->assertLessThanOrEqual(FeedbackChannel::FAILURE_MAX_LENGTH, mb_strlen($failureMessage));
        $response->assertDontSee('admin.teacher_created_successfully');
        $response->assertDontSee('admin.instrument_in_use_error');
    }

    public function test_success_message_stays_inside_the_success_length_bounds(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['success' => str_repeat('ن', 400)])
            ->get(route('admin.teachers.index'));

        $response->assertOk();
        $response->assertSee(str_repeat('ن', FeedbackChannel::SUCCESS_MAX_LENGTH - 1) . '…');
        $response->assertDontSee(str_repeat('ن', FeedbackChannel::SUCCESS_MAX_LENGTH + 1));
    }

    public function test_failure_message_stays_inside_the_failure_length_bounds(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['error' => str_repeat('ن', 400)])
            ->get(route('admin.teachers.index'));

        $response->assertOk();
        $response->assertSee(str_repeat('ن', FeedbackChannel::FAILURE_MAX_LENGTH - 1) . '…');
        $response->assertDontSee(str_repeat('ن', FeedbackChannel::FAILURE_MAX_LENGTH + 1));
    }

    public function test_short_message_is_replaced_by_the_localized_generic_message(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['error' => 'خطا'])
            ->get(route('admin.teachers.index'));

        $response->assertOk();
        $response->assertSee(__('admin.feedback_failure_generic'));
    }

    /**
     * Requirement 7.7: no SQL text, stack trace, path, token or personal contact
     * data may reach a rendered failure message.
     */
    public function test_failure_message_never_renders_sensitive_implementation_detail(): void
    {
        $leaks = [
            'SQLSTATE[23000]: select id from students where phone = 1',
            'Stack trace: #0 /var/www/parsian-music/app/Http/Controllers/Admin/StudentController.php(42)',
            'Could not write C:\\laragon\\www\\parsian-music\\storage\\logs\\laravel.log',
            'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9 rejected',
            'The reminder to student@example.com and 09121234567 was not delivered',
        ];

        foreach ($leaks as $leak) {
            $response = $this->actingAs($this->admin)
                ->withSession(['error' => $leak])
                ->get(route('admin.teachers.index'));

            $response->assertOk();
            $response->assertDontSee($leak);
            $response->assertSee(__('admin.feedback_failure_generic'));
        }
    }

    public function test_field_error_is_associated_with_the_invalid_control(): void
    {
        $form = $this->actingAs($this->admin)
            ->from(route('admin.teachers.create'))
            ->followingRedirects()
            ->post(route('admin.teachers.store'), ['full_name' => '', 'phone' => '']);

        $form->assertOk();
        $form->assertSee('name="full_name" aria-invalid="true" aria-describedby="full_name-error"', false);
        $form->assertSee('name="phone" aria-invalid="true" aria-describedby="phone-error"', false);
        $form->assertSee('id="full_name-error"', false);
        $form->assertSee('id="phone-error"', false);
        $form->assertSee('data-feedback="field"', false);
        $form->assertSee('data-feedback-field="full_name"', false);
    }

    public function test_valid_control_carries_no_invalid_state(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.teachers.create'));

        $response->assertOk();
        $response->assertDontSee('aria-invalid="true"', false);
        $response->assertDontSee('data-feedback="field"', false);
    }

    public function test_every_owned_admin_screen_renders_feedback_through_the_shared_channel(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->create();

        $screens = [
            route('admin.teachers.index'),
            route('admin.teachers.create'),
            route('admin.teachers.edit', $teacher),
            route('admin.teachers.show', $teacher),
            route('admin.students.index'),
            route('admin.students.create'),
            route('admin.students.edit', $student),
            route('admin.instruments.index'),
            route('admin.rooms.index'),
            route('admin.enrollments.index'),
            route('admin.leads.index'),
            route('admin.users.index'),
        ];

        foreach ($screens as $screen) {
            $response = $this->actingAs($this->admin)
                ->withSession(['success' => __('admin.student_updated_successfully')])
                ->get($screen);

            $response->assertOk();
            $response->assertSee('data-feedback="success"', false);
            $response->assertSee('role="status"', false);
            $response->assertSee(__('admin.student_updated_successfully'));
        }
    }

    public function test_channel_renders_no_hardcoded_flash_colour_or_inline_style(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['error' => __('admin.payment_blocked')])
            ->get(route('admin.teachers.index'));

        $response->assertOk();
        $response->assertDontSee('bg-emerald-500/10', false);
        $response->assertDontSee('bg-red-500/10', false);
        $response->assertDontSee('<div style=', false);
    }
}
