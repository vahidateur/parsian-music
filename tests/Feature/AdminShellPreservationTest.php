<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Property 2: Preservation — non-layout behavior remains available to the shell.
 *
 * These tests intentionally describe the behavior observed before the layout fix.
 * They inspect the existing Alpine, Blade, and CSS contracts because this
 * repository has no JavaScript/browser test runner configured.
 *
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8**
 */
class AdminShellPreservationTest extends TestCase
{
    private const SHELL_CSS = __DIR__ . '/../../resources/css/admin/shell.css';
    private const ADMIN_TOKENS = __DIR__ . '/../../resources/css/admin/tokens.css';
    private const DESIGN_TOKENS = __DIR__ . '/../../resources/css/design-tokens.css';
    private const CORE_UI_CSS = __DIR__ . '/../../resources/css/components/core-ui.css';
    private const SHELL_JS = __DIR__ . '/../../resources/js/admin-shell.js';
    private const APP_JS = __DIR__ . '/../../resources/js/app.js';
    private const VIEW_ROOT = __DIR__ . '/../../resources/views';

    public function test_property_sidebar_collapse_preserves_transition_and_content_offset(): void
    {
        $css = $this->read(self::SHELL_CSS);
        $js = $this->read(self::SHELL_JS);

        $states = [
            'expanded' => '--admin-sidebar-width-expanded',
            'collapsed' => '--admin-sidebar-width-collapsed',
        ];

        foreach ($states as $state => $widthToken) {
            $this->assertStringContainsString(
                $widthToken,
                $css,
                "{$state} sidebar width contract is missing"
            );
        }

        $this->assertStringContainsString('toggleCollapsed()', $js);
        $this->assertStringContainsString("localStorage.setItem('adminSidebarCollapsed'", $js);
        $this->assertStringContainsString(
            'transition: width var(--admin-duration-standard)',
            $this->cssRule('.admin-shell__sidebar', $css)
        );
        $this->assertStringContainsString(
            'transition: margin-inline-start var(--admin-duration-standard)',
            $this->cssRule('.admin-shell__main', $css)
        );
        $this->assertStringContainsString(
            '--admin-sidebar-current-width: var(--admin-sidebar-width-collapsed)',
            $this->cssRule('.admin-shell--collapsed', $css)
        );
    }

    public function test_property_mobile_drawer_preserves_backdrop_focus_trap_and_rtl_positioning(): void
    {
        $drawer = $this->read(self::VIEW_ROOT . '/components/admin/drawer.blade.php');
        $topbar = $this->read(self::VIEW_ROOT . '/components/admin/topbar.blade.php');
        $css = $this->read(self::SHELL_CSS);
        $js = $this->read(self::SHELL_JS);
        $app = $this->read(self::APP_JS);

        foreach (['mobileOpen', 'accessibilityReady'] as $state) {
            $this->assertStringContainsString(
                $state,
                $drawer,
                "mobile drawer does not depend on {$state}"
            );
        }

        $this->assertStringContainsString('class="admin-shell__drawer-backdrop"', $drawer);
        $this->assertStringContainsString('x-show="mobileOpen && accessibilityReady"', $drawer);
        $this->assertStringContainsString('x-trap.noscroll="mobileOpen && accessibilityReady"', $drawer);
        $this->assertStringContainsString('role="dialog"', $drawer);
        $this->assertStringContainsString('aria-modal="true"', $drawer);
        $this->assertStringContainsString('@click="closeMobile()"', $drawer);
        $this->assertStringContainsString('@keydown.escape.window="closeMobile()"', $drawer);
        $this->assertStringContainsString('Alpine.plugin(focus)', $app);

        $drawerRule = $this->cssRule('.admin-shell__drawer', $css);
        $this->assertStringContainsString('inset-inline-end: 0', $drawerRule);
        $this->assertDoesNotMatchRegularExpression('/(?:^|[;\\s])(left|right):/', $drawerRule);

        foreach (['openMobile()', 'closeMobile()', 'accessibilityReady'] as $hook) {
            $this->assertStringContainsString($hook, $js, "drawer hook {$hook} is missing");
        }

        // The logical inline-end rule is the same contract in both direction states.
        foreach (['ltr', 'rtl'] as $direction) {
            $this->assertStringContainsString(
                'inset-inline-end: 0',
                $drawerRule,
                "drawer inline-end positioning is missing for {$direction} rendering"
            );
        }

        $this->assertStringContainsString('aria-controls="admin-mobile-drawer"', $topbar);
        $this->assertStringContainsString('@click="openMobile()"', $topbar);
    }

