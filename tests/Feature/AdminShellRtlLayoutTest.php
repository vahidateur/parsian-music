<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Focused RTL regression coverage for shell-owned positioning.
 *
 * **Validates: Requirements 2.4**
 */
class AdminShellRtlLayoutTest extends TestCase
{
    private const SHELL_CSS = __DIR__ . '/../../resources/css/admin/shell.css';
    private const DASHBOARD_LAYOUT = __DIR__ . '/../../resources/views/layouts/dashboard.blade.php';

    public function test_shell_has_no_physical_directional_declarations(): void
    {
        $css = $this->read(self::SHELL_CSS);

        $this->assertDoesNotMatchRegularExpression(
            '/(?:^|[;{}\s])(?:left|right|margin-left|margin-right|padding-left|padding-right)\s*:/m',
            $css,
            'shell positioning must use logical directional properties'
        );
    }

    public function test_rtl_sidebar_and_main_offset_use_logical_properties(): void
    {
        $css = $this->read(self::SHELL_CSS);
        $layout = $this->read(self::DASHBOARD_LAYOUT);

        $this->assertStringContainsString('dir="{{ $isRtl ? \'rtl\' : \'ltr\' }}"', $layout);
        $this->assertStringContainsString('inset-inline-start: 0', $this->cssRule('.admin-shell__sidebar', $css));
        $this->assertStringContainsString('margin-inline-start: var(--admin-sidebar-current-width)', $css);
    }

    public function test_mobile_drawer_remains_at_inline_end(): void
    {
        $this->assertStringContainsString(
            'inset-inline-end: 0',
            $this->cssRule('.admin-shell__drawer', $this->read(self::SHELL_CSS))
        );
    }

    private function read(string $path): string
    {
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents, "Unable to read {$path}");

        return $contents;
    }

    private function cssRule(string $selector, string $css): string
    {
        preg_match('/' . preg_quote($selector, '/') . '\\s*\\{(?<body>[^}]*)\\}/s', $css, $matches);
        $this->assertArrayHasKey('body', $matches, "CSS rule not found for {$selector}");

        return $matches['body'];
    }
}
