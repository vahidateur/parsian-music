<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use InvalidArgumentException;
use JsonSerializable;

/** One authorized, deterministic conflict or hard-constraint report item. */
final readonly class SchedulingConflict implements JsonSerializable
{
    /** @param array<string, scalar|null> $parameters */
    public function __construct(
        public string $resource,
        public string $code,
        public string $classification,
        public bool $invalid,
        public int|string|null $sessionId = null,
        public ?TimeRange $range = null,
        public array $parameters = [],
    ) {
        if (! in_array($resource, ['teacher', 'student', 'enrollment', 'room', 'academy_rule', 'recurring_occurrence'], true) || ! in_array($classification, ['hard', 'soft', 'non_blocking'], true) || $code === '') {
            throw new InvalidArgumentException('A scheduling conflict must have safe classification fields.');
        }
    }

    public function blocks(): bool { return $this->classification !== 'non_blocking'; }

    public function jsonSerialize(): array
    {
        return ['resource' => $this->resource, 'code' => $this->code, 'classification' => $this->classification, 'invalid' => $this->invalid, 'session_id' => $this->sessionId, 'range' => $this->range, 'parameters' => $this->parameters];
    }
}