    public function test_property_topbar_dropdown_toggles_preserve_position_z_index_and_focus_restore(): void
    {
        $css = $this->read(self::SHELL_CSS);
        $js = $this->read(self::SHELL_JS);
        $topbar = $this->read(self::VIEW_ROOT . '/components/admin/topbar.blade.php');

        $panels = [
            'notification' => [
                'state' => 'notifOpen',
                'panel' => '.admin-shell__notif-panel',
                'toggle' => 'toggleNotif()',
                'close' => 'closeNotif()',
                'trigger' => 'notifTrigger',
                'id' => 'admin-notif-panel',
            ],
            'user' => [
                'state' => 'userMenuOpen',
                'panel' => '.admin-shell__user-panel',
                'toggle' => 'toggleUserMenu()',
                'close' => 'closeUserMenu()',
                'trigger' => 'userMenuTrigger',
                'id' => 'admin-user-panel',
            ],
        ];

        foreach ($panels as $name => $panel) {
            $this->assertStringContainsString(
                'x-show="' . $panel['state'] . '"',
                $topbar,
                "{$name} panel visibility state is missing"
            );
            $this->assertStringContainsString($panel['id'], $topbar);
            $this->assertStringContainsString($panel['toggle'], $js);
            $this->assertStringContainsString($panel['close'], $js);
            $this->assertStringContainsString('$refs.' . $panel['trigger'] . '?.focus()', $js);

            $rule = $this->cssRule($panel['panel'], $css);
            foreach (['position: absolute', 'top: 100%', 'inset-inline-end: 0', 'z-index: var(--admin-z-dropdown)'] as $declaration) {
                $this->assertStringContainsString($declaration, $rule, "{$name} panel is missing {$declaration}");
            }
        }

        $this->assertStringContainsString('closeAllPanels()', $js);
        $this->assertStringContainsString('this.notifOpen = false', $js);
        $this->assertStringContainsString('this.userMenuOpen = false', $js);
        $this->assertStringContainsString('@click.outside="closeNotif()"', $topbar);
        $this->assertStringContainsString('@click.outside="closeUserMenu()"', $topbar);
        $this->assertStringContainsString('@keydown.escape.window="closeNotif()"', $topbar);
        $this->assertStringContainsString('@keydown.escape.window="closeUserMenu()"', $topbar);
    }

    public function test_property_keyboard_navigation_preserves_visible_focus_indicators(): void
    {
        $css = $this->read(self::SHELL_CSS);
        $focusRule = $this->cssRuleContainingSelector('.admin-shell__collapse-button:focus-visible', $css);

        $interactiveShellClasses = [
            '.admin-shell__collapse-button:focus-visible',
            '.admin-shell__icon-button:focus-visible',
            '.admin-shell__mobile-toggle:focus-visible',
            '.admin-shell__placeholder-button:focus-visible',
            '.admin-shell__user-placeholder:focus-visible',
            '.admin-navigation__link:focus-visible',
            '.admin-shell__brand:focus-visible',
            '.admin-shell__breadcrumb-link:focus-visible',
        ];

        foreach ($interactiveShellClasses as $selector) {
            $this->assertStringContainsString($selector, $css, "focus indicator selector missing: {$selector}");
        }

        $this->assertStringContainsString('outline: var(--ui-focus-width)', $focusRule);
        $this->assertStringContainsString('outline-offset: var(--ui-focus-offset)', $focusRule);
        $this->assertStringContainsString('.admin-shell__search-input:focus', $css);
        $this->assertMatchesRegularExpression(
            '/\.admin-shell__search-input:focus\s*\{[^}]*box-shadow:/s',
            $css,
            'search focus has no visible replacement for outline'
        );
    }

    public function test_property_reduced_motion_preserves_transition_disable_contract(): void
    {
        $css = $this->read(self::SHELL_CSS);
        $motionStart = strpos($css, '@media (prefers-reduced-motion: reduce)');

        $this->assertNotFalse($motionStart, 'reduced-motion media query is missing');
        $motionRules = substr($css, $motionStart);
        $this->assertStringContainsString('transition: none', $motionRules);

        foreach ([
            '.admin-shell__sidebar',
            '.admin-shell__main',
            '.admin-shell__collapse-icon',
            '.admin-shell__collapse-button',
            '.admin-shell__icon-button',
            '.admin-shell__mobile-toggle',
            '.admin-navigation__link',
        ] as $selector) {
            $this->assertStringContainsString($selector, $motionRules, "reduced-motion rule omits {$selector}");
        }
    }

