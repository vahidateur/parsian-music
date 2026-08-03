<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Canonical text normalization contract shared by list search, Form Requests and
 * the admin Actions.
 *
 * One class owns the character map so a value normalized during validation is
 * byte-identical to the value normalized before persistence; validation and
 * persistence can therefore never disagree about what was submitted.
 *
 * Field modes:
 *   - TEXT      single-line value: canonical characters, collapsed whitespace, trimmed.
 *   - MULTILINE free text: canonical characters, per-line trim, line breaks preserved.
 *
 * Internal separators of a single-line value (the spaces and dashes of a phone
 * number, for example) are preserved: only surrounding and repeated whitespace
 * is collapsed, so a normalized value still equals the stored value.
 *
 * An empty result always becomes `null`, matching the framework's
 * ConvertEmptyStringsToNull boundary.
 *
 * Requirements: 6.6, 6.13
 */
final class PersianTextNormalizer
{
    public const TEXT = 'text';

    public const MULTILINE = 'multiline';

    /**
     * Equivalent Persian/Arabic code points collapsed to a single canonical form.
     *
     * Only characters that are Arabic-only variants of a Persian letter are
     * mapped. Letters that are valid in persisted Persian data (آ, ئ, ؤ, ۀ) are
     * preserved so a normalized value still matches stored values. Zero-width
     * non-joiner (U+200C) is preserved for the same reason; only invisible
     * joiners, direction marks and BOM are removed.
     *
     * @var array<string, string>
     */
    public const CHARACTER_MAP = [
        // Arabic letters -> Persian equivalents
        'ك' => 'ک',
        'ﻙ' => 'ک',
        'ﻚ' => 'ک',
        'ي' => 'ی',
        'ى' => 'ی',
        'ﻯ' => 'ی',
        'ﻰ' => 'ی',
        'ة' => 'ه',
        'أ' => 'ا',
        'إ' => 'ا',
        'ٱ' => 'ا',
        // Arabic-Indic and extended Arabic-Indic digits -> ASCII
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        // Diacritics, tatweel and invisible characters
        'َ' => '', 'ُ' => '', 'ِ' => '', 'ً' => '', 'ٌ' => '', 'ٍ' => '',
        'ّ' => '', 'ْ' => '', 'ٔ' => '', 'ٕ' => '', 'ـ' => '',
        "\u{200B}" => '', "\u{200D}" => '', "\u{200E}" => '', "\u{200F}" => '',
        "\u{FEFF}" => '',
    ];

    /**
     * Normalize the declared fields of a submitted or validated payload.
     *
     * Keys absent from the payload stay absent, so a partial update never gains
     * a field it did not submit. Non-string values are returned untouched.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $fields  field name => mode
     * @return array<string, mixed>
     */
    public static function fields(array $data, array $fields): array
    {
        foreach ($fields as $field => $mode) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $data[$field] = self::value($data[$field], $mode);
        }

        return $data;
    }

    /**
     * Normalize one value in the given mode.
     */
    public static function value(mixed $raw, string $mode): mixed
    {
        if (! is_string($raw)) {
            return $raw;
        }

        return match ($mode) {
            self::MULTILINE => self::multiline($raw),
            default => self::text($raw),
        };
    }

    /**
     * Canonical single-line value, or null when nothing readable remains.
     */
    public static function text(string $raw): ?string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', self::canonical($raw)));

        return $value === '' ? null : $value;
    }

    /**
     * Canonical free text with line breaks preserved and each line trimmed.
     */
    public static function multiline(string $raw): ?string
    {
        $value = self::canonical($raw);
        $value = (string) preg_replace('/\R/u', "\n", $value);

        $lines = array_map(
            static fn (string $line): string => trim((string) preg_replace('/[^\S\n]+/u', ' ', $line)),
            explode("\n", $value)
        );

        $value = trim(implode("\n", $lines));

        return $value === '' ? null : $value;
    }

    /**
     * Apply the shared character map without touching whitespace.
     */
    public static function canonical(string $raw): string
    {
        return strtr($raw, self::CHARACTER_MAP);
    }
}
