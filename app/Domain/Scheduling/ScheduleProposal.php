<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Enums\SessionStatusEnum;
use InvalidArgumentException;
use JsonSerializable;

/** Canonical immutable scheduling command with only permitted mutable intent. */
final readonly class ScheduleProposal implements JsonSerializable
{
    public function __construct(
        public int|string|null $sessionId,
        public ?SessionVersion $sessionVersion,
        public RelationPath $relationPath,
        public TimeRange $timeRange,
        public ?string $room,
        public SessionStatusEnum $status,
        public ?string $notes,
        public ProposalSource $source,
        public ?OverrideInstruction $override = null,
    ) {
        if ($sessionId === null && $sessionVersion !== null) {
            throw new InvalidArgumentException('Only an existing session may have a version token.');
        }

        if ($sessionId !== null && ! self::isStableId($sessionId)) {
            throw new InvalidArgumentException('A session identifier must be stable.');
        }

        foreach ([$room, $notes] as $value) {
            if ($value !== null && (trim($value) === '' || strip_tags($value) !== $value)) {
                throw new InvalidArgumentException('Scheduling text must be non-empty plain text when provided.');
            }
        }
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'session_id' => $this->sessionId,
            'session_version' => $this->sessionVersion?->value,
            'relation' => $this->relationPath,
            'time_range' => $this->timeRange,
            'room' => $this->room,
            'status' => $this->status->value,
            'notes' => $this->notes,
            'source' => $this->source->value,
            'override' => $this->override === null ? null : ['confirmed' => $this->override->confirmed, 'reason' => $this->override->reason],
        ];
    }

    private static function isStableId(int|string $id): bool
    {
        return (is_int($id) && $id > 0) || (is_string($id) && ctype_digit($id) && (int) $id > 0);
    }
}
