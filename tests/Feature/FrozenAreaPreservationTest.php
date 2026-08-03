<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use Tests\Support\FrozenAreaBaseline;

/**
 * Preservation gate for the Frozen_Area byte baseline.
 *
 * Compares every path in the frozen preservation set against the approved baseline
 * manifest, requires an explicit approval record for any accepted exception, and
 * blocks indirect drift (new `!important` declarations, theme selector or cascade
 * changes). Failure messages name the exact path and the exact difference.
 *
 * Requirements: 13.3, 13.7, 14.1, 14.2, 14.5, 14.6, 14.7, 14.8
 */
class FrozenAreaPreservationTest extends TestCase
{
    public function test_every_frozen_path_is_present_with_the_expected_kind(): void
    {
        $missing = FrozenAreaBaseline::missingFrozenPaths();

        $this->assertSame(
            [],
            $missing,
            "Frozen paths are missing, renamed or moved:\n - " . implode("\n - ", $missing),
        );

        $this->assertCount(9, FrozenAreaBaseline::FROZEN_PATHS, 'The frozen preservation set must contain the nine declared paths.');
    }

    public function test_frozen_files_stay_byte_identical_to_the_approved_baseline(): void
    {
        $baseline = FrozenAreaBaseline::manifest()['files'];
        $current = FrozenAreaBaseline::currentFileFingerprints();

        $this->assertNotSame([], $baseline, 'The approved baseline manifest records no frozen file.');

        $differences = FrozenAreaBaseline::differences($baseline, $current);
        $unapproved = FrozenAreaBaseline::unapprovedDifferences($differences, FrozenAreaBaseline::approvals());

        $this->assertSame(
            [],
            $unapproved,
            "Frozen preservation gate failed. Unapproved differences:\n - "
                . implode("\n - ", array_map(
                    static fn (array $difference): string => FrozenAreaBaseline::describeDifference($difference),
                    $unapproved,
                )),
        );
    }

    public function test_baseline_manifest_only_records_paths_inside_the_frozen_set(): void
    {
        foreach (array_keys(FrozenAreaBaseline::manifest()['files']) as $path) {
            $this->assertTrue(
                FrozenAreaBaseline::isFrozenPath($path),
                sprintf('Baseline manifest records "%s", which is outside the frozen preservation set.', $path),
            );
        }
    }

    public function test_every_approval_exception_is_recorded_completely_and_matches_the_current_bytes(): void
    {
        $current = FrozenAreaBaseline::currentFileFingerprints();
        $approvals = FrozenAreaBaseline::approvals();

        $this->assertSame(
            array_values($approvals),
            $approvals,
            'The approval store must expose approvals as a list of records.',
        );

        foreach ($approvals as $index => $approval) {
            $problems = FrozenAreaBaseline::validateApproval($approval);

            $this->assertSame(
                [],
                $problems,
                sprintf("Approval record #%d is incomplete: %s", $index, implode('; ', $problems)),
            );

            $path = (string) $approval['path'];

            $this->assertArrayHasKey(
                $path,
                $current,
                sprintf('Approval record #%d approves "%s", which no longer exists.', $index, $path),
            );
            $this->assertSame(
                $approval['approved_sha256'],
                $current[$path]['sha256'],
                sprintf(
                    'Approval record #%d for "%s" approves sha256 %s but the file is now %s.',
                    $index,
                    $path,
                    (string) $approval['approved_sha256'],
                    $current[$path]['sha256'],
                ),
            );
        }
    }

