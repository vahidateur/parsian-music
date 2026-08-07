<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\DTOs\SessionDisplayData;
use App\DTOs\SessionEditResource;
use App\DTOs\SessionEditViewData;
use App\Enums\SessionStatusEnum;
use App\Models\ClassSession;
use App\Services\RelationPathResolver;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Converts trusted legacy representations and untrusted scalar payloads into
 * the one immutable scheduling command. This class never persists anything.
 */
final class ScheduleProposalNormalizer
{
    private const PROTECTED_FIELDS = [
        'enrollment_id',
        'enrollment',
        'session_fee',
        'discount',
        'invoice_id',
        'invoice_item_id',
        'payment_id',
        'subscription_id',
        'subscription_payment_id',
        'recurring_schedule_id',
        'recurrence_identity',
        'occurrence_identity',
        'recurrence_scope',
        'recurrence_id',
        'series_id',
        'teacher_code',
        'student_code',
        'business_code',
    ];

    private const ALLOWED_FIELDS = [
        'session_id',
        'session_version',
        'student_id',
        'teacher_id',
        'instrument_id',
        'session_date',
        'start_time',
        'duration_minutes',
        'status',
        'room',
        'notes',
        'source',
        'override',
    ];

    public function __construct(private readonly RelationPathResolver $relationPaths) {}

    /** @param array<string, mixed> $input */
    public function fromSession(array $input, ClassSession $session, DateTimeZone $timezone): ScheduleProposal
    {
        $errors = $this->shapeErrors($input);

        if (array_key_exists('session_id', $input)) {
            try {
                if ((string) $this->requiredId($input['session_id'], 'session_id') !== (string) $session->getKey()) {
                    $errors['session_id'] = 'session_mismatch';
                }
            } catch (SchedulingValidationException $exception) {
                $errors = [...$errors, ...$exception->errors];
            }
        }

        if ($errors !== []) {
            throw SchedulingValidationException::with($errors);
        }

        $input['session_id'] = $session->getKey();
        $input['session_version'] ??= $session->updated_at?->toISOString();

        return $this->normalize($input, RelationPath::fromResolved($this->relationPaths->resolve($session)), $timezone);
    }

    /** @param array<string, mixed> $input */
    public function normalize(array $input, RelationPath $currentPath, DateTimeZone $timezone): ScheduleProposal
    {
        $errors = $this->shapeErrors($input);
        if ($errors !== []) {
            throw SchedulingValidationException::with($errors);
        }

        $relationPath = $this->proposalPath($input, $currentPath);
        $sessionId = $this->optionalId($input, 'session_id');
        $version = $this->version($input, $errors);
        if ($sessionId !== null && $version === null && ! array_key_exists('session_version', $errors)) {
            $errors['session_version'] = 'required';
        }

        $duration = $this->positiveInteger($input, 'duration_minutes', $errors);
        $date = $this->requiredString($input, 'session_date', $errors);
        $time = $this->requiredString($input, 'start_time', $errors);
        $status = $this->status($input, $errors);
        $source = $this->source($input, $errors);
        $room = $this->plainNullableString($input, 'room', $errors);
        $notes = $this->plainNullableString($input, 'notes', $errors);
        $override = $this->override($input, $errors);
        if ($errors !== []) {
            throw SchedulingValidationException::with($errors);
        }

        try {
            $range = TimeRange::fromLocal($date, $time, $duration, $timezone);
        } catch (InvalidArgumentException) {
            throw SchedulingValidationException::with([
                'session_date' => 'invalid_range',
                'start_time' => 'invalid_range',
                'duration_minutes' => 'invalid_range',
            ]);
        }

        return new ScheduleProposal($sessionId, $version, $relationPath, $range, $room, $status, $notes, $source, $override);
    }

    public function fromSessionEditResource(SessionEditResource $resource, DateTimeZone $timezone): ScheduleProposal
    {
        $input = $resource->toArray();
        unset($input['relation'], $input['protected_fields'], $input['room_resolution'], $input['room_id'], $input['updated_at']);
        $input['session_version'] = $resource->updated_at;
        $input['source'] = ProposalSource::Legacy->value;

        return $this->normalize($input, RelationPath::fromArray($resource->relation), $timezone);
    }

    public function fromSessionEditViewData(SessionEditViewData $view, DateTimeZone $timezone): ScheduleProposal
    {
        $input = $view->values;
        $input['session_id'] = $view->session_id;
        $input['session_version'] = $input['updated_at'] ?? null;
        unset($input['updated_at']);
        $input['source'] = ProposalSource::Legacy->value;
        $relation = $view->relation_options['relation'] ?? [];

        return $this->normalize($input, RelationPath::fromArray(is_array($relation) ? $relation : []), $timezone);
    }

    public function fromSessionDisplayData(SessionDisplayData $display, RelationPath $relationPath, SessionVersion $version, DateTimeZone $timezone): ScheduleProposal
    {
        return $this->normalize([
            'session_id' => $display->id,
            'session_version' => $version->value,
            'session_date' => $display->session_date,
            'start_time' => $display->start_time,
            'duration_minutes' => $display->duration_minutes,
            'status' => $display->status->value,
            'room' => $display->room,
            'notes' => null,
            'source' => ProposalSource::Legacy->value,
        ], $relationPath, $timezone);
    }

