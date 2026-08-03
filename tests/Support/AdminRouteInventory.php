<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use RuntimeException;

/**
 * Test-side inventory of every named route in the `admin.` namespace.
 *
 * Extracts the routes registered by `routes/web.php`, resolves the controller method
 * for each one and derives the declared response target (view / redirect / JSON).
 * Routes owned by another specification are flagged consume-only so this baseline
 * never redefines their behavior.
 *
 * Requirements: 2.6, 2.8, 15.1, 15.6
 */
final class AdminRouteInventory
{
    private const ROUTE_SOURCE = 'routes/web.php';

    /**
     * Name prefixes whose behavior belongs to a different specification.
     *
     * @var array<string, string>
     */
    private const CONSUME_ONLY_OWNERS = [
        'admin.calendar.' => 'admin-calendar-module',
        'admin.dashboard' => 'admin-shell-layout-fix',
        'admin.leads.' => 'crm-ui-lead-management',
        'admin.sessions.' => 'admin-bulk-selection-actions',
        'admin.settings.' => 'admin-settings-module',
        'admin.users.' => 'admin-settings-module',
    ];

    /**
     * @return list<AdminRouteDefinition>
     */
    public function routes(): array
    {
        $routeSource = base_path(self::ROUTE_SOURCE);

        if (! is_file($routeSource)) {
            throw new RuntimeException(sprintf('Admin route source is missing: %s', $routeSource));
        }

        $inventory = [];

        foreach (RouteFacade::getRoutes()->getRoutes() as $route) {
            $name = $route->getName();

            if (! is_string($name) || ! str_starts_with($name, 'admin.')) {
                continue;
            }

            $inventory[] = $this->definitionFrom($route, $name);
        }

        usort($inventory, static fn (AdminRouteDefinition $left, AdminRouteDefinition $right): int => $left->name <=> $right->name);

        return $inventory;
    }

    /**
     * @return array<string, AdminRouteDefinition>
     */
    public function routesByName(): array
    {
        $indexed = [];

        foreach ($this->routes() as $route) {
            $indexed[$route->name] = $route;
        }

        return $indexed;
    }

    /**
     * Human-readable contract gaps, keyed by route name.
     *
     * A gap means the named route has no reachable controller method or no declared
     * view / redirect / JSON target, so requirement 2.6 is not yet satisfied for it.
     *
     * @return array<string, string>
     */
    public function contractGaps(): array
    {
        $gaps = [];

        foreach ($this->routes() as $route) {
            if (! $route->controllerMethodExists) {
                $gaps[$route->name] = sprintf('missing controller method %s@%s', $route->controller, $route->method);
                continue;
            }

            if ($route->target === null) {
                $gaps[$route->name] = 'no declared view, redirect, or JSON target';
                continue;
            }

            if (! $route->target->hasKind('view')) {
                continue;
            }

            if ($route->target->viewNames === []) {
                $gaps[$route->name] = 'returns a View without a literal view target';
                continue;
            }

            foreach ($route->target->viewNames as $viewName) {
                if (! view()->exists($viewName)) {
                    $gaps[$route->name] = sprintf('targets missing view [%s]', $viewName);
                    break;
                }
            }
        }

        return $gaps;
    }

    private function definitionFrom(Route $route, string $name): AdminRouteDefinition
    {
        $action = $route->getAction('controller');

        if (! is_string($action) || ! str_contains($action, '@')) {
            throw new RuntimeException(sprintf('%s does not use a controller action.', $name));
        }

        [$controller, $method] = explode('@', $action, 2);
        $methodExists = class_exists($controller) && method_exists($controller, $method);

        return new AdminRouteDefinition(
            name: $name,
            uri: $route->uri(),
            methods: array_values($route->methods()),
            controller: $controller,
            method: $method,
            controllerMethodExists: $methodExists,
            target: $methodExists ? $this->targetFor($controller, $method) : null,
            consumeOnlyOwner: $this->consumeOnlyOwnerFor($name),
        );
    }

    private function targetFor(string $controller, string $method): ?AdminRouteTarget
    {
        $reflection = new ReflectionMethod($controller, $method);
        $source = $this->methodSource($reflection);

        $kinds = [];
        $viewNames = [];

        foreach ($this->returnTypeNames($reflection->getReturnType()) as $typeName) {
            if (is_a($typeName, ViewContract::class, true)) {
                $viewNames = $this->literalViewNames($source);

                $kinds[] = 'view';

                continue;
            }

            if (is_a($typeName, RedirectResponse::class, true) && $this->returnsRedirect($source)) {
                $kinds[] = 'redirect';

                continue;
            }

            if (is_a($typeName, JsonResponse::class, true) && $this->returnsJson($source)) {
                $kinds[] = 'json';
            }
        }

        return $kinds === [] ? null : new AdminRouteTarget(array_values(array_unique($kinds)), $viewNames);
    }

    /**
     * @return list<string>
     */
    private function returnTypeNames(?ReflectionType $type): array
    {
        if ($type instanceof ReflectionNamedType) {
            return $type->isBuiltin() ? [] : [$type->getName()];
        }

        if (! $type instanceof ReflectionUnionType) {
            return [];
        }

        $names = [];

        foreach ($type->getTypes() as $member) {
            $names = [...$names, ...$this->returnTypeNames($member)];
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    private function literalViewNames(string $source): array
    {
        preg_match_all('/\bview\s*\(\s*[\'"]([^\'"]+)[\'"]/', $source, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    private function returnsRedirect(string $source): bool
    {
        return preg_match('/\breturn\s+(?:redirect\s*\(|back\s*\(|to_route\s*\()/', $source) === 1;
    }

    private function returnsJson(string $source): bool
    {
        return str_contains($source, 'response()->json(')
            || preg_match('/->response\s*\(\s*\)/', $source) === 1
            || preg_match('/\bnew\s+JsonResponse\s*\(/', $source) === 1;
    }

    private function methodSource(ReflectionMethod $method): string
    {
        $filename = $method->getFileName();

        if ($filename === false) {
            throw new RuntimeException(sprintf('Unable to locate %s@%s.', $method->getDeclaringClass()->getName(), $method->getName()));
        }

        $lines = file($filename);

        if ($lines === false) {
            throw new RuntimeException(sprintf('Unable to read %s.', $filename));
        }

        return implode('', array_slice(
            $lines,
            $method->getStartLine() - 1,
            $method->getEndLine() - $method->getStartLine() + 1,
        ));
    }

    private function consumeOnlyOwnerFor(string $routeName): ?string
    {
        foreach (self::CONSUME_ONLY_OWNERS as $prefix => $owner) {
            if (str_starts_with($routeName, $prefix)) {
                return $owner;
            }
        }

        return null;
    }
}
