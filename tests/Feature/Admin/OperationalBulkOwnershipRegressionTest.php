<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\AdminBulkActionController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Regression guards for the integration-only boundary of this specification.
 *
 * The bulk selection/execution/result/audit contracts remain owned by
 * admin-bulk-selection-actions; this suite only prevents a second architecture
 * from being introduced by the operational UX baseline.
 *
 * Requirements: 9.1, 9.4, 9.8, 9.10, 15.1, 15.2, 15.3, 15.4, 15.5, 15.6, 15.9, 15.10.
 */
final class OperationalBulkOwnershipRegressionTest extends TestCase
{
    public function test_bulk_routes_have_one_owner_controller_and_no_duplicate_registration(): void
    {
        $routes = array_values(array_filter(
            Route::getRoutes()->getRoutes(),
            static fn ($route): bool => is_string($route->getName())
                && str_starts_with($route->getName(), 'admin.')
                && str_contains($route->getName(), '.bulk'),
        ));

        $names = array_map(static fn ($route): string => $route->getName(), $routes);
        sort($names);
        $this->assertSame([
            'admin.students.bulk',
            'admin.students.bulk.preview',
            'admin.teachers.bulk',
            'admin.teachers.bulk.preview',
        ], $names);

        foreach ($routes as $route) {
            $this->assertSame(AdminBulkActionController::class.'@'.(
                str_ends_with($route->getName(), '.preview') ? 'preview' : 'store'
            ), $route->getActionName());
            $this->assertContains('POST', $route->methods());
            $this->assertNotContains('GET', $route->methods());
        }
    }

    public function test_bulk_persistence_and_audit_schema_exist_only_in_owner_contract(): void
    {
        $auditMigration = $this->path(base_path('database/migrations/2026_07_24_000001_create_audit_records_table.php'));
        $auditMigrations = array_values(array_filter(
            $this->sourceFiles(base_path('database/migrations')),
            static fn (string $path): bool => preg_match(
                "/Schema::create\\s*\\(\\s*['\"]audit_records['\"]/",
                (string) file_get_contents($path),
            ) === 1,
        ));

        $this->assertSame([$auditMigration], $auditMigrations);
        $this->assertSame([], array_values(array_filter(
            $this->sourceFiles(base_path('database/migrations')),
            static fn (string $path): bool => preg_match('/(?:operational|bulk).*?(?:audit|schema)|(?:audit|schema).*?(?:operational|bulk)/i', basename($path)) === 1,
        )));

        $auditModels = array_values(array_filter(
            $this->sourceFiles(base_path('app/Models')),
            static fn (string $path): bool => str_contains((string) file_get_contents($path), "'audit_records'"),
        ));

        $this->assertSame([$this->path(base_path('app/Models/AuditRecord.php'))], $auditModels);
    }

    public function test_bulk_execution_and_selection_state_have_one_canonical_implementation(): void
    {
        $bulkExecutors = array_values(array_filter(
            [...$this->sourceFiles(base_path('app/Services')), ...$this->sourceFiles(base_path('app/Actions'))],
            static fn (string $path): bool => preg_match('/Bulk.*(?:Service|Action)\.php$/i', basename($path)) === 1,
        ));
        sort($bulkExecutors);
        $this->assertEqualsCanonicalizing([
            $this->path(base_path('app/Services/BulkActionService.php')),
            $this->path(base_path('app/Services/BulkAuthorizationService.php')),
        ], $bulkExecutors);

        $selectionModules = array_values(array_filter(
            $this->sourceFiles(base_path('resources/js')),
            static fn (string $path): bool => preg_match('/(?:selection.*state|state.*selection)\.js$/i', basename($path)) === 1,
        ));
        $this->assertSame([$this->path(base_path('resources/js/bulk-selection-state.js'))], $selectionModules);
        $this->assertStringContainsString("import bulkSelectionState from './bulk-selection-state'", (string) file_get_contents(base_path('resources/js/app.js')));
        $this->assertStringNotContainsString('localStorage', (string) file_get_contents($selectionModules[0]));
        $this->assertStringNotContainsString('sessionStorage', (string) file_get_contents($selectionModules[0]));
    }

