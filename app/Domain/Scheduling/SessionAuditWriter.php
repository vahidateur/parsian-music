<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Models\ClassSession;
use App\Models\SessionAuditRecord;
use App\Models\User;
use JsonSerializable;

/** Appends the one immutable, versioned snapshot for an accepted transition. */
class SessionAuditWriter
{
    /** @param array<string, mixed> $before @param array<string, mixed> $after */
    public function append(
        ClassSession $session,
        ?User $actor,
        ScheduleProposal $proposal,
        ?SessionVersion $priorVersion,
        SessionVersion $resultingVersion,
        array $before,
        array $after,
        AvailabilityResult $availability,
    ): SessionAuditRecord {
        $conflicts = $availability->details['conflicts'] ?? null;
        $conflicts = $conflicts instanceof JsonSerializable ? $conflicts->jsonSerialize() : $conflicts;

        return $this->store([
            'actor_id' => $actor?->getKey(),
            'event_type' => SessionAuditRecord::EVENT_TYPE,
            'entity_type' => ClassSession::class,
            'action' => $priorVersion === null ? 'create' : 'update',
            'selection_mode' => $proposal->source->value,
            'total' => 1,
            'succeeded' => 1,
            'skipped' => 0,
            'failed' => 0,
            'metadata' => [
                'schema_version' => 1,
                'session_id' => $session->getKey(),
                'source' => $proposal->source->value,
                'prior_version' => $priorVersion?->value,
                'resulting_version' => $resultingVersion->value,
                'changed_fields' => array_keys(array_diff_assoc($after, $before)),
                'before' => $before,
                'after' => $after,
                'conflicts' => $conflicts,
                'override_reason' => $proposal->override?->reason,
            ],
            'occurred_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $attributes */
    protected function store(array $attributes): SessionAuditRecord
    {
        return SessionAuditRecord::query()->create($attributes);
    }
}
