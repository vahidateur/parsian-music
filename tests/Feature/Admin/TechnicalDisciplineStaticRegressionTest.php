<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Tests\Support\FrozenAreaBaseline;
use Tests\TestCase;

/**
 * Ownership-scoped static regression guards for task 15.2.
 *
 * The declared roots are app/, admin Blade, admin JavaScript and admin CSS. Auth
 * and Teacher Portal code are intentionally excluded from blocker scans: they are
 * frozen or separately owned follow-ups, not operational-admin scope.
 *
 * Requirements: 16.2, 16.3, 16.4, 16.5.
 */
final class TechnicalDisciplineStaticRegressionTest extends TestCase
{
    /** @var array<string, list<string>> */
    private const DECLARED_ROOTS = [
        'app' => ['.php'],
        'resources/views/admin' => ['.blade.php'],
        'resources/js' => ['.js'],
        'resources/css/admin' => ['.css'],
    ];

    /** @var list<string> */
    private const OUT_OF_SCOPE_PREFIXES = [
        'app/Http/Controllers/Auth/',
        'app/Http/Controllers/TeacherPortal/',
        'app/Http/Controllers/Teacher/',
    ];

    public function test_owned_admin_blade_has_no_inline_scripts_handlers_or_orm_access(): void
    {
        foreach ($this->sourceFiles(resource_path('views/admin'), ['.blade.php']) as $path) {
            $source = $this->read($path);
            $label = $this->relativePath($path);

            $this->assertDoesNotMatchRegularExpression('/<script\b/i', $source, $label);
            $this->assertDoesNotMatchRegularExpression(
                '/<[^>]+\s(?:onclick|ondblclick|onmousedown|onmouseup|onmouseover|onmousemove|onmouseout|onmouseenter|onmouseleave|onkeydown|onkeyup|onkeypress|onfocus|onblur|onsubmit|onchange|oninput)\s*=/i',
                $source,
                $label,
            );
            $this->assertDoesNotMatchRegularExpression('/<[^>]+\sstyle\s*=/i', $source, $label);
            $this->assertDoesNotMatchRegularExpression(
                '/\bDB::|\b[A-Z][A-Za-z0-9_]*::(?:query|where|find|first|all|with)\s*\(|->(?:newQuery|query|where|orWhere|with|load|paginate|cursor|lazy)\s*\(/i',
                $source,
                $label,
            );
        }
    }

    public function test_declared_operational_roots_have_no_debug_output(): void
    {
        foreach (self::DECLARED_ROOTS as $root => $suffixes) {
            foreach ($this->sourceFiles(base_path($root), $suffixes) as $path) {
                $source = $this->read($path);
                $label = $this->relativePath($path);

                $this->assertDoesNotMatchRegularExpression(
                    '/(?:\bdd\s*\(|\bdump\s*\(|\bvar_dump\s*\(|\bprint_r\s*\(|\bconsole\.log\s*\()/i',
                    $source,
                    $label,
                );
            }
        }
    }

    public function test_credentials_are_not_rendered_in_owned_admin_blade(): void
    {
        foreach ($this->sourceFiles(resource_path('views/admin'), ['.blade.php']) as $path) {
            $source = $this->read($path);
            $label = $this->relativePath($path);

            $this->assertDoesNotMatchRegularExpression(
                '/\bsession\s*\(\s*[\'\"][^\'\"]*(?:password|token|credential|secret)[^\'\"]*[\'\"]/i',
                $source,
                $label,
            );
            $this->assertDoesNotMatchRegularExpression(
                '/<input\b(?=[^>]*\bname\s*=\s*[\'\"][^\'\"]*(?:_(?:password|token|credential|credentials|secret)|(?:password|token|credential|credentials|secret))[\'\"])(?=[^>]*\bvalue\s*=)[^>]*>/is',
                $source,
                $label,
            );
            $this->assertDoesNotMatchRegularExpression(
                '/\{\{(?:(?!\}\}).)*(?:\bold\s*\(\s*[\'\"][^\'\"]*(?:_(?:password|token|credential|credentials|secret)|(?:password|token|credential|credentials|secret))[\'\"]|\$(?:[A-Za-z][A-Za-z0-9_]*_)?(?:password|token|credential|credentials|secret)\b|\[\s*[\'\"][^\'\"]*_(?:password|token|credential|secret)\s*[\'\"]\s*\]|config\s*\(\s*[\'\"][^\'\"]*_(?:password|token|credential|secret)\b)(?:(?!\}\}).)*\}\}/is',
                $source,
                $label,
            );
        }

        $usersIndex = $this->read(resource_path('views/admin/users/index.blade.php'));
        $this->assertDoesNotMatchRegularExpression('/\bsession\s*\(/i', $usersIndex);
        $this->assertDoesNotMatchRegularExpression('/\$(?:temp(?:orary)?_?password|api_?token|credential)\b/i', $usersIndex);
    }

