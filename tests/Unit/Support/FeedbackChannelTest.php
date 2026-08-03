<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Feedback\FeedbackChannel;
use Illuminate\Support\MessageBag;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Shared Feedback_Channel message contract.
 *
 * Requirements: 7.6, 7.7, 8.2, 8.3, 8.4, 8.5
 */
class FeedbackChannelTest extends TestCase
{
    public function test_success_message_keeps_a_localized_message_inside_the_length_bounds(): void
    {
        $message = FeedbackChannel::success('  استاد با موفقیت  ثبت شد. ');

        $this->assertSame('استاد با موفقیت ثبت شد.', $message);
        $this->assertGreaterThanOrEqual(FeedbackChannel::MIN_LENGTH, mb_strlen((string) $message));
        $this->assertLessThanOrEqual(FeedbackChannel::SUCCESS_MAX_LENGTH, mb_strlen((string) $message));
    }

    public function test_localized_success_and_failure_messages_keep_the_entity_action_and_bounds(): void
    {
        $successMessage = __('admin.teacher_created_successfully');
        $failureMessage = __('admin.instrument_in_use_error');

        $this->assertSame($successMessage, FeedbackChannel::success($successMessage));
        $this->assertSame($failureMessage, FeedbackChannel::failure($failureMessage));

        foreach ([
            [$successMessage, FeedbackChannel::SUCCESS_MAX_LENGTH],
            [$failureMessage, FeedbackChannel::FAILURE_MAX_LENGTH],
        ] as [$message, $maxLength]) {
            $this->assertGreaterThanOrEqual(FeedbackChannel::MIN_LENGTH, mb_strlen((string) $message));
            $this->assertLessThanOrEqual($maxLength, mb_strlen((string) $message));
            $this->assertStringNotContainsString('admin.', (string) $message);
        }
    }

    public function test_absent_message_produces_no_feedback(): void
    {
        $this->assertNull(FeedbackChannel::success(null));
        $this->assertNull(FeedbackChannel::success('   '));
        $this->assertNull(FeedbackChannel::failure(null));
        $this->assertNull(FeedbackChannel::failure(''));
    }

    public function test_too_short_message_falls_back_to_the_localized_generic_message(): void
    {
        $this->assertSame(__('admin.feedback_success_generic'), FeedbackChannel::success('شد.'));
        $this->assertSame(__('admin.feedback_failure_generic'), FeedbackChannel::failure('خطا'));
    }

    public function test_long_message_is_truncated_to_the_variant_bound(): void
    {
        $success = FeedbackChannel::success(str_repeat('ا', 400));
        $failure = FeedbackChannel::failure(str_repeat('ا', 400));

        $this->assertSame(FeedbackChannel::SUCCESS_MAX_LENGTH, mb_strlen((string) $success));
        $this->assertSame(FeedbackChannel::FAILURE_MAX_LENGTH, mb_strlen((string) $failure));
        $this->assertStringEndsWith('…', (string) $success);
        $this->assertStringEndsWith('…', (string) $failure);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function sensitiveMessages(): array
    {
        return [
            'sql text' => ['SQLSTATE[23000]: select id from students where phone = 1'],
            'sql mutation' => ['insert into class_sessions values (1, 2, 3) failed'],
            'stack trace' => ['Stack trace: #0 /var/www/app/Http/Controllers/Admin/StudentController.php(42)'],
            'exception class' => ['QueryException thrown while saving the record'],
            'windows path' => ['Could not write C:\\laragon\\www\\parsian-music\\storage\\logs\\laravel.log'],
            'unix path' => ['Failed to open /var/www/parsian-music/storage/framework/views/abc.php'],
            'token' => ['Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9 rejected'],
            'credential word' => ['The database password was rejected by the server'],
            'email pii' => ['The message could not be delivered to student@example.com'],
            'phone pii' => ['The reminder to 09121234567 could not be delivered'],
        ];
    }

    #[DataProvider('sensitiveMessages')]
    public function test_sensitive_content_never_reaches_a_rendered_message(string $raw): void
    {
        $this->assertTrue(FeedbackChannel::containsSensitiveContent($raw));
        $this->assertSame(__('admin.feedback_failure_generic'), FeedbackChannel::failure($raw));
        $this->assertSame(__('admin.feedback_success_generic'), FeedbackChannel::success($raw));
    }

    public function test_localized_operational_messages_are_not_treated_as_sensitive(): void
    {
        $messages = [
            'هنرجو با موفقیت ثبت شد.',
            'صورت‌حساب لغو شد. برای بازگشت به فهرست صورت‌حساب‌ها اقدام کنید.',
            'امکان حذف وجود ندارد: این ساز به استاد یا ثبت‌نامی اختصاص دارد.',
            '۱۲ کلاس با موفقیت تولید شد.',
            'this session overlaps an existing class session',
            __('admin.feedback_failure_generic'),
            __('admin.feedback_success_generic'),
            __('admin.feedback_validation_generic'),
        ];

        foreach ($messages as $message) {
            $this->assertFalse(
                FeedbackChannel::containsSensitiveContent($message),
                sprintf('Localized message "%s" must not be flagged as sensitive.', $message),
            );
        }
    }

    public function test_field_error_id_is_stable_and_dom_safe(): void
    {
        $this->assertSame('phone-error', FeedbackChannel::fieldErrorId('phone'));
        $this->assertSame('items_0_title-error', FeedbackChannel::fieldErrorId('items.0.title'));
    }

    public function test_field_attributes_wire_aria_invalid_and_describedby_only_for_invalid_fields(): void
    {
        $bag = new MessageBag(['phone' => ['شماره تلفن الزامی است.']]);

        $this->assertSame(
            'aria-invalid="true" aria-describedby="phone-error"',
            FeedbackChannel::fieldAttributes('phone', $bag)->toHtml(),
        );
        $this->assertSame('', FeedbackChannel::fieldAttributes('full_name', $bag)->toHtml());
    }

    public function test_field_message_stays_field_specific_and_bounded(): void
    {
        $bag = new MessageBag([
            'phone' => ['شماره تلفن الزامی است.'],
            'notes' => [str_repeat('ب', 400)],
            'email' => ['Delivery to student@example.com failed'],
        ]);

        $this->assertSame('شماره تلفن الزامی است.', FeedbackChannel::fieldMessage('phone', $bag));
        $this->assertSame(FeedbackChannel::FAILURE_MAX_LENGTH, mb_strlen((string) FeedbackChannel::fieldMessage('notes', $bag)));
        $this->assertSame(__('admin.feedback_validation_generic'), FeedbackChannel::fieldMessage('email', $bag));
        $this->assertNull(FeedbackChannel::fieldMessage('missing_field', $bag));
        $this->assertNull(FeedbackChannel::fieldMessage('phone', null));
    }

    public function test_validation_summary_is_deduplicated_and_sanitized(): void
    {
        $bag = new MessageBag([
            'phone' => ['شماره تلفن الزامی است.'],
            'parent_phone' => ['شماره تلفن الزامی است.'],
            'email' => ['/var/www/app/Models/User.php broke'],
        ]);

        $summary = FeedbackChannel::validationSummary($bag);

        $this->assertSame([
            'شماره تلفن الزامی است.',
            __('admin.feedback_validation_generic'),
        ], $summary);
        $this->assertSame([], FeedbackChannel::validationSummary(new MessageBag()));
        $this->assertSame([], FeedbackChannel::validationSummary(null));
    }

    public function test_minimum_visible_window_is_four_seconds(): void
    {
        $this->assertSame(4000, FeedbackChannel::MIN_VISIBLE_MS);
    }
}
