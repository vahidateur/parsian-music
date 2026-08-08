<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Models\ClassSession;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

/**
 * Central, gate-first authorization boundary for scheduling-domain callers.
 *
 * This service neither resolves records nor exposes denial details. Callers must
 * authorize here before querying or evaluating protected scheduling facts.
 */
final class SchedulingAuthorization
{
    public function authorizeSession(User $actor, SchedulingAbility $ability, ClassSession $session): void
    {
        $gate = Gate::forUser($actor);

        $gate->authorize('view', $session);
        $gate->authorize($ability->value, $session);
    }

    public function authorizeCollection(User $actor, SchedulingAbility $ability): void
    {
        if (! in_array($ability, [SchedulingAbility::Suggest, SchedulingAbility::Recurrence, SchedulingAbility::Rules], true)) {
            throw new InvalidArgumentException("{$ability->value} requires a ClassSession target.");
        }

        Gate::forUser($actor)->authorize($ability->value, ClassSession::class);
    }

    public function authorizeBusinessCodeView(User $actor, Teacher|Student $resource): void
    {
        Gate::forUser($actor)->authorize('view', $resource);
    }

    /** @template TResult @param Closure(): TResult $evaluation @return TResult */
    public function evaluateSessionFacts(User $actor, SchedulingAbility $ability, ClassSession $session, Closure $evaluation): mixed
    {
        $this->authorizeSession($actor, $ability, $session);

        return $evaluation();
    }
}