    public function test_admin_css_has_no_important_declarations_and_glass_selector_has_one_owner(): void
    {
        $cssFiles = $this->sourceFiles(resource_path('css/admin'), ['.css']);
        $themeScopedFiles = [];

        foreach ($cssFiles as $path) {
            $source = $this->read($path);
            $label = $this->relativePath($path);

            $this->assertStringNotContainsString('!important', $source, $label);

            if (str_contains($source, '[data-admin-theme')) {
                $themeScopedFiles[] = $label;
            }
        }

        $this->assertSame(['resources/css/admin/glass.css'], $themeScopedFiles);
        $this->assertStringContainsString(
            '[data-admin-theme="glass"]',
            $this->read(resource_path('css/admin/glass.css')),
        );
    }

    public function test_date_and_session_create_state_keep_their_canonical_owners(): void
    {
        $jsFiles = $this->sourceFiles(resource_path('js'), ['.js']);
        $appSource = $this->read(resource_path('js/app.js'));

        $this->assertSame(
            ['resources/js/admin-date-form.js'],
            $this->relativePathsContaining($jsFiles, '/export\s+default\s+function\s+adminDateForm\b/'),
        );
        $this->assertStringContainsString("import adminDateForm from './admin-date-form'", $appSource);
        $this->assertSame(1, substr_count($appSource, "Alpine.data('adminDateForm'"));

        $this->assertSame(
            ['resources/js/session-create.js'],
            $this->relativePathsContaining($jsFiles, '/export\s+default\s+function\s+sessionCreate\b/'),
        );
        $this->assertStringContainsString("import sessionCreate from './session-create'", $appSource);
        $this->assertSame(1, substr_count($appSource, "Alpine.data('sessionCreate'"));

        $sessionCreateView = $this->read(resource_path('views/admin/sessions/create.blade.php'));
        $this->assertStringContainsString('x-data="sessionCreate"', $sessionCreateView);
        $this->assertDoesNotMatchRegularExpression('/(?:function|const)\s+sessionCreate\b/', $sessionCreateView);

        foreach ($this->sourceFiles(resource_path('views/admin'), ['.blade.php']) as $path) {
            $source = $this->read($path);

            if (str_contains($source, 'adminDateForm(')) {
                $this->assertDoesNotMatchRegularExpression('/(?:function|const)\s+adminDateForm\b/', $source, $this->relativePath($path));
            }
        }
    }

