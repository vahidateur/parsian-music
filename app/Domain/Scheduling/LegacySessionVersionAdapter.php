<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Models\ClassSession;
use Carbon\CarbonInterface;

/** Keeps legacy updated_at tokens at the compatibility edge only. */
final class LegacySessionVersionAdapter
{
    public function current(ClassSession $session): ?SessionVersion
    {
        $stored = $session->getAttribute('session_version');
        if (is_string($stored) && $stored !== '') {
            return new SessionVersion($stored);
        }

        return $this->legacy($session);
    }

    public function matches(ClassSession $session, SessionVersion $submitted): bool
    {
        $current = $this->current($session);
        if ($current === null) {
            return false;
        }

        if (str_starts_with($submitted->value, 'sv1_')) {
            return str_starts_with($current->value, 'sv1_') && hash_equals($current->value, $submitted->value);
        }

        $legacy = $this->legacy($session);

        return $legacy !== null && hash_equals($legacy->value, $submitted->value);
    }

    private function legacy(ClassSession $session): ?SessionVersion
    {
        $updatedAt = $session->getAttribute('updated_at');
        $value = $updatedAt instanceof CarbonInterface ? $updatedAt->toISOString() : (is_string($updatedAt) ? $updatedAt : null);

        return $value === null || trim($value) === '' ? null : new SessionVersion($value);
    }
}