    /** @param array<string, mixed> $input @return array<string, string> */
    private function shapeErrors(array $input): array
    {
        $errors = [];
        foreach (array_keys($input) as $field) {
            if (! is_string($field)) {
                $errors['payload'] = 'invalid_field';
                continue;
            }

            if (in_array($field, self::PROTECTED_FIELDS, true)) {
                $errors[$field] = 'protected_field';
            } elseif (! in_array($field, self::ALLOWED_FIELDS, true)) {
                $errors[$field] = 'unexpected_field';
            }
        }

        return $errors;
    }

    /** @param array<string, mixed> $input */
    private function proposalPath(array $input, RelationPath $currentPath): RelationPath
    {
        $ids = [];
        foreach (['student_id' => $currentPath->studentId, 'teacher_id' => $currentPath->teacherId, 'instrument_id' => $currentPath->instrumentId] as $field => $current) {
            $ids[$field] = $this->requiredId($input[$field] ?? $current, $field);
        }

        if ($currentPath->type === RelationPathType::Enrollment) {
            if (! $currentPath->hasTuple($ids['student_id'], $ids['teacher_id'], $ids['instrument_id'])) {
                throw SchedulingValidationException::with([
                    'student_id' => 'relation_conflict',
                    'teacher_id' => 'relation_conflict',
                    'instrument_id' => 'relation_conflict',
                ]);
            }

            return $currentPath;
        }

        return new RelationPath(RelationPathType::Direct, null, $ids['student_id'], $ids['teacher_id'], $ids['instrument_id']);
    }

    /** @param array<string, mixed> $input @param array<string, string> $errors */
    private function version(array $input, array &$errors): ?SessionVersion
    {
        try {
            return SessionVersion::fromNullable($input['session_version'] ?? null);
        } catch (InvalidArgumentException) {
            $errors['session_version'] = 'invalid';

            return null;
        }
    }

    /** @param array<string, mixed> $input @param array<string, string> $errors */
    private function positiveInteger(array $input, string $field, array &$errors): int
    {
        $value = $input[$field] ?? null;
        if ((is_int($value) && $value > 0) || (is_string($value) && ctype_digit($value) && (int) $value > 0)) {
            return (int) $value;
        }

        $errors[$field] = 'invalid';

        return 0;
    }

    /** @param array<string, mixed> $input @param array<string, string> $errors */
    private function requiredString(array $input, string $field, array &$errors): string
    {
        $value = $input[$field] ?? null;
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        $errors[$field] = 'required';

        return '';
    }

    /** @param array<string, mixed> $input @param array<string, string> $errors */
    private function status(array $input, array &$errors): SessionStatusEnum
    {
        $value = $input['status'] ?? null;
        $status = $value instanceof SessionStatusEnum ? $value : (is_string($value) ? SessionStatusEnum::tryFrom($value) : null);
        if ($status instanceof SessionStatusEnum) {
            return $status;
        }

        $errors['status'] = 'invalid';

        return SessionStatusEnum::Scheduled;
    }

    /** @param array<string, mixed> $input @param array<string, string> $errors */
    private function source(array $input, array &$errors): ProposalSource
    {
        $value = $input['source'] ?? ProposalSource::Form->value;
        $source = $value instanceof ProposalSource ? $value : (is_string($value) ? ProposalSource::tryFrom($value) : null);
        if ($source instanceof ProposalSource) {
            return $source;
        }

        $errors['source'] = 'invalid';

        return ProposalSource::Form;
    }

    /** @param array<string, mixed> $input @param array<string, string> $errors */
    private function plainNullableString(array $input, string $field, array &$errors): ?string
    {
        $value = $input[$field] ?? null;
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        if (is_string($value) && strip_tags($value) === $value) {
            return trim($value);
        }

        $errors[$field] = 'invalid';

        return null;
    }

    /** @param array<string, mixed> $input */
    private function optionalId(array $input, string $field): int|string|null
    {
        if (! array_key_exists($field, $input) || $input[$field] === null || $input[$field] === '') {
            return null;
        }

        return $this->requiredId($input[$field], $field);
    }

    private function requiredId(mixed $value, string $field): int|string
    {
        if ((is_int($value) && $value > 0) || (is_string($value) && ctype_digit($value) && (int) $value > 0)) {
            return $value;
        }

        throw SchedulingValidationException::with([$field => 'invalid']);
    }

    /** @param array<string, mixed> $input @param array<string, string> $errors */
    private function override(array $input, array &$errors): ?OverrideInstruction
    {
        if (! array_key_exists('override', $input) || $input['override'] === null) {
            return null;
        }

        $value = $input['override'];
        if (! is_array($value) || array_diff(array_keys($value), ['confirmed', 'reason']) !== []) {
            $errors['override'] = 'invalid';

            return null;
        }

        $confirmed = $value['confirmed'] ?? null;
        $reason = $value['reason'] ?? null;
        if (! is_bool($confirmed) || ! is_string($reason)) {
            $errors['override'] = 'invalid';

            return null;
        }

        try {
            return new OverrideInstruction($confirmed, $reason);
        } catch (InvalidArgumentException) {
            $errors['override'] = 'invalid';

            return null;
        }
    }
}
