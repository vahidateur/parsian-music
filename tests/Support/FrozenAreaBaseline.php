<?php

declare(strict_types=1);

namespace Tests\Support;

use FilesystemIterator;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/**
 * Byte-level baseline for the Frozen_Area preservation gate.
 *
 * Owns the frozen path set, the approved baseline manifest, the approval-exception
 * record store and the pure comparison helpers used by the preservation test.
 * Nothing in this class writes to a frozen path; it only reads and fingerprints.
 *
 * Requirements: 13.3, 13.7, 14.1, 14.2, 14.5, 14.6, 14.7, 14.8
 */
final class FrozenAreaBaseline
{
    /**
     * The exact frozen preservation set (requirement 14.1).
     * A trailing slash marks a directory whose whole tree is frozen.
     *
     * @var list<string>
     */
    public const FROZEN_PATHS = [
        'resources/views/auth/login.blade.php',
        'resources/views/components/auth/',
        'resources/css/teacher/hero.css',
        'resources/css/teacher/biography.css',
        'resources/views/components/ui/teacher/',
        'resources/css/design-tokens.css',
        'resources/css/semantic-tokens.css',
        'resources/css/admin/tokens.css',
        'resources/css/admin/glass.css',
    ];

    /**
     * Theme_Token_File set (requirement 13.3).
     *
     * @var list<string>
     */
    public const THEME_TOKEN_FILES = [
        'resources/css/design-tokens.css',
        'resources/css/semantic-tokens.css',
        'resources/css/admin/tokens.css',
        'resources/css/admin/glass.css',
    ];

    /**
     * Directories scanned for new `!important` declarations (requirement 13.7).
     *
     * @var list<string>
     */
    public const IMPORTANT_SCAN_DIRECTORIES = [
        'resources/css',
        'resources/js',
        'resources/views/admin',
        'resources/views/auth',
        'resources/views/components',
        'resources/views/layouts',
    ];

    /**
     * Directory scanned for theme-selector cascade leakage (requirement 14.6).
     */
    public const THEME_CASCADE_SCAN_DIRECTORY = 'resources/css';

    public const THEME_SELECTOR_MARKER = '[data-admin-theme';

    /**
     * @var list<string>
     */
    private const SCANNED_EXTENSIONS = ['css', 'js', 'php'];

    /**
     * @var list<string>
     */
    private const APPROVAL_FIELDS = ['path', 'previous_sha256', 'approved_sha256', 'reason', 'approval_reference'];

    public static function basePath(string $relative = ''): string
    {
        $base = dirname(__DIR__, 2);

        return $relative === '' ? $base : $base . '/' . ltrim($relative, '/');
    }

    public static function manifestPath(): string
    {
        return self::basePath('tests/Support/frozen-area-baseline.json');
    }

    public static function approvalsPath(): string
    {
        return self::basePath('tests/Support/frozen-area-approvals.json');
    }

    /**
     * The approved baseline manifest.
     *
     * @return array{files: array<string, array{sha256: string, bytes: int}>, important: array<string, int>, theme_selectors: array<string, list<string>>, theme_scoped_files: list<string>}
     */
    public static function manifest(): array
    {
        /** @var array<string, mixed> $manifest */
        $manifest = self::readJson(self::manifestPath());

        foreach (['files', 'important', 'theme_selectors', 'theme_scoped_files'] as $section) {
            if (! array_key_exists($section, $manifest) || ! is_array($manifest[$section])) {
                throw new RuntimeException(sprintf(
                    'Frozen baseline manifest is missing the "%s" section: %s',
                    $section,
                    self::manifestPath(),
                ));
            }
        }

        /** @phpstan-ignore-next-line the shape is validated above */
        return $manifest;
    }

