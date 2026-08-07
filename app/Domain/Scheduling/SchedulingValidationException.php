<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use InvalidArgumentException;

/** Field-keyed, transport-neutral proposal validation failure. */
final class SchedulingValidationException extends InvalidArgumentException
{
    /** @param array<string, string> $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('The scheduling proposal is invalid.');
    }

    /** @param array<string, string> $errors */
    public static function with(array $errors): self
    {
        ksort($errors);

        return new self($errors);
    }
}
