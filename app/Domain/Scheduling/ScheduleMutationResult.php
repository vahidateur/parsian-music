<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Models\ClassSession;
use App\Models\SessionAuditRecord;

/** Returned only after the enclosing authoritative mutation transaction commits. */
final readonly class ScheduleMutationResult
{
    /** @param array<string, int> $resourceVersions */
    public function __construct(
        public ClassSession $session,
        public SessionVersion $version,
        public AvailabilityResult $availability,
        public SessionAuditRecord $audit,
        public array $resourceVersions,
    ) {}
}