    /**
     * Approval-exception records (requirement 14.7).
     *
     * @return list<array<string, mixed>>
     */
    public static function approvals(): array
    {
        /** @var array<string, mixed> $store */
        $store = self::readJson(self::approvalsPath());

        if (! array_key_exists('approvals', $store) || ! is_array($store['approvals'])) {
            throw new RuntimeException(sprintf(
                'Frozen approval store must expose an "approvals" list: %s',
                self::approvalsPath(),
            ));
        }

        /** @var list<array<string, mixed>> $records */
        $records = array_values($store['approvals']);

        return $records;
    }

    /**
     * Frozen paths that are missing, so a rename or move is reported explicitly.
     *
     * @return list<string>
     */
    public static function missingFrozenPaths(): array
    {
        $missing = [];

        foreach (self::FROZEN_PATHS as $path) {
            $absolute = self::basePath($path);

            if (str_ends_with($path, '/')) {
                if (! is_dir($absolute) || self::filesIn($absolute) === []) {
                    $missing[] = $path;
                }

                continue;
            }

            if (! is_file($absolute)) {
                $missing[] = $path;
            }
        }

        return $missing;
    }

    /**
     * Byte fingerprint of every file inside the frozen preservation set.
     *
     * @return array<string, array{sha256: string, bytes: int}>
     */
    public static function currentFileFingerprints(): array
    {
        $fingerprints = [];

        foreach (self::FROZEN_PATHS as $path) {
            $absolute = self::basePath($path);

            $files = str_ends_with($path, '/')
                ? self::filesIn($absolute)
                : (is_file($absolute) ? [$absolute] : []);

            foreach ($files as $file) {
                $relative = self::toRelative($file);
                $fingerprints[$relative] = [
                    'sha256' => (string) hash_file('sha256', $file),
                    'bytes' => (int) filesize($file),
                ];
            }
        }

        ksort($fingerprints);

        return $fingerprints;
    }

    /**
     * `!important` occurrences per scanned file, excluding files with none.
     *
     * @return array<string, int>
     */
    public static function currentImportantInventory(): array
    {
        $inventory = [];

        foreach (self::IMPORTANT_SCAN_DIRECTORIES as $directory) {
            foreach (self::filesIn(self::basePath($directory)) as $file) {
                $count = substr_count(self::read($file), '!important');

                if ($count > 0) {
                    $inventory[self::toRelative($file)] = $count;
                }
            }
        }

        ksort($inventory);

        return $inventory;
    }

    /**
     * Top-level selector text of every rule in each Theme_Token_File.
     *
     * @return array<string, list<string>>
     */
    public static function currentThemeSelectorInventory(): array
    {
        $inventory = [];

        foreach (self::THEME_TOKEN_FILES as $path) {
            $absolute = self::basePath($path);

            if (! is_file($absolute)) {
                continue;
            }

            $inventory[$path] = self::selectorsIn(self::read($absolute));
        }

        ksort($inventory);

        return $inventory;
    }

