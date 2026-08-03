<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Single named route in the `admin.` namespace with its controller and target contract.
 *
 * @see AdminRouteInventory
 */
final readonly class AdminRouteDefinition
{
    /**
     * @param list<string> $methods HTTP verbs registered for the route
     */
    public function __construct(
        public string $name,
        public string $uri,
        public array $methods,
        public string $controller,
        public string $method,
        public bool $controllerMethodExists,
        public ?AdminRouteTarget $target,
        public ?string $consumeOnlyOwner,
    ) {}

    /**
     * Routes owned by another specification: their behavior is consumed, never redefined here.
     */
    public function isConsumeOnly(): bool
    {
        return $this->consumeOnlyOwner !== null;
    }
}
