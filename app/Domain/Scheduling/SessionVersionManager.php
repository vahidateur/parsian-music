<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Models\ClassSession;

/** Issues opaque versions; legacy timestamps are interpreted only by its adapter. */
final readonly class SessionVersionManager
{
    public function __construct(private LegacySessionVersionAdapter $legacy) {}

    public function current(ClassSession $session): ?SessionVersion
    {
        return $this->legacy->current($session);
    }

    public function matches(ClassSession $session, SessionVersion $submitted): bool
    {
        return $this->legacy->matches($session, $submitted);
    }

    public function next(): SessionVersion
    {
        return new SessionVersion('sv1_'.bin2hex(random_bytes(24)));
    }
}
