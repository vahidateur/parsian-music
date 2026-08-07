<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use InvalidArgumentException;
use JsonSerializable;

/** Opaque concurrency token; interpretation belongs to the mutation boundary. */
final readonly class SessionVersion implements JsonSerializable
{
    public function __construct(public string $value)
    {
        if (trim($value) === '' || strip_tags($value) !== $value || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            throw new InvalidArgumentException('A session version must be non-empty plain text.');
        }
    }

    public static function fromNullable(mixed $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException('A session version must be a string.');
        }

        return new self($value);
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
