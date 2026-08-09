<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use JsonSerializable;

/** Complete, sorted immutable collection of authorized scheduling conflicts. */
final readonly class ConflictReport implements JsonSerializable
{
    /** @var list<SchedulingConflict> */
    public array $conflicts;

    /** @param list<SchedulingConflict> $conflicts */
    public function __construct(array $conflicts)
    {
        usort($conflicts, static fn (SchedulingConflict $left, SchedulingConflict $right): int => [$left->resource, $left->code, (string) $left->sessionId, $left->range?->start->format(DATE_ATOM) ?? ''] <=> [$right->resource, $right->code, (string) $right->sessionId, $right->range?->start->format(DATE_ATOM) ?? '']);
        $this->conflicts = $conflicts;
    }

    public function hasBlockingConflict(): bool { return array_filter($this->conflicts, static fn (SchedulingConflict $conflict): bool => $conflict->blocks()) !== []; }
    public function hasInvalidConstraint(): bool { return array_filter($this->conflicts, static fn (SchedulingConflict $conflict): bool => $conflict->invalid) !== []; }
    public function jsonSerialize(): array { return $this->conflicts; }
}
