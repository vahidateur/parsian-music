<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminShellLayoutBugConditionTest extends TestCase
{
    private const SHELL_CSS = __DIR__ . '/../../resources/css/admin/shell.css';

    private const ADMIN_VIEW_ROOT = __DIR__ . '/../../resources/views';

    /**
     * Property 1: the shell contract must hold at every required desktop width.
     *
     * This is intentionally an exploration test. It is expected to fail against
     * the unfixed shell and is preserved for re-running after implementation.
     *
     * **Validates: Requirements 2.1, 2.2, 2.3, 2.6**
     */
    public function test_body_page_scroll_is_locked(): void
    {
        $this->assertRuleContains('.admin-page', $this->shellCss(), [
            'overflow: hidden',
            'height: 100vh',
        ], 'body page scroll lock is missing');
    }

    #[DataProvider('desktopViewportWidths')]
    public function test_shell_contains_horizontal_overflow_at_required_viewports(int $viewportWidth): void
    {
        $this->assertRuleContains('.admin-shell', $this->shellCss(), [
            'overflow-x: hidden',
            'max-width: 100vw',
        ], "shell horizontal containment is missing at {$viewportWidth}px");
    }

    public function test_main_and_content_form_a_single_vertical_scroll_container(): void
    {
        $css = $this->shellCss();

        $this->assertRuleContains('.admin-shell__main', $css, [
            'height: 100vh',
            'display: flex',
            'flex-direction: column',
        ], 'main shell height/flex containment is missing');

        $this->assertRuleContains('.admin-shell__content', $css, [
            'overflow-y: auto',
            'overflow-x: hidden',
            'flex: 1',
            'min-height: 0',
        ], 'content-only scrolling is missing');
    }

    public function test_content_inner_uses_token_max_width_and_logical_centering(): void
    {
        $this->assertRuleContains('.admin-shell__content-inner', $this->shellCss(), [
            'width: min(100%, var(--admin-content-max-width))',
            'margin-inline: auto',
        ], 'content max-width and logical centering contract is missing');
    }

    public function test_sidebar_and_topbar_use_scroll_safe_positioning(): void
    {
        $css = $this->shellCss();

        $this->assertRuleContains('.admin-shell__sidebar', $css, [
            'position: fixed',
            'inset-block: 0',
        ], 'sidebar fixed positioning is missing');

        $this->assertRuleContains('.admin-shell__topbar', $css, [
            'position: sticky',
            'top: 0',
        ], 'topbar sticky positioning is missing');
    }

    /**
     * **Validates: Requirements 2.5**
     */
    public function test_admin_templates_have_no_hardcoded_color_classes(): void
    {
        $combined = implode("\n", $this->adminTemplateContents());

        foreach (['bg-slate-950', 'text-white', 'border-slate-700'] as $hardcodedClass) {
            $this->assertStringNotContainsString(
                $hardcodedClass,
                $combined,
                "hardcoded color class remains in an admin template: {$hardcodedClass}"
            );
        }
    }

    /**
     * **Validates: Requirements 2.9, 2.14**
     */
    public function test_admin_templates_have_no_layout_utilities(): void
    {
        $combined = implode("\n", $this->adminTemplateContents());

        foreach ([
            'ml-64',
            'mr-64',
            'pl-64',
            'pr-64',
            'fixed',
            'sticky',
            'left-0',
            'right-0',
            'top-0',
            'min-h-screen',
        ] as $layoutUtility) {
            $this->assertStringNotContainsString(
                $layoutUtility,
                $combined,
                "layout utility remains in an admin template: {$layoutUtility}"
            );
        }
    }

    /**
     * **Validates: Requirements 2.14**
     */
    public function test_admin_templates_have_no_inline_layout_styles(): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/\sstyle\s*=/',
            implode("\n", $this->adminTemplateContents()),
            'inline layout styling remains in an admin template'
        );
    }

    /**
     * **Validates: Requirements 2.8**
     */
    public function test_admin_shell_uses_only_the_bem_namespace(): void
    {
        $adminTemplates = implode("\n", $this->adminTemplateContents());
        $shellCss = $this->shellCss();

        foreach (['admin-sidebar', 'admin-topbar', 'sidebar-nav'] as $oldNamespace) {
            $this->assertDoesNotMatchRegularExpression(
                '/class\s*=\s*["\'][^"\']*\b' . preg_quote($oldNamespace, '/') . '\b[^"\']*["\']/s',
                $adminTemplates,
                "old shell class namespace remains in templates: {$oldNamespace}"
            );
            $this->assertDoesNotMatchRegularExpression(
                '/\.' . preg_quote($oldNamespace, '/') . '\b/',
                $shellCss,
                "old shell class namespace remains in CSS: {$oldNamespace}"
            );
        }

        $this->assertDoesNotMatchRegularExpression(
            '/class\s*=\s*["\'][^"\']*\b(?:sidebar|topbar|content)\b[^"\']*["\']/s',
            $adminTemplates,
            'generic shell namespace class remains in admin templates'
        );

        $this->assertStringContainsString('admin-shell__', $adminTemplates . $shellCss);
    }

    public static function desktopViewportWidths(): array
    {
        return [
            '1440px' => [1440],
            '1600px' => [1600],
            '1920px' => [1920],
            '2560px' => [2560],
        ];
    }

    private function shellCss(): string
    {
        return file_get_contents(self::SHELL_CSS) ?: '';
    }

    /**
     * Extract one CSS rule and assert each declaration in the rule body.
     * This deliberately checks source contracts rather than computed browser
     * styles so the exploration test stays runnable in the existing PHP suite.
     */
    private function assertRuleContains(string $selector, string $css, array $declarations, string $message): void
    {
        $pattern = '/' . preg_quote($selector, '/') . '\s*\{(?<body>[^}]*)\}/s';
        preg_match($pattern, $css, $matches);

        $this->assertArrayHasKey('body', $matches, "CSS rule not found for {$selector}: {$message}");

        foreach ($declarations as $declaration) {
            $this->assertStringContainsString($declaration, $matches['body'], "{$selector} is missing {$declaration}: {$message}");
        }
    }

    /** @return list<string> */
    private function adminTemplateContents(): array
    {
        $files = [
            self::ADMIN_VIEW_ROOT . '/layouts/admin.blade.php',
            ...glob(self::ADMIN_VIEW_ROOT . '/components/admin/*.blade.php'),
        ];

        return array_values(array_filter(array_map(
            static fn (string $file): string => file_get_contents($file) ?: '',
            $files
        )));
    }
}