    /**
     * Stylesheets that scope rules to an admin theme marker (requirement 14.6).
     *
     * @return list<string>
     */
    public static function currentThemeScopedFiles(): array
    {
        $files = [];

        foreach (self::filesIn(self::basePath(self::THEME_CASCADE_SCAN_DIRECTORY)) as $file) {
            if (str_contains(self::read($file), self::THEME_SELECTOR_MARKER)) {
                $files[] = self::toRelative($file);
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Pure comparison between a recorded baseline and a current fingerprint set.
     *
     * @param  array<string, array{sha256: string, bytes: int}>  $baseline
     * @param  array<string, array{sha256: string, bytes: int}>  $current
     * @return list<array{path: string, type: string, expected_sha256: ?string, actual_sha256: ?string, expected_bytes: ?int, actual_bytes: ?int}>
     */
    public static function differences(array $baseline, array $current): array
    {
        $differences = [];

        foreach ($baseline as $path => $recorded) {
            if (! array_key_exists($path, $current)) {
                $differences[] = [
                    'path' => $path,
                    'type' => 'missing',
                    'expected_sha256' => $recorded['sha256'],
                    'actual_sha256' => null,
                    'expected_bytes' => $recorded['bytes'],
                    'actual_bytes' => null,
                ];

                continue;
            }

            if ($current[$path]['sha256'] !== $recorded['sha256']) {
                $differences[] = [
                    'path' => $path,
                    'type' => 'byte_difference',
                    'expected_sha256' => $recorded['sha256'],
                    'actual_sha256' => $current[$path]['sha256'],
                    'expected_bytes' => $recorded['bytes'],
                    'actual_bytes' => $current[$path]['bytes'],
                ];
            }
        }

        foreach ($current as $path => $actual) {
            if (! array_key_exists($path, $baseline)) {
                $differences[] = [
                    'path' => $path,
                    'type' => 'unrecorded_path',
                    'expected_sha256' => null,
                    'actual_sha256' => $actual['sha256'],
                    'expected_bytes' => null,
                    'actual_bytes' => $actual['bytes'],
                ];
            }
        }

        usort($differences, static fn (array $left, array $right): int => [$left['path'], $left['type']] <=> [$right['path'], $right['type']]);

        return $differences;
    }

    /**
     * Differences without a matching approval record (requirement 14.7).
     *
     * @param  list<array<string, mixed>>  $differences
     * @param  list<array<string, mixed>>  $approvals
     * @return list<array<string, mixed>>
     */
    public static function unapprovedDifferences(array $differences, array $approvals): array
    {
        return array_values(array_filter(
            $differences,
            static function (array $difference) use ($approvals): bool {
                foreach ($approvals as $approval) {
                    if (self::approvalCovers($approval, $difference)) {
                        return false;
                    }
                }

                return true;
            },
        ));
    }

    /**
     * @param  array<string, mixed>  $approval
     * @param  array<string, mixed>  $difference
     */
    public static function approvalCovers(array $approval, array $difference): bool
    {
        if (self::validateApproval($approval) !== []) {
            return false;
        }

        return $approval['path'] === $difference['path']
            && $approval['previous_sha256'] === $difference['expected_sha256']
            && $approval['approved_sha256'] === $difference['actual_sha256'];
    }

    /**
     * Structural problems in a single approval record; empty means well formed.
     *
     * @param  array<string, mixed>  $approval
     * @return list<string>
     */
    public static function validateApproval(array $approval): array
    {
        $problems = [];

        foreach (self::APPROVAL_FIELDS as $field) {
            if (! array_key_exists($field, $approval)) {
                $problems[] = sprintf('missing field "%s"', $field);
            }
        }

        if ($problems !== []) {
            return $problems;
        }

        foreach (['path', 'reason', 'approval_reference'] as $field) {
            if (! is_string($approval[$field]) || trim($approval[$field]) === '') {
                $problems[] = sprintf('field "%s" must be a non-empty string', $field);
            }
        }

        foreach (['previous_sha256', 'approved_sha256'] as $field) {
            $value = $approval[$field];

            if ($value !== null && (! is_string($value) || preg_match('/^[0-9a-f]{64}$/', $value) !== 1)) {
                $problems[] = sprintf('field "%s" must be null or a lowercase sha256 hash', $field);
            }
        }

        if (is_string($approval['path']) && ! self::isFrozenPath($approval['path'])) {
            $problems[] = sprintf('path "%s" is outside the frozen preservation set', $approval['path']);
        }

        return $problems;
    }

    public static function isFrozenPath(string $relativePath): bool
    {
        foreach (self::FROZEN_PATHS as $frozen) {
            if (str_ends_with($frozen, '/') ? str_starts_with($relativePath, $frozen) : $relativePath === $frozen) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether an approved frozen-file byte change also authorizes its selector
     * ownership reconciliation. The approval must match the current bytes and
     * the recorded baseline bytes for the same frozen path.
     */
    public static function themeSelectorChangeApproved(string $path): bool
    {
        $manifest = self::manifest();
        $baselineHash = $manifest['files'][$path]['sha256'] ?? null;
        $absolute = self::basePath($path);
        $currentHash = is_file($absolute) ? (string) hash_file('sha256', $absolute) : null;

        if (! is_string($baselineHash) || $currentHash === null) {
            return false;
        }

        foreach (self::approvals() as $approval) {
            if (self::validateApproval($approval) !== []) {
                continue;
            }

            if (
                $approval['path'] === $path
                && $approval['previous_sha256'] === $baselineHash
                && $approval['approved_sha256'] === $currentHash
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Human-readable failure line naming the exact path and difference (requirement 14.5).
     *
     * @param  array<string, mixed>  $difference
     */
    public static function describeDifference(array $difference): string
    {
        return match ($difference['type']) {
            'missing' => sprintf(
                '%s: frozen path is missing, renamed or moved (expected sha256 %s, %d bytes)',
                $difference['path'],
                $difference['expected_sha256'],
                $difference['expected_bytes'],
            ),
            'unrecorded_path' => sprintf(
                '%s: frozen path is not part of the approved baseline (actual sha256 %s, %d bytes)',
                $difference['path'],
                $difference['actual_sha256'],
                $difference['actual_bytes'],
            ),
            default => sprintf(
                '%s: bytes changed (expected sha256 %s / %d bytes, actual sha256 %s / %d bytes, size delta %+d)',
                $difference['path'],
                $difference['expected_sha256'],
                $difference['expected_bytes'],
                $difference['actual_sha256'],
                $difference['actual_bytes'],
                $difference['actual_bytes'] - $difference['expected_bytes'],
            ),
        };
    }

    /**
     * Regenerate the manifest payload from the current working tree.
     * Only an explicitly approved change may persist this output (requirement 14.8).
     *
     * @return array<string, mixed>
     */
    public static function buildManifest(): array
    {
        return [
            'frozen_paths' => self::FROZEN_PATHS,
            'files' => self::currentFileFingerprints(),
            'important' => self::currentImportantInventory(),
            'theme_selectors' => self::currentThemeSelectorInventory(),
            'theme_scoped_files' => self::currentThemeScopedFiles(),
        ];
    }

    /**
     * @return list<string>
     */
    private static function selectorsIn(string $css): array
    {
        $withoutComments = (string) preg_replace('#/\*.*?\*/#s', '', $css);

        preg_match_all('/(^|[};])\s*([^{};@\n][^{};]*)\{/m', $withoutComments, $matches);

        $selectors = [];

        foreach ($matches[2] ?? [] as $selector) {
            $normalized = trim((string) preg_replace('/\s+/', ' ', $selector));

            if ($normalized !== '') {
                $selectors[] = $normalized;
            }
        }

        sort($selectors);

        return array_values(array_unique($selectors));
    }

    /**
     * @return list<string>
     */
    private static function filesIn(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        );

        $files = [];

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), self::SCANNED_EXTENSIONS, true)) {
                $files[] = str_replace('\\', '/', $file->getPathname());
            }
        }

        sort($files);

        return $files;
    }

    private static function toRelative(string $absolutePath): string
    {
        $base = str_replace('\\', '/', self::basePath()) . '/';

        return str_starts_with(str_replace('\\', '/', $absolutePath), $base)
            ? substr(str_replace('\\', '/', $absolutePath), strlen($base))
            : str_replace('\\', '/', $absolutePath);
    }

    private static function read(string $path): string
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read %s.', $path));
        }

        return $contents;
    }

    /**
     * @return array<string, mixed>
     */
    private static function readJson(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException(sprintf('Frozen baseline file is missing: %s', $path));
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode(self::read($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(sprintf('%s contains invalid JSON: %s', $path, $exception->getMessage()));
        }

        return $decoded;
    }
}
