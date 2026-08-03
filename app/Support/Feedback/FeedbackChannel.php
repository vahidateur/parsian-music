<?php

declare(strict_types=1);

namespace App\Support\Feedback;

use Illuminate\Support\HtmlString;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

/**
 * Shared Feedback_Channel message contract.
 *
 * Single source of truth for the operational feedback the admin panel renders:
 * success flash, failure flash and field-level validation feedback. Every message
 * passes through the same normalization, sensitive-content guard and length bounds
 * before it reaches a view, so no screen can leak SQL text, a stack trace, a file
 * path, a credential/token or personal contact data.
 *
 * Requirements: 7.6, 7.7, 8.1, 8.2, 8.3, 8.4, 8.5
 */
final class FeedbackChannel
{
    /** Shortest renderable message (requirements 8.2, 8.3). */
    public const MIN_LENGTH = 10;

    /** Longest renderable success message (requirement 8.2). */
    public const SUCCESS_MAX_LENGTH = 160;

    /** Longest renderable failure message (requirement 8.3). */
    public const FAILURE_MAX_LENGTH = 200;

    /** Minimum visible time in milliseconds before a message may disappear (requirement 8.5). */
    public const MIN_VISIBLE_MS = 4000;

    /**
     * Content that must never reach a rendered message (requirement 7.7).
     *
     * @var list<string>
     */
    private const SENSITIVE_PATTERNS = [
        // SQL text
        '/\b(?:select\s+.+\s+from|insert\s+into|update\s+\S+\s+set|delete\s+from|drop\s+table|alter\s+table|truncate\s+table)\b/i',
        '/\bsqlstate\b|\bsql:\s|\bconnection\s+refused\b/i',
        // Stack traces and framework internals
        '/\bstack\s+trace\b|#\d+\s+\/|\.php(?::\d+)?\b|\bthrew\b|\w+(?:Exception|Error)\b|::\w+\(\)/',
        // Absolute filesystem paths
        '/(?:^|[\s"\'(])(?:[A-Za-z]:\\\\|\/)[A-Za-z0-9_.\-]{2,}(?:[\/\\\\][A-Za-z0-9_.\-]+)+/',
        // Credentials and tokens
        '/\b(?:bearer|api[_-]?key|access[_-]?token|secret|passwd|password)\b/i',
        // Opaque token material: a long run mixing letters and digits, or a long hex digest.
        '/\b(?=[A-Za-z0-9+\/=_-]{24,}\b)[A-Za-z0-9+\/=_-]*\d[A-Za-z0-9+\/=_-]*\b/',
        '/\b[0-9a-f]{32,}\b/i',
        // Personal contact data
        '/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/',
        '/(?<!\d)(?:0|\+?98)\d{9,10}(?!\d)/',
    ];

    /**
     * Normalize a success message: safe, localized and within 10–160 characters.
     */
    public static function success(mixed $message): ?string
    {
        return self::normalize($message, self::SUCCESS_MAX_LENGTH, __('admin.feedback_success_generic'));
    }

    /**
     * Normalize a failure message: safe, localized and within 10–200 characters.
     */
    public static function failure(mixed $message): ?string
    {
        return self::normalize($message, self::FAILURE_MAX_LENGTH, __('admin.feedback_failure_generic'));
    }

    /**
     * Normalize a field validation message: safe and bounded, never replaced by a
     * generic fallback because a validation message must stay field-specific.
     */
    public static function fieldMessage(string $field, mixed $errors): ?string
    {
        $bag = self::bag($errors);

        if ($bag === null || ! $bag->has($field)) {
            return null;
        }

        $message = self::collapse((string) $bag->first($field));

        if ($message === '') {
            return null;
        }

        if (self::containsSensitiveContent($message)) {
            return __('admin.feedback_validation_generic');
        }

        return self::truncate($message, self::FAILURE_MAX_LENGTH);
    }

    /**
     * Every validation message of the current request, normalized for rendering.
     *
     * @return list<string>
     */
    public static function validationSummary(mixed $errors): array
    {
        $bag = self::bag($errors);

        if ($bag === null || $bag->isEmpty()) {
            return [];
        }

        $messages = [];

        foreach ($bag->all() as $message) {
            $normalized = self::collapse((string) $message);

            if ($normalized === '') {
                continue;
            }

            $messages[] = self::containsSensitiveContent($normalized)
                ? __('admin.feedback_validation_generic')
                : self::truncate($normalized, self::FAILURE_MAX_LENGTH);
        }

        return array_values(array_unique($messages));
    }

    /**
     * Stable DOM id of a field error message, matching the existing
     * `{field}-error` convention of the shared form-field component.
     */
    public static function fieldErrorId(string $field): string
    {
        $slug = preg_replace('/[^A-Za-z0-9_-]+/', '_', $field) ?? $field;

        return trim($slug, '_') . '-error';
    }

    /**
     * Accessible wiring for an invalid control: `aria-invalid` plus the
     * `aria-describedby` pointer to the rendered field message (requirement 8.4).
     * Returns an empty string when the field has no error.
     */
    public static function fieldAttributes(string $field, mixed $errors = null): HtmlString
    {
        $bag = self::bag($errors ?? self::sharedErrors());

        if ($bag === null || ! $bag->has($field)) {
            return new HtmlString('');
        }

        return new HtmlString(sprintf(
            'aria-invalid="true" aria-describedby="%s"',
            e(self::fieldErrorId($field)),
        ));
    }

    public static function containsSensitiveContent(string $message): bool
    {
        foreach (self::SENSITIVE_PATTERNS as $pattern) {
            if (preg_match($pattern, $message) === 1) {
                return true;
            }
        }

        return false;
    }

    private static function normalize(mixed $message, int $maxLength, string $fallback): ?string
    {
        if ($message === null || $message === '' || (! is_string($message) && ! is_numeric($message))) {
            return null;
        }

        $normalized = self::collapse((string) $message);

        if ($normalized === '') {
            return null;
        }

        if (self::containsSensitiveContent($normalized)) {
            $normalized = self::collapse($fallback);
        }

        if (mb_strlen($normalized) < self::MIN_LENGTH) {
            $normalized = self::collapse($fallback);
        }

        return self::truncate($normalized, $maxLength);
    }

    private static function collapse(string $message): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $message));
    }

    private static function truncate(string $message, int $maxLength): string
    {
        if (mb_strlen($message) <= $maxLength) {
            return $message;
        }

        return rtrim(mb_substr($message, 0, $maxLength - 1)) . '…';
    }

    private static function bag(mixed $errors): ?MessageBag
    {
        if ($errors instanceof ViewErrorBag) {
            return $errors->getBag('default');
        }

        if ($errors instanceof MessageBag) {
            return $errors;
        }

        return null;
    }

    private static function sharedErrors(): mixed
    {
        return view()->shared('errors');
    }
}