    public function test_settings_working_days_and_bulk_integration_keep_their_owners(): void
    {
        $jsFiles = $this->sourceFiles(resource_path('js'), ['.js']);
        $appSource = $this->read(resource_path('js/app.js'));

        $this->assertSame(
            ['resources/js/settings-working-days.js'],
            $this->relativePathsContaining($jsFiles, '/export\s+default\s+function\s+settingsWorkingDays\b/'),
        );
        $this->assertStringContainsString("import settingsWorkingDays from './settings-working-days'", $appSource);
        $this->assertSame(1, substr_count($appSource, "Alpine.data('settingsWorkingDays'"));
        $this->assertStringContainsString(
            'input[name="working_days[]"]',
            $this->read(resource_path('js/settings-working-days.js')),
        );

        $workingDayViews = $this->relativePathsContaining(
            $this->sourceFiles(resource_path('views/admin'), ['.blade.php']),
            '/name=[\'\"]working_days\[\][\'\"]/i',
        );
        $this->assertSame(['resources/views/admin/settings/sections/institute.blade.php'], $workingDayViews);
        $this->assertStringContainsString(
            'x-data="settingsWorkingDays"',
            $this->read(resource_path('views/admin/settings/sections/institute.blade.php')),
        );

        $this->assertSame(
            ['resources/js/bulk-selection-state.js'],
            $this->relativePathsContaining($jsFiles, '/export\s+default\s+function\s+bulkSelectionState\b/'),
        );
        $this->assertStringContainsString("import bulkSelectionState from './bulk-selection-state'", $appSource);
        $this->assertSame(1, substr_count($appSource, "Alpine.data('bulkSelectionState'"));

        foreach (['teachers', 'students'] as $entity) {
            $source = $this->read(resource_path("views/admin/{$entity}/index.blade.php"));
            $this->assertStringContainsString('<x-admin.bulk-selection.toolbar', $source);
            $this->assertStringContainsString('<x-admin.bulk-selection.result-summary', $source);
            $this->assertStringNotContainsString('BulkActionService', $source);
            $this->assertStringNotContainsString('BulkResultData', $source);
        }
    }

    public function test_frozen_area_still_requires_byte_preservation_or_a_matching_approval(): void
    {
        $differences = FrozenAreaBaseline::differences(
            FrozenAreaBaseline::manifest()['files'],
            FrozenAreaBaseline::currentFileFingerprints(),
        );
        $unapproved = FrozenAreaBaseline::unapprovedDifferences($differences, FrozenAreaBaseline::approvals());

        $this->assertSame(
            [],
            $unapproved,
            'Frozen files changed without a matching approval record: ' . implode(', ', array_column($unapproved, 'path')),
        );

        foreach (FrozenAreaBaseline::approvals() as $approval) {
            $this->assertSame([], FrozenAreaBaseline::validateApproval($approval));
        }
    }

    /** @param list<string> $files */
    private function relativePathsContaining(array $files, string $pattern): array
    {
        $matches = [];

        foreach ($files as $path) {
            if (preg_match($pattern, $this->read($path)) === 1) {
                $matches[] = $this->relativePath($path);
            }
        }

        return $matches;
    }

    /** @param list<string> $suffixes
     *  @return list<string>
     */
    private function sourceFiles(string $directory, array $suffixes): array
    {
        if (! is_dir($directory)) {
            throw new RuntimeException(sprintf('Declared static-scan root is missing: %s', $directory));
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $path = str_replace('\\', '/', $file->getPathname());

            if ($file->isFile() && ! $this->isOutOfScope($path) && $this->hasSuffix($path, $suffixes)) {
                $files[] = $path;
            }
        }

        sort($files);

        return $files;
    }

    /** @param list<string> $suffixes */
    private function hasSuffix(string $path, array $suffixes): bool
    {
        foreach ($suffixes as $suffix) {
            if (str_ends_with($path, $suffix)) {
                return true;
            }
        }

        return false;
    }

    private function isOutOfScope(string $path): bool
    {
        $relativePath = $this->relativePath($path);

        foreach (self::OUT_OF_SCOPE_PREFIXES as $prefix) {
            if (str_starts_with($relativePath, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function relativePath(string $path): string
    {
        $base = rtrim(str_replace('\\', '/', base_path()), '/').'/';
        $normalized = str_replace('\\', '/', $path);

        return str_starts_with($normalized, $base) ? substr($normalized, strlen($base)) : $normalized;
    }

    private function read(string $path): string
    {
        $source = file_get_contents($path);

        if ($source === false) {
            throw new RuntimeException(sprintf('Unable to read static-scan source: %s', $path));
        }

        return $source;
    }
}
