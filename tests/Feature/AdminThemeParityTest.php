<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Theme parity and state fallback contracts for the existing admin design system.
 *
 * **Validates: Requirements 13.1, 13.2, 13.5, 13.6, 13.8, 13.9**
 */
class AdminThemeParityTest extends TestCase
{
    private const LAYOUT = __DIR__ . '/../../resources/views/layouts/dashboard.blade.php';
    private const SHELL_CSS = __DIR__ . '/../../resources/css/admin/shell.css';
    private const PAGES_CSS = __DIR__ . '/../../resources/css/admin/pages.css';
    private const SHELL_JS = __DIR__ . '/../../resources/js/admin-shell.js';
    private const LIST_TOOLBAR = __DIR__ . '/../../resources/views/admin/partials/list-toolbar.blade.php';

    public function test_layout_has_a_safe_dark_fallback_for_any_unsupported_cookie_value(): void
    {
        $layout = $this->read(self::LAYOUT);

        $this->assertStringContainsString(
            "(\$_COOKIE['pm_admin_theme'] ?? null) === 'glass'",
            $layout
        );
        $this->assertStringContainsString(
            "? 'glass' : 'dark'",
            $layout,
            'Unsupported or missing cookie state must render the dark marker without throwing.'
        );
        $this->assertStringContainsString(
            'data-admin-theme="{{ $adminTheme }}"',
            $layout
        );
    }

    public function test_shared_ui_primitives_resolve_through_existing_admin_theme_tokens(): void
    {
        $css = $this->read(self::SHELL_CSS);

        foreach ([
            '--ui-surface: var(--admin-color-surface-glass)',
            '--ui-surface-elevated: var(--admin-color-surface-elevated)',
            '--ui-border: var(--admin-color-border-strong)',
            '--ui-border-subtle: var(--admin-color-border)',
            '--ui-text: var(--admin-color-text)',
            '--ui-text-muted: var(--admin-color-text-muted)',
            '--ui-text-subtle: var(--admin-color-text-subtle)',
            '--ui-accent: var(--admin-color-accent)',
            '--ui-accent-hover: var(--admin-color-accent-hover)',
        ] as $alias) {
            $this->assertStringContainsString($alias, $css, "Missing admin semantic alias: {$alias}");
        }
    }

    public function test_completed_operational_markup_uses_the_existing_admin_contract_in_both_themes(): void
    {
        $css = $this->read(self::PAGES_CSS);
        $js = $this->read(self::SHELL_JS);

        foreach ([
            '.admin-page .text-gray-100',
            '.admin-page .text-gray-400',
            '.admin-page .bg-gray-900\\/50',
            '.admin-page .bg-gray-800\\/50',
            '.admin-page .border-gray-800\\/60',
            '.admin-page .hover\\:bg-gray-800\\/25:hover',
        ] as $selector) {
            $this->assertStringContainsString($selector, $css, "Missing parity bridge selector: {$selector}");
        }

        $this->assertStringContainsString('export const normalizeAdminTheme', $js);
        $this->assertStringContainsString("pm_admin_theme=\${normalized}", $js);
        $this->assertStringNotContainsString('location.reload', $js);
        $this->assertStringNotContainsString('window.location', $js);
    }

    public function test_shared_list_toolbar_keeps_the_canonical_list_context_during_navigation(): void
    {
        $toolbar = $this->read(self::LIST_TOOLBAR);

        $this->assertStringContainsString('$list->formParameters()', $toolbar);
        $this->assertStringContainsString('$list->context->search', $toolbar);
        $this->assertStringContainsString('$list->has_active_context', $toolbar);
    }

    private function read(string $path): string
    {
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents, "Unable to read {$path}");

        return $contents;
    }
}
