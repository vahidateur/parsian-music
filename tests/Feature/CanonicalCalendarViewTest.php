<?php

namespace Tests\Feature;

require_once __DIR__.'/../TestCase.php';

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * De-duplication guard for the calendar view target (Requirements 2.5, 15.3, 16.4).
 *
 * The calendar rendering engine and the `admin.calendar.*` routes are owned by the
 * `admin-calendar-module` spec. This test only locks the fact that
 * `resources/views/admin/calendar/index.blade.php` is the single rendered target of
 * `admin.calendar.index`, and that the former parallel view
 * `resources/views/admin/calendar.blade.php` no longer exists and is referenced nowhere.
 */
class CanonicalCalendarViewTest extends TestCase
{
    use RefreshDatabase;

    private const CANONICAL_VIEW = 'admin.calendar.index';

    private const REMOVED_VIEW_PATH = 'resources/views/admin/calendar.blade.php';

    public function test_calendar_index_route_renders_the_canonical_view(): void
    {
        $admin = User::factory()->create(['role' => RoleEnum::ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.calendar.index'))
            ->assertOk()
            ->assertViewIs(self::CANONICAL_VIEW);

        $this->assertTrue(
            view()->exists(self::CANONICAL_VIEW),
            'Canonical calendar view resources/views/admin/calendar/index.blade.php must exist.'
        );
    }

    public function test_no_parallel_calendar_view_remains(): void
    {
        $this->assertFileDoesNotExist(
            base_path(self::REMOVED_VIEW_PATH),
            'The parallel calendar view was removed as an unreferenced duplicate; '
            .'the canonical target is resources/views/admin/calendar/index.blade.php.'
        );

        $this->assertFalse(
            view()->exists('admin.calendar'),
            'Only one calendar view target may be resolvable for admin.calendar.index.'
        );
    }

    public function test_only_one_calendar_view_target_is_rendered_by_admin_sources(): void
    {
        $renderTargets = [];

        foreach ($this->sourceFiles() as $file) {
            $contents = (string) file_get_contents($file);

            if (preg_match_all('/view\(\s*[\'"](admin\.calendar[^\'"]*)[\'"]/', $contents, $matches)) {
                $renderTargets = array_merge($renderTargets, $matches[1]);
            }

            foreach (['@include(\'admin.calendar\'', '@extends(\'admin.calendar\''] as $needle) {
                $this->assertStringNotContainsString(
                    $needle,
                    $contents,
                    'Removed parallel calendar view is still referenced in '.$file
                );
            }
        }

        $this->assertSame(
            [self::CANONICAL_VIEW],
            array_values(array_unique($renderTargets)),
            'Exactly one calendar view target may be rendered for admin.calendar.index.'
        );
    }

    public function test_calendar_route_still_points_at_the_owning_controller_method(): void
    {
        $route = Route::getRoutes()->getByName('admin.calendar.index');

        $this->assertNotNull($route);
        $this->assertSame(
            \App\Http\Controllers\Admin\CalendarController::class.'@index',
            $route->getActionName()
        );
    }

    /**
     * @return list<string>
     */
    private function sourceFiles(): array
    {
        $files = [];

        foreach ([base_path('app'), base_path('resources/views')] as $directory) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && in_array($file->getExtension(), ['php'], true)) {
                    $files[] = $file->getPathname();
                }
            }
        }

        sort($files);

        return $files;
    }
}
