<?php

namespace Tests\Feature\Admin;

require_once dirname(__DIR__, 2).'/TestCase.php';

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CalendarPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => RoleEnum::ADMIN]);
        $this->teacher = User::factory()->create(['role' => RoleEnum::TEACHER]);
    }

    public function test_calendar_routes_are_named_and_admin_protected(): void
    {
        $index = Route::getRoutes()->getByName('admin.calendar.index');
        $events = Route::getRoutes()->getByName('admin.calendar.events');

        $this->assertNotNull($index);
        $this->assertNotNull($events);
        $this->assertSame('admin/calendar', $index->uri());
        $this->assertSame('admin/calendar/events', $events->uri());

        foreach ([$index, $events] as $route) {
            $this->assertContains('auth', $route->middleware());
            $this->assertContains('role:admin', $route->middleware());
        }
    }

    public function test_calendar_page_and_api_reject_guests_and_non_admin_users(): void
    {
        $this->get(route('admin.calendar.index'))->assertRedirect(route('login'));
        $this->getJson(route('admin.calendar.events', ['start' => '2025-07-14', 'end' => '2025-07-14']))
            ->assertRedirect(route('login'));

        $this->actingAs($this->teacher)->get(route('admin.calendar.index'))->assertForbidden();
        $this->actingAs($this->teacher)
            ->getJson(route('admin.calendar.events', ['start' => '2025-07-14', 'end' => '2025-07-14']))
            ->assertForbidden();
    }

    public function test_admin_page_resolves_calendar_components_and_preserves_shell_content(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.calendar.index'));

        $response->assertOk()
            ->assertSee('id="calendar-page-title"', false)
            ->assertSee('data-calendar-root', false)
            ->assertSee('data-calendar-week-sidebar', false)
            ->assertSee('data-calendar-filters', false)
            ->assertSee('data-calendar-mount', false)
            ->assertSee('data-calendar-drawer', false)
            ->assertSee('href="'.route('admin.dashboard').'"', false);

        foreach (['calendar-layout', 'calendar-header', 'week-sidebar', 'day-timeline', 'event-drawer', 'event-filters'] as $component) {
            $this->assertTrue(view()->exists("components.{$component}"), "Missing Blade component: {$component}");
        }
    }

    public function test_calendar_blade_has_no_inline_presentation_behavior_or_queries(): void
    {
        foreach ($this->calendarBladeFiles() as $path) {
            $source = file_get_contents($path);
            $this->assertIsString($source);
            $this->assertDoesNotMatchRegularExpression('/\bstyle\s*=/', $source, $path);
            $this->assertDoesNotMatchRegularExpression('/\bon(?:click|mouseover|mouseout|focus|blur)\s*=/', $source, $path);
            $this->assertDoesNotMatchRegularExpression('/\b(?:DB::|->(?:query|where|get|first|find|pluck|paginate|all)\s*\()/', $source, $path);
        }
    }

    public function test_calendar_modules_use_orchestrator_imports_without_circular_sibling_imports(): void
    {
        $calendarDirectory = base_path('resources/js/calendar');
        $siblings = ['fullcalendar', 'sidebar', 'drawer', 'filters'];
        $orchestrator = file_get_contents($calendarDirectory.'/calendar-app.js');

        foreach ($siblings as $sibling) {
            $this->assertSame(1, substr_count($orchestrator, "from './{$sibling}.js'"));
        }

        foreach ($siblings as $sibling) {
            $source = file_get_contents($calendarDirectory.'/'.$sibling.'.js');
            foreach ($siblings as $other) {
                if ($sibling !== $other) {
                    $this->assertStringNotContainsString("from './{$other}.js'", $source);
                }
            }
        }
    }

    public function test_calendar_vite_entry_is_manifest_entry_with_dynamic_fullcalendar_chunks(): void
    {
        $manifestPath = public_path('build/manifest.json');
        $this->assertFileExists($manifestPath, 'Run the Vite build before calendar verification.');

        $manifest = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $entry = $manifest['resources/js/calendar/calendar-app.js'] ?? null;

        $this->assertIsArray($entry);
        $this->assertTrue($entry['isEntry'] ?? false);
        $this->assertNotEmpty($entry['file'] ?? null);

        foreach ([
            'node_modules/@fullcalendar/core/index.js',
            'node_modules/@fullcalendar/timegrid/index.js',
            'node_modules/@fullcalendar/interaction/index.js',
            'node_modules/@fullcalendar/core/locales/fa.js',
        ] as $chunk) {
            $this->assertContains($chunk, $entry['dynamicImports'] ?? []);
            $this->assertTrue($manifest[$chunk]['isDynamicEntry'] ?? false);
            $this->assertNotContains($chunk, $entry['imports'] ?? []);
        }
    }

    public function test_calendar_backend_uses_eager_loading_and_safe_generic_failures(): void
    {
        $controller = file_get_contents(base_path('app/Http/Controllers/Admin/CalendarController.php'));
        $resource = file_get_contents(base_path('app/Http/Resources/CalendarEventResource.php'));

        $this->assertStringContainsString('withEnrollmentDetails()', $controller);
        $this->assertStringContainsString('orderBySchedule()->get()', $controller);
        $this->assertStringContainsString('catch (Throwable $exception)', $controller);
        $this->assertStringContainsString("__('admin.calendar_errors.loading_calendar')", $controller);
        $this->assertStringNotContainsString("'exception' =>", $controller);
        $this->assertStringNotContainsString('"exception" =>', $controller);
        $this->assertDoesNotMatchRegularExpression('/->(?:load|loadMissing)\s*\(/', $resource);
    }

    public function test_calendar_feature_has_no_debug_logging_or_statements(): void
    {
        $files = array_merge(
            $this->calendarBladeFiles(),
            glob(base_path('resources/js/calendar/*.js')) ?: [],
            [
                base_path('app/Http/Controllers/Admin/CalendarController.php'),
                base_path('app/Http/Resources/CalendarEventResource.php'),
            ],
        );

        foreach ($files as $path) {
            $source = file_get_contents($path);
            $this->assertDoesNotMatchRegularExpression('/\bconsole\.(?:log|debug|info|warn|error)\s*\(/', $source, $path);
            $this->assertDoesNotMatchRegularExpression('/\b(?:dd|dump|var_dump|print_r)\s*\(/', $source, $path);
        }
    }

    /** @return list<string> */
    private function calendarBladeFiles(): array
    {
        return array_merge(
            [base_path('resources/views/admin/calendar/index.blade.php')],
            glob(base_path('resources/views/components/calendar*.blade.php')) ?: [],
            [base_path('resources/views/components/week-sidebar.blade.php')],
            [base_path('resources/views/components/day-timeline.blade.php')],
        );
    }
}
