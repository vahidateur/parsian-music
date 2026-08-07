<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use InvalidArgumentException;

/** Explicit override intent; authorization and conflict policy are evaluated later. */
final readonly class OverrideInstruction
{
    public function __construct(public bool $confirmed, public string $reason)
    {
        if (! $confirmed || trim($reason) === '' || strip_tags($reason) !== $reason) {
            throw new InvalidArgumentException('An override requires explicit confirmation and a plain-text reason.');
        }
    }
}