    public function test_bulk_result_dto_and_resource_contracts_are_not_duplicated(): void
    {
        $resultContracts = array_values(array_filter(
            [...$this->sourceFiles(base_path('app/DTOs')), ...$this->sourceFiles(base_path('app/Http/Resources'))],
            static fn (string $path): bool => preg_match('/^Bulk.*Result(?:Data|Resource)?\.php$/', basename($path)) === 1,
        ));
        sort($resultContracts);
        $this->assertSame([
            $this->path(base_path('app/DTOs/BulkItemResultData.php')),
            $this->path(base_path('app/DTOs/BulkResultData.php')),
            $this->path(base_path('app/Http/Resources/BulkResultResource.php')),
        ], $resultContracts);

        $controller = (string) file_get_contents(base_path('app/Http/Controllers/Admin/AdminBulkActionController.php'));
        $this->assertStringContainsString('BulkActionService', $controller);
        $this->assertStringContainsString('BulkResultResource', $controller);
        $this->assertSame([], array_values(array_filter(
            $resultContracts,
            static fn (string $path): bool => preg_match('/Operational|Integration/i', basename($path)) === 1,
        )));
    }

    public function test_operational_lists_consume_owner_components_without_bulk_domain_logic(): void
    {
        foreach (['teachers', 'students'] as $entity) {
            $source = (string) file_get_contents(base_path("resources/views/admin/{$entity}/index.blade.php"));
            $this->assertStringContainsString('<x-admin.bulk-selection.toolbar', $source);
            $this->assertStringContainsString('<x-admin.bulk-selection.result-summary', $source);
            $this->assertStringNotContainsString('BulkActionService', $source);
            $this->assertStringNotContainsString('BulkResultData', $source);
            $this->assertStringNotContainsString('AuditRecord', $source);
        }

        $components = array_map(
            static fn (string $name): string => base_path("resources/views/components/admin/bulk-selection/{$name}.blade.php"),
            ['toolbar', 'confirmation-dialog', 'result-summary'],
        );
        foreach ($components as $component) {
            $this->assertFileExists($component);
        }
        $this->assertSame(3, count($this->sourceFiles(base_path('resources/views/components/admin/bulk-selection'))));
    }

