<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\ClassSessionController;
use App\Policies\SessionPolicy;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * Task 1.1 characterization guards for the preserved calendar projection seam.
 *
 * Validates: Requirements 1.1-1.8, 17.1-17.7, 18.5-18.7, 20.5, 21.1-21.8.
 */
final class SchedulingArchitectureCharacterizationTest extends TestCase
{
    public function test_calendar_projection_has_no_command_generation_cache_or_decision_imports(): void
    {
        $forbidden = '/^use\\s+(?:App\\\\Domain\\\\Scheduling\\\\|App\\\\Services\\\\(?:SessionCreateService|SessionEditService|SessionGeneratorService)|Illuminate\\\\Support\\\\Facades\\\\Cache);/m';

        foreach ([
            app_path('Services/CalendarQueryService.php'),
            app_path('Http/Resources/CalendarEventResource.php'),
        ] as $path) {
            $this->assertDoesNotMatchRegularExpression($forbidden, $this->read($path), $this->relative($path));
        }
    }

    public function test_calendar_and_session_presentation_views_are_query_free_and_consume_server_contracts(): void
    {
        $queryPattern = '/\\b(?:DB::|[A-Z][A-Za-z0-9_]*::(?:query|where|find|first|all|with)\\s*\\(|->(?:newQuery|query|where|orWhere|with|load|paginate|cursor|lazy)\\s*\\()/';
        $views = [
            resource_path('views/admin/calendar/index.blade.php'),
            resource_path('views/components/event-drawer.blade.php'),
            resource_path('views/admin/sessions/edit.blade.php'),
        ];

        foreach ($views as $path) {
            $this->assertDoesNotMatchRegularExpression($queryPattern, $this->read($path), $this->relative($path));
        }

        $this->assertStringContainsString(':events-url="route(\'admin.calendar.events\')"', $this->read($views[0]));
        $this->assertStringContainsString('SessionEditViewData', $this->read($views[2]));
    }

    public function test_existing_named_routes_and_session_policy_remain_the_compatibility_seams(): void
    {
        $routes = [
            'admin.calendar.index' => [CalendarController::class, ['GET', 'HEAD']],
            'admin.calendar.events' => [CalendarController::class, ['GET', 'HEAD']],
            'admin.sessions.edit' => [ClassSessionController::class, ['GET', 'HEAD']],
            'admin.sessions.update' => [ClassSessionController::class, ['PUT', 'PATCH']],
        ];

        foreach ($routes as $name => [$controller, $methods]) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, $name.' must remain named.');
            $this->assertSame($controller, $route->getControllerClass(), $name.' controller changed.');
            $this->assertSame($methods, $route->methods(), $name.' methods changed.');
        }

        $this->assertTrue(method_exists(SessionPolicy::class, 'update'));
        $this->assertStringContainsString("authorize('update', \$session)", $this->read(app_path('Http/Controllers/Admin/ClassSessionController.php')));
        $this->assertStringContainsString("authorize('update', \$session)", $this->read(app_path('Http/Controllers/Admin/CalendarController.php')));
    }

    private function read(string $path): string
    {
        $source = file_get_contents($path);

        if ($source === false) {
            throw new RuntimeException('Unable to read characterization source: '.$path);
        }

        return $source;
    }

    private function relative(string $path): string
    {
        return str_replace('\\', '/', str_replace(base_path().'\\', '', $path));
    }
}