    public function test_property_existing_admin_modules_continue_to_render_inside_the_shell(): void
    {
        $layout = $this->read(self::VIEW_ROOT . '/layouts/dashboard.blade.php');
        $shell = $this->read(self::VIEW_ROOT . '/components/admin/shell.blade.php');

        $modules = [
            'Dashboard' => ['admin/dashboard.blade.php', 'db__header'],
            'Teachers' => ['admin/teachers/index.blade.php', 'admin.teachers.index'],
            'Students' => ['admin/students/index.blade.php', 'admin.students.index'],
            // Canonical calendar view target of `admin.calendar.index`; the parallel
            // `admin/calendar.blade.php` was removed as an unreferenced duplicate.
            'Calendar' => ['admin/calendar/index.blade.php', 'calendar-page-title'],
            'Settings' => ['admin/settings/index.blade.php', 'x-settings.shell'],
        ];

        $this->assertStringContainsString('<x-admin.shell', $layout);
        $this->assertStringContainsString('@yield(\'content\')', $layout);
        $this->assertStringContainsString('class="admin-shell__content"', $shell);
        $this->assertStringContainsString('{{ $slot }}', $shell);

        foreach ($modules as $module => [$relativePath, $marker]) {
            $viewPath = self::VIEW_ROOT . '/' . $relativePath;
            $this->assertFileExists($viewPath, "{$module} view is missing");
            $view = $this->read($viewPath);
            $this->assertStringContainsString("@extends('layouts.dashboard')", $view, "{$module} bypasses the admin shell layout");
            $this->assertStringContainsString("@section('content')", $view, "{$module} has no content section");
            $this->assertStringContainsString($marker, $view, "{$module} content marker is missing");
        }
    }

    public function test_property_z_index_hierarchy_remains_token_based(): void
    {
        $adminTokens = $this->read(self::ADMIN_TOKENS);
        $designTokens = $this->read(self::DESIGN_TOKENS);
        $shellCss = $this->read(self::SHELL_CSS);
        $coreUiCss = $this->read(self::CORE_UI_CSS);

        $adminLayerTokens = [
            '--admin-z-navigation' => 'var(--z-sticky)',
            '--admin-z-dropdown' => 'var(--z-dropdown)',
            '--admin-z-overlay' => 'var(--z-modal-backdrop)',
            '--admin-z-dialog' => 'var(--z-modal)',
            '--admin-z-toast' => 'var(--z-toast)',
        ];

        foreach ($adminLayerTokens as $token => $value) {
            $this->assertStringContainsString("{$token}: {$value}", $adminTokens);
        }

        foreach ([
            '--z-dropdown: 10',
            '--z-sticky: 20',
            '--z-modal-backdrop: 40',
            '--z-modal: 50',
            '--z-popover: 60',
            '--z-toast: 70',
        ] as $primitive) {
            $this->assertStringContainsString($primitive, $designTokens);
        }

        foreach (['.admin-shell__sidebar', '.admin-shell__topbar'] as $selector) {
            $this->assertStringContainsString('z-index: var(--admin-z-navigation)', $this->cssRule($selector, $shellCss));
        }
        $this->assertStringContainsString('z-index: var(--admin-z-dropdown)', $this->cssRule('.admin-shell__notif-panel', $shellCss));
        $this->assertStringContainsString('z-index: var(--admin-z-dialog)', $this->cssRule('.admin-shell__drawer', $shellCss));
        $this->assertStringContainsString('z-index: var(--z-modal)', $this->cssRule('.ui-modal', $coreUiCss));
        $this->assertStringContainsString('z-index: var(--z-popover)', $this->cssRule('.ui-tooltip__content', $coreUiCss));
        preg_match_all('/z-index:\s*([^;]+);/', $shellCss, $zIndexDeclarations);
        foreach ($zIndexDeclarations[1] as $declaration) {
            $this->assertStringStartsWith('var(', trim($declaration), 'shell z-index must resolve through a token');
        }
    }

    private function read(string $path): string
    {
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents, "Unable to read {$path}");

        return $contents;
    }

    private function cssRule(string $selector, string $css): string
    {
        $pattern = '/' . preg_quote($selector, '/') . '\\s*\\{(?<body>[^}]*)\\}/s';
        preg_match($pattern, $css, $matches);
        $this->assertArrayHasKey('body', $matches, "CSS rule not found for {$selector}");

        return $matches['body'];
    }

    private function cssRuleContainingSelector(string $selector, string $css): string
    {
        $pattern = '/' . preg_quote($selector, '/') . '\\s*,.*?\\{(?<body>[^}]*)\\}/s';
        preg_match($pattern, $css, $matches);
        $this->assertArrayHasKey('body', $matches, "Grouped CSS rule not found for {$selector}");

        return $matches['body'];
    }
}