    public function test_operational_integration_does_not_add_parallel_execution_state_or_persistence_contracts(): void
    {
        $migrationFiles = $this->sourceFiles(base_path('database/migrations'));
        $operationalBulkSchemas = array_values(array_filter(
            $migrationFiles,
            static fn (string $path): bool => preg_match(
                "/Schema::(?:create|table)\\s*\\(\\s*['\"]([^'\"]*(?:bulk|selection|operational)[^'\"]*)['\"]/i",
                (string) file_get_contents($path),
            ) === 1,
        ));

        $this->assertSame([], $operationalBulkSchemas, 'Operational UX must not add bulk/selection persistence schemas.');

        $bulkServiceActions = [
            ...$this->sourceFiles(base_path('app/Services')),
            ...$this->sourceFiles(base_path('app/Actions')),
        ];
        $bulkServiceActions = array_values(array_filter(
            $bulkServiceActions,
            static fn (string $path): bool => preg_match('/Bulk.*(?:Service|Action)\\.php$/i', basename($path)) === 1,
        ));
        sort($bulkServiceActions);

        $this->assertEqualsCanonicalizing([
            $this->path(base_path('app/Services/BulkActionService.php')),
            $this->path(base_path('app/Services/BulkAuthorizationService.php')),
        ], $bulkServiceActions);

        $bulkExecutionImplementations = array_values(array_filter(
            $bulkServiceActions,
            static fn (string $path): bool => preg_match('/function\\s+(?:execute|handle|run|dispatch)\\s*\\(/i', (string) file_get_contents($path)) === 1,
        ));
        $this->assertSame([
            $this->path(base_path('app/Services/BulkActionService.php')),
        ], $bulkExecutionImplementations, 'Bulk execution must have one canonical service/action.');

        $ownerSelectionState = base_path('resources/js/bulk-selection-state.js');
        $sharedAdminState = base_path('resources/js/admin-state.js');
        $ownerSelectionSource = (string) file_get_contents($ownerSelectionState);
        $sharedAdminSource = (string) file_get_contents($sharedAdminState);

        $this->assertStringContainsString('selectedIds', $ownerSelectionSource);
        $this->assertStringNotContainsString('selectedIds', $sharedAdminSource);
        $this->assertStringNotContainsString('bulkSelectionState', $sharedAdminSource);
        $this->assertStringNotContainsString('adminState', $ownerSelectionSource);

        $appSource = (string) file_get_contents(base_path('resources/js/app.js'));
        $this->assertSame(1, substr_count($appSource, "Alpine.data('adminState'"));
        $this->assertSame(1, substr_count($appSource, "Alpine.data('bulkSelectionState'"));

        $operationalResultContracts = array_values(array_filter(
            [...$this->sourceFiles(base_path('app/DTOs')), ...$this->sourceFiles(base_path('app/Http/Resources'))],
            static fn (string $path): bool => preg_match('/(?:Operational|Integration).*?(?:Bulk.*)?Result(?:Data|Resource)?\\.php$/i', basename($path)) === 1,
        ));
        $this->assertSame([], $operationalResultContracts, 'Operational UX must consume owner result contracts, not define another DTO/resource.');

        $ownerBulkSymbols = [
            'BulkActionService',
            'BulkAuthorizationService',
            'BulkResultResource',
            'BulkResultData',
            'AuditRecordService',
            'StatusTransitionAction',
            'ProtectedDependencyChecker',
        ];
        $operationalControllers = array_values(array_filter(
            $this->sourceFiles(base_path('app/Http/Controllers/Admin')),
            static fn (string $path): bool => basename($path) !== 'AdminBulkActionController.php'
                && preg_match('/'.implode('|', $ownerBulkSymbols).'/', (string) file_get_contents($path)) === 1,
        ));
        $this->assertSame([], $operationalControllers, 'Operational controllers must consume bulk integration routes/components only.');
    }

    public function test_superseded_owner_regression_suites_remain_present_for_the_focused_green_gate(): void
    {
        $ownerSuites = [
            'admin-bulk-selection-actions' => [
                'tests/Feature/Admin/BulkTask212Test.php',
                'tests/Feature/Admin/BulkSelectionListRenderTest.php',
                'tests/Feature/Admin/BulkRowListQueryTest.php',
                'tests/Feature/Admin/BulkAuthorizationServiceTest.php',
                'tests/Feature/Admin/BulkAuditRouteTest.php',
            ],
            'admin-calendar-module' => [
                'tests/Feature/Admin/CalendarPageTest.php',
                'tests/Feature/Admin/CalendarControllerTest.php',
                'tests/Feature/CanonicalCalendarViewTest.php',
            ],
            'admin-settings-module' => [
                'tests/Feature/SettingsPagesTest.php',
            ],
            'crm-ui-lead-management' => [
                'tests/Feature/LeadFormConsistencyTest.php',
                'tests/Feature/LeadTranslationsTest.php',
                'tests/Feature/LeadQueryOptimizationTest.php',
                'tests/Feature/LeadOverdueIndicatorTest.php',
            ],
            'admin-shell-layout-fix' => [
                'tests/Feature/AdminShellPreservationTest.php',
            ],
            'demo-data-system' => [
                'tests/Feature/TestDataSeederTest.php',
            ],
        ];

        foreach ($ownerSuites as $owner => $suite) {
            foreach ($suite as $relativePath) {
                $this->assertFileExists(base_path($relativePath), sprintf(
                    'Regression suite for %s is missing: %s',
                    $owner,
                    $relativePath,
                ));
            }
        }
    }

    private function path(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    /** @return list<string> */
    private function sourceFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $this->path($file->getPathname());
            }
        }

        sort($files);

        return $files;
    }
}
