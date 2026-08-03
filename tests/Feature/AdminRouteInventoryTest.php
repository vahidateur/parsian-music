<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\Support\AdminRouteInventory;
use Tests\TestCase;

/**
 * Verifies the registered admin route surface without redefining externally owned behavior.
 *
 * **Validates: Requirements 2.6, 2.8, 15.1, 15.6**
 */
class AdminRouteInventoryTest extends TestCase
{
    /**
     * Route contract gaps that this specification has not repaired yet.
     * Each entry must reference the task that closes it and be removed once closed.
     *
     * @var array<string, string>
     */
    private const KNOWN_CONTRACT_GAPS = [
        // No open route contract gap: `admin.teachers.show` was closed by task 10.1
        // (requirement 2.1) with TeacherController@show and the teacher detail view.
    ];

    public function test_named_admin_routes_are_extracted_from_the_route_source(): void
    {
        $routes = (new AdminRouteInventory())->routes();

        $this->assertNotEmpty($routes, 'No named admin routes were extracted from routes/web.php.');

        foreach ($routes as $route) {
            $this->assertStringStartsWith('admin.', $route->name);
            $this->assertNotSame('', $route->uri);
            $this->assertNotEmpty($route->methods);
            $this->assertTrue(class_exists($route->controller), sprintf('%s maps to unknown controller %s.', $route->name, $route->controller));
        }
    }

    public function test_every_named_admin_route_has_a_controller_method_and_documented_target(): void
    {
        $gaps = (new AdminRouteInventory())->contractGaps();

        $this->assertSame(
            self::KNOWN_CONTRACT_GAPS,
            $gaps,
            "Admin route contract gaps changed. Fix the route or update the documented gap list:\n"
                . json_encode($gaps, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );
    }

    public function test_routes_without_a_known_gap_resolve_to_an_existing_target(): void
    {
        foreach ((new AdminRouteInventory())->routes() as $route) {
            if (array_key_exists($route->name, self::KNOWN_CONTRACT_GAPS)) {
                continue;
            }

            $this->assertTrue($route->controllerMethodExists, sprintf('%s has no controller method.', $route->name));
            $this->assertNotNull($route->target, sprintf('%s has no declared response target.', $route->name));
            $this->assertNotEmpty($route->target->kinds, sprintf('%s declares an empty response target.', $route->name));

            if ($route->target->hasKind('view')) {
                $this->assertNotEmpty($route->target->viewNames, sprintf('%s returns a View without a literal view target.', $route->name));

                foreach ($route->target->viewNames as $viewName) {
                    $this->assertTrue(view()->exists($viewName), sprintf('%s targets missing view [%s].', $route->name, $viewName));
                }
            }
        }
    }

    public function test_routes_owned_by_other_specs_are_marked_consume_only(): void
    {
        $routesByName = (new AdminRouteInventory())->routesByName();

        foreach ([
            'admin.dashboard' => 'admin-shell-layout-fix',
            'admin.calendar.index' => 'admin-calendar-module',
            'admin.sessions.index' => 'admin-bulk-selection-actions',
            'admin.settings.index' => 'admin-settings-module',
            'admin.users.index' => 'admin-settings-module',
            'admin.leads.index' => 'crm-ui-lead-management',
        ] as $name => $owner) {
            $this->assertArrayHasKey($name, $routesByName);
            $this->assertTrue($routesByName[$name]->isConsumeOnly(), sprintf('%s must be consume-only.', $name));
            $this->assertSame($owner, $routesByName[$name]->consumeOnlyOwner);
        }
    }

    public function test_routes_owned_by_this_spec_are_not_marked_consume_only(): void
    {
        $routesByName = (new AdminRouteInventory())->routesByName();

        foreach (['admin.teachers.index', 'admin.students.index', 'admin.invoices.index', 'admin.rooms.index', 'admin.instruments.index'] as $name) {
            $this->assertArrayHasKey($name, $routesByName);
            $this->assertFalse($routesByName[$name]->isConsumeOnly(), sprintf('%s is owned by this spec.', $name));
            $this->assertNull($routesByName[$name]->consumeOnlyOwner);
        }
    }
}
