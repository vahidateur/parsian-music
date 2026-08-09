<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Models\InstituteProfile;
use DateTimeZone;

/** Reads the existing institute hours as a legacy-compatible effective rule snapshot. */
final readonly class AcademyRulesProvider
{
    public function __construct(private ?EffectiveSchedulingRules $override = null) {}

    public function effective(): EffectiveSchedulingRules
    {
        if ($this->override !== null) {
            return $this->override;
        }

        $profile = InstituteProfile::query()->find(1);
        $weekdays = $this->weekdays($profile?->working_days);
        $opening = $this->minute($profile?->working_hours_from, 0);
        $closing = $this->minute($profile?->working_hours_to, 1440);
        $version = $profile?->updated_at?->toISOString() ?? 'legacy-v1';

        return EffectiveSchedulingRules::legacy(new DateTimeZone((string) config('app.timezone', 'Asia/Tehran')), $weekdays, $opening, $closing, $version, 'institute_profile');
    }

    /** @param array<int, string>|null $days @return list<int> */
    private function weekdays(?array $days): array
    {
        $map = ['monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6, 'sunday' => 7];
        $result = array_values(array_unique(array_filter(array_map(static fn (mixed $day): ?int => is_string($day) ? ($map[$day] ?? null) : null, $days ?? []))));
        sort($result);

        return $result === [] ? [1, 2, 3, 4, 5, 6, 7] : $result;
    }

    private function minute(?string $time, int $default): int
    {
        if ($time === null || ! preg_match('/^(?<hour>[01]\\d|2[0-3]):(?<minute>[0-5]\\d)$/', $time, $matches)) {
            return $default;
        }

        return ((int) $matches['hour'] * 60) + (int) $matches['minute'];
    }
}
