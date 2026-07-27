<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Focused accessibility contracts for the admin shell.
 *
 * **Validates: Requirements 3.5, 3.6**
 */
class AdminShellAccessibilityTest extends TestCase
{
    private const SHELL_CSS = __DIR__ . '/../../resources/css/admin/shell.css';
    private const SHELL_VIEW = __DIR__ . '/../../resources/views/components/admin/shell.blade.php';
    private const DRAWER_VIEW = __DIR__ . '/../../resources/views/components/admin/drawer.blade.php';
    private const TOPBAR_VIEW = __DIR__ . '/../../resources/views/components/admin/topbar.blade.php';
    private const APP_JS = __DIR__ . '/../../resources/js/app.js';

    public function test_all_shell_interactive_controls_have_keyboard_focus_indicators(): void
    {
        $css = $this->read(self::SHELL_CSS);
        $selectors = [
            '.admin-shell__collapse-button:focus-visible',
            '.admin-shell__icon-button:focus-visible',
            '.admin-shell__mobile-toggle:focus-visible',
            '.admin-shell__placeholder-button:focus-visible',
            '.admin-shell__user-placeholder:focus-visible',
            '.admin-navigation__link:focus-visible',
            '.admin-navigation__sublink:focus-visible',
            '.admin-shell__brand:focus-visible',
            '.admin-shell__breadcrumb-link:focus-visible',
            '.admin-shell__notif-panel-clear:focus-visible',
            '.admin-shell__notif-panel-link:focus-visible',
            '.admin-shell__user-panel-item:focus-visible',
            '.admin-shell__search-input:focus-visible',
        ];

        foreach ($selectors as $selector) {
            $this->assertStringContainsString($selector, $css, "Missing keyboard focus selector: {$selector}");
        }

        $this->assertStringContainsString('outline: var(--ui-focus-width)', $css);
        $this->assertStringContainsString('outline-offset: var(--ui-focus-offset)', $css);
    }

    public function test_mobile_drawer_uses_alpine_focus_trap_and_restores_closed_state(): void
    {
        $drawer = $this->read(self::DRAWER_VIEW);
        $app = $this->read(self::APP_JS);

        $this->assertStringContainsString('x-trap.noscroll="mobileOpen && accessibilityReady"', $drawer);
        $this->assertStringContainsString('x-show="mobileOpen && accessibilityReady"', $drawer);
        $this->assertStringContainsString('x-cloak', $drawer);
        $this->assertStringContainsString('role="dialog"', $drawer);
        $this->assertStringContainsString('aria-modal="true"', $drawer);
        $this->assertStringContainsString('@keydown.escape.window="closeMobile()"', $drawer);
        $this->assertStringContainsString('Alpine.plugin(focus)', $app);
    }

    public function test_shell_reduced_motion_disables_every_declared_transition(): void
    {
        $css = $this->read(self::SHELL_CSS);
        $motionStart = strpos($css, '@media (prefers-reduced-motion: reduce)');

        $this->assertNotFalse($motionStart, 'Reduced-motion media query is missing');
        $motionRules = substr($css, $motionStart);
        $this->assertStringContainsString('transition: none', $motionRules);

        foreach ([
            '.admin-shell__sidebar',
            '.admin-shell__collapse-button',
            '.admin-shell__icon-button',
            '.admin-shell__mobile-toggle',
            '.admin-shell__placeholder-button',
            '.admin-shell__user-placeholder',
            '.admin-shell__collapse-icon',
            '.admin-shell__main',
            '.admin-navigation__link',
            '.admin-navigation__chevron',
            '.admin-navigation__submenu-enter',
            '.admin-navigation__submenu-leave',
            '.admin-navigation__sublink',
            '.admin-shell__search-input',
            '.admin-shell__notif-item',
            '.admin-shell__user-chevron',
            '.admin-shell__user-panel-item',
            '.admin-shell__panel-enter',
            '.admin-shell__panel-leave',
        ] as $selector) {
            $this->assertStringContainsString($selector, $motionRules, "Reduced-motion rule omits {$selector}");
        }
    }

    public function test_shell_dom_order_keeps_desktop_tab_sequence_logical(): void
    {
        $shell = $this->read(self::SHELL_VIEW);
        $components = glob(__DIR__ . '/../../resources/views/components/admin/*.blade.php') ?: [];
        $componentMarkup = implode("\n", array_map(
            static fn (string $path): string => file_get_contents($path) ?: '',
            $components
        ));

        $sidebar = strpos($shell, '<x-admin.sidebar');
        $main = strpos($shell, 'class="admin-shell__main"');
        $topbar = strpos($shell, '<x-admin.topbar');
        $content = strpos($shell, 'class="admin-shell__content"');

        $this->assertNotFalse($sidebar);
        $this->assertNotFalse($main);
        $this->assertNotFalse($topbar);
        $this->assertNotFalse($content);
        $this->assertLessThan($main, $sidebar, 'Sidebar must precede the main region');
        $this->assertLessThan($topbar, $main, 'Topbar must precede content within main');
        $this->assertLessThan($content, $topbar, 'Content must follow the topbar');
        $this->assertDoesNotMatchRegularExpression('/tabindex=["\'][1-9][0-9]*["\']/', $componentMarkup);
    }

    private function read(string $path): string
    {
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents, "Unable to read {$path}");

        return $contents;
    }
}
