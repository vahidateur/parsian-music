<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Declared response target of an admin route action.
 *
 * @see AdminRouteInventory
 */
final readonly class AdminRouteTarget
{
    /**
     * @param list<string> $kinds     one or more of: view, redirect, json
     * @param list<string> $viewNames literal view names returned by the action
     */
    public function __construct(
        public array $kinds,
        public array $viewNames = [],
    ) {}

    public function hasKind(string $kind): bool
    {
        return in_array($kind, $this->kinds, true);
    }

    public function describe(): string
    {
        return implode('|', $this->kinds);
    }
}