    public function test_gate_reports_the_exact_path_and_difference_for_every_drift_kind(): void
    {
        $baseline = [
            'resources/css/admin/glass.css' => ['sha256' => str_repeat('a', 64), 'bytes' => 100],
            'resources/css/teacher/hero.css' => ['sha256' => str_repeat('b', 64), 'bytes' => 200],
        ];
        $current = [
            'resources/css/admin/glass.css' => ['sha256' => str_repeat('c', 64), 'bytes' => 140],
            'resources/css/teacher/hero-v2.css' => ['sha256' => str_repeat('d', 64), 'bytes' => 200],
        ];

        $differences = FrozenAreaBaseline::differences($baseline, $current);

        $byType = [];
        foreach ($differences as $difference) {
            $byType[$difference['type']] = $difference;
        }

        $types = array_values(array_unique(array_map(
            static fn (array $difference): string => $difference['type'],
            $differences,
        )));
        sort($types);

        $this->assertSame(['byte_difference', 'missing', 'unrecorded_path'], $types);

        $this->assertStringContainsString('resources/css/teacher/hero.css', FrozenAreaBaseline::describeDifference($byType['missing']));
        $this->assertStringContainsString('missing, renamed or moved', FrozenAreaBaseline::describeDifference($byType['missing']));
        $this->assertStringContainsString('size delta +40', FrozenAreaBaseline::describeDifference($byType['byte_difference']));
        $this->assertStringContainsString('resources/css/teacher/hero-v2.css', FrozenAreaBaseline::describeDifference($byType['unrecorded_path']));

        // An unrecorded exception fails, an incomplete record does not rescue it,
        // and only a complete matching record clears the difference.
        $this->assertCount(3, FrozenAreaBaseline::unapprovedDifferences($differences, []));

        $incomplete = [[
            'path' => 'resources/css/admin/glass.css',
            'previous_sha256' => str_repeat('a', 64),
            'approved_sha256' => str_repeat('c', 64),
            'reason' => '',
            'approval_reference' => 'PO-1',
        ]];
        $this->assertCount(3, FrozenAreaBaseline::unapprovedDifferences($differences, $incomplete));

        $approved = [[
            'path' => 'resources/css/admin/glass.css',
            'previous_sha256' => str_repeat('a', 64),
            'approved_sha256' => str_repeat('c', 64),
            'reason' => 'security defect fix approved by the product owner',
            'approval_reference' => 'PO-1',
        ]];
        $this->assertCount(2, FrozenAreaBaseline::unapprovedDifferences($differences, $approved));
    }

    public function test_no_new_important_declaration_is_introduced_outside_the_baseline(): void
    {
        $baseline = FrozenAreaBaseline::manifest()['important'];
        $current = FrozenAreaBaseline::currentImportantInventory();

        $violations = [];

        foreach ($current as $path => $count) {
            $allowed = $baseline[$path] ?? 0;

            if ($count > $allowed) {
                $violations[] = sprintf('%s: %d `!important` declarations, baseline allows %d', $path, $count, $allowed);
            }
        }

        $this->assertSame(
            [],
            $violations,
            "New `!important` declarations are not allowed outside the existing `[x-cloak]` exception:\n - "
                . implode("\n - ", $violations),
        );

        foreach (FrozenAreaBaseline::THEME_TOKEN_FILES as $themeFile) {
            $this->assertSame(
                $baseline[$themeFile] ?? 0,
                $current[$themeFile] ?? 0,
                sprintf('Theme token file %s changed its `!important` inventory.', $themeFile),
            );
        }
    }

    public function test_theme_selector_structure_and_cascade_ownership_are_unchanged(): void
    {
        $manifest = FrozenAreaBaseline::manifest();
        $currentSelectors = FrozenAreaBaseline::currentThemeSelectorInventory();

        $this->assertSame(
            array_keys($manifest['theme_selectors']),
            array_keys($currentSelectors),
            'The Theme_Token_File set changed.',
        );

        foreach ($manifest['theme_selectors'] as $file => $selectors) {
            if (FrozenAreaBaseline::themeSelectorChangeApproved($file)) {
                continue;
            }

            $this->assertSame(
                $selectors,
                $currentSelectors[$file] ?? [],
                sprintf('Theme selector structure changed in %s.', $file),
            );
        }

        $this->assertSame(
            $manifest['theme_scoped_files'],
            FrozenAreaBaseline::currentThemeScopedFiles(),
            sprintf(
                'Only the approved stylesheet may scope rules with "%s"; a new or removed theme-scoped file changes the frozen cascade.',
                FrozenAreaBaseline::THEME_SELECTOR_MARKER,
            ),
        );
    }
}
