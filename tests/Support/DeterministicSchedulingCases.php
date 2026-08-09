<?php

declare(strict_types=1);

namespace Tests\Support;

use InvalidArgumentException;

/** Deterministic, test-only fixtures for future scheduling-domain properties. */
final class DeterministicSchedulingCases
{
    public const DEFAULT_SEED = 20260714;

    public const MINIMUM_CASES = 100;

    /** @return list<array<string, int|string>> */
    public static function intervals(int $seed = self::DEFAULT_SEED, int $count = self::MINIMUM_CASES): array
    {
        $state = self::state($seed);

        return self::build($seed, $count, 'interval', static function (int $case) use (&$state): array {
            $start = self::next($state, 1200);
            $duration = 15 * (1 + self::next($state, 8));

            return [
                'start_minute' => $start,
                'duration_minutes' => $duration,
                'end_minute' => $start + $duration,
                'adjacent_start_minute' => $start + $duration,
                'overlap_start_minute' => $start + max(1, intdiv($duration, 2)),
            ];
        });
    }

    /** @return list<array<string, int|string|array<string, int>>> */
    public static function relationPaths(int $seed = self::DEFAULT_SEED, int $count = self::MINIMUM_CASES): array
    {
        $state = self::state($seed + 11);

        return self::build($seed, $count, 'relation-path', static function (int $case) use (&$state): array {
            $mode = ['direct', 'enrollment', 'mixed'][self::next($state, 3)];
            $relations = [
                'student_id' => 1 + self::next($state, 50),
                'teacher_id' => 1 + self::next($state, 50),
                'instrument_id' => 1 + self::next($state, 12),
            ];

            return [
                'path' => $mode,
                'enrollment_id' => $mode === 'direct' ? 0 : 1 + self::next($state, 50),
                'relations' => $relations,
                'valid' => $mode !== 'mixed' ? 1 : 0,
            ];
        });
    }

    /** @return list<array<string, int|string|array<int, int>>> */
    public static function rules(int $seed = self::DEFAULT_SEED, int $count = self::MINIMUM_CASES): array
    {
        $state = self::state($seed + 23);

        return self::build($seed, $count, 'rule', static function (int $case) use (&$state): array {
            $opening = 8 * 60 + self::next($state, 120);
            $minimum = 15 * (1 + self::next($state, 3));

            return [
                'enabled_weekdays' => [1 + self::next($state, 7)],
                'opening_minute' => $opening,
                'closing_minute' => $opening + 480 + self::next($state, 240),
                'minimum_duration' => $minimum,
                'maximum_duration' => $minimum + 15 * (1 + self::next($state, 8)),
                'daily_limit' => 1 + self::next($state, 8),
                'consecutive_limit' => 1 + self::next($state, 5),
                'buffer_before' => 5 * self::next($state, 7),
                'buffer_after' => 5 * self::next($state, 7),
            ];
        });
    }

    /** @return list<array<string, int|string|array<int, string>>> */
    public static function rooms(int $seed = self::DEFAULT_SEED, int $count = self::MINIMUM_CASES): array
    {
        $state = self::state($seed + 37);

        return self::build($seed, $count, 'room', static function (int $case) use (&$state): array {
            $capabilities = ['piano', 'violin', 'daf'];

            return [
                'room_id' => 1 + self::next($state, 30),
                'name' => 'Room-'.(1 + self::next($state, 30)),
                'active' => self::next($state, 2),
                'authorized' => self::next($state, 2),
                'capabilities' => [$capabilities[self::next($state, count($capabilities))]],
                'required_capability' => $capabilities[self::next($state, count($capabilities))],
                'occupied' => self::next($state, 2),
            ];
        });
    }

    /** @return list<array<string, int|string>> */
    public static function versions(int $seed = self::DEFAULT_SEED, int $count = self::MINIMUM_CASES): array
    {
        $state = self::state($seed + 47);

        return self::build($seed, $count, 'version', static function (int $case) use (&$state): array {
            $persisted = 1 + self::next($state, 500);
            $stateName = ['current', 'stale', 'missing', 'malformed'][self::next($state, 4)];

            return [
                'persisted_version' => 'v'.$persisted,
                'client_version' => match ($stateName) {
                    'current' => 'v'.$persisted,
                    'stale' => 'v'.max(0, $persisted - 1),
                    'missing' => '',
                    default => 'malformed-'.$persisted,
                },
                'state' => $stateName,
            ];
        });
    }

    /** @return list<array<string, int|string|null>> */
    public static function calendarProjection(int $seed = self::DEFAULT_SEED, int $count = self::MINIMUM_CASES): array
    {
        $state = self::state($seed + 71);
        $baseDate = new \DateTimeImmutable('2026-08-01');

        return self::build($seed, $count, 'calendar-projection', static function (int $case) use (&$state, $baseDate): array {
            $startOffset = self::next($state, 46);
            $endOffset = $startOffset + self::next($state, 8);
            $teacherIndex = self::next($state, 4);
            $studentIndex = self::next($state, 4);
            $roomIndex = self::next($state, 4);

            return [
                'start' => $baseDate->modify("+{$startOffset} days")->format('Y-m-d'),
                'end' => $baseDate->modify("+{$endOffset} days")->format('Y-m-d'),
                'teacher_index' => $teacherIndex === 3 ? null : $teacherIndex,
                'student_index' => $studentIndex === 3 ? null : $studentIndex,
                'room_index' => $roomIndex === 3 ? null : $roomIndex,
            ];
        });
    }

    /** @return list<array<string, int|string|array<int, string>>> */
    public static function concurrency(int $seed = self::DEFAULT_SEED, int $count = self::MINIMUM_CASES): array
    {
        $state = self::state($seed + 59);

        return self::build($seed, $count, 'concurrency', static function (int $case) use (&$state): array {
            $version = 1 + self::next($state, 500);
            $winner = self::next($state, 2) === 0 ? 'first' : 'second';

            return [
                'session_id' => 1 + self::next($state, 100),
                'current_version' => 'v'.$version,
                'stale_version' => 'v'.max(0, $version - 1),
                'winner' => $winner,
                'interleaving' => $winner === 'first' ? ['first', 'second'] : ['second', 'first'],
            ];
        });
    }

    public static function firstFailure(string $boundary, mixed $expected, mixed $observed): string
    {
        return sprintf(
            'First failure at %s; expected=%s; observed=%s.',
            $boundary,
            json_encode($expected, JSON_THROW_ON_ERROR),
            json_encode($observed, JSON_THROW_ON_ERROR),
        );
    }

    /** @return list<array<string, mixed>> */
    private static function build(int $seed, int $count, string $family, callable $builder): array
    {
        if ($count < self::MINIMUM_CASES) {
            throw new InvalidArgumentException('Scheduling properties require at least 100 generated cases.');
        }

        $cases = [];
        for ($case = 0; $case < $count; $case++) {
            $cases[] = [
                'seed' => $seed,
                'case' => $case,
                'family' => $family,
                ...$builder($case),
            ];
        }

        return $cases;
    }

    private static function state(int $seed): int
    {
        return (abs($seed) % 2147483646) + 1;
    }

    private static function next(int &$state, int $maxExclusive): int
    {
        $state = (int) (($state * 48271) % 2147483647);

        return $state % $maxExclusive;
    }
}
