<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use DateTimeZone;
use InvalidArgumentException;
use JsonSerializable;

/** Immutable, validated effective academy constraints. */
final readonly class EffectiveSchedulingRules implements JsonSerializable
{
    /** @param list<int> $enabledWeekdays @param array<string, list<string>> $roomRequirements */
    public function __construct(
        public string $version,
        public string $source,
        public DateTimeZone $timezone,
        public array $enabledWeekdays,
        public int $openingMinute,
        public int $closingMinute,
        public int $minimumDuration,
        public int $maximumDuration,
        public int $dailySessionLimit,
        public int $consecutiveSessionLimit,
        public ?array $lunch,
        public int $teacherBufferBefore,
        public int $teacherBufferAfter,
        public array $roomRequirements = [],
    ) {
        $weekdays = array_values(array_unique($enabledWeekdays));
        sort($weekdays);
        if ($version === '' || $source === '' || $weekdays !== $enabledWeekdays || $weekdays === [] || array_filter($weekdays, static fn (int $day): bool => $day < 1 || $day > 7) !== [] || $openingMinute < 0 || $closingMinute > 1440 || $openingMinute >= $closingMinute || $minimumDuration < 1 || $minimumDuration > $maximumDuration || $dailySessionLimit < 1 || $consecutiveSessionLimit < 1 || $teacherBufferBefore < 0 || $teacherBufferAfter < 0) {
            throw new InvalidArgumentException('Effective scheduling rules are contradictory.');
        }
        if ($lunch !== null && (! isset($lunch['start'], $lunch['end']) || ! is_int($lunch['start']) || ! is_int($lunch['end']) || $lunch['start'] < $openingMinute || $lunch['end'] > $closingMinute || $lunch['start'] >= $lunch['end'])) {
            throw new InvalidArgumentException('The lunch interval is invalid.');
        }
    }

    public static function legacy(DateTimeZone $timezone, array $weekdays = [1, 2, 3, 4, 5, 6, 7], int $openingMinute = 0, int $closingMinute = 1440, string $version = 'legacy-v1', string $source = 'legacy_default'): self
    {
        return new self($version, $source, $timezone, $weekdays, $openingMinute, $closingMinute, 1, 1439, PHP_INT_MAX, PHP_INT_MAX, null, 0, 0);
    }

    /** @return list<string> */
    public function requiredRoomsFor(int|string $instrumentId): array { return $this->roomRequirements[(string) $instrumentId] ?? []; }
    public function requiresRoomFor(int|string $instrumentId): bool { return $this->requiredRoomsFor($instrumentId) !== []; }

    public function jsonSerialize(): array
    {
        return ['version' => $this->version, 'source' => $this->source, 'timezone' => $this->timezone->getName(), 'enabled_weekdays' => $this->enabledWeekdays, 'opening_minute' => $this->openingMinute, 'closing_minute' => $this->closingMinute, 'minimum_duration' => $this->minimumDuration, 'maximum_duration' => $this->maximumDuration, 'daily_session_limit' => $this->dailySessionLimit, 'consecutive_session_limit' => $this->consecutiveSessionLimit, 'lunch' => $this->lunch, 'teacher_buffer_before' => $this->teacherBufferBefore, 'teacher_buffer_after' => $this->teacherBufferAfter, 'room_requirements' => $this->roomRequirements];
    }
}
