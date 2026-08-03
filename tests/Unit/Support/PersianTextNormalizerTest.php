<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\PersianTextNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Canonical normalization contract shared by Form Requests and admin Actions.
 *
 * Covers Requirements 6.6 and 6.13.
 */
class PersianTextNormalizerTest extends TestCase
{
    public function test_single_line_text_is_trimmed_and_persian_arabic_equivalents_are_canonical(): void
    {
        $this->assertSame('علی کریمی', PersianTextNormalizer::text("  علي   كريمي \t"));
        $this->assertSame('0912', PersianTextNormalizer::text('۰۹۱۲'));
    }

    public function test_internal_separators_of_a_single_line_value_are_preserved(): void
    {
        $this->assertSame('0912 345 6789', PersianTextNormalizer::text(' 0912 345 6789 '));
        $this->assertSame('(555) 123-4567', PersianTextNormalizer::text('(555) 123-4567'));
    }

    public function test_blank_values_become_null(): void
    {
        $this->assertNull(PersianTextNormalizer::text("  \t "));
        $this->assertNull(PersianTextNormalizer::multiline("\n\n  \n"));
    }

    public function test_multiline_text_keeps_line_breaks_and_trims_each_line(): void
    {
        $this->assertSame(
            "خط اول\nخط دوم",
            PersianTextNormalizer::multiline("  خط اول  \r\n   خط دوم\n")
        );
    }

    public function test_only_declared_fields_are_normalized_and_absent_keys_stay_absent(): void
    {
        $normalized = PersianTextNormalizer::fields(
            ['full_name' => '  علي ', 'password' => '  secret  ', 'age' => 12],
            ['full_name' => PersianTextNormalizer::TEXT, 'notes' => PersianTextNormalizer::MULTILINE],
        );

        $this->assertSame('علی', $normalized['full_name']);
        $this->assertSame('  secret  ', $normalized['password']);
        $this->assertSame(12, $normalized['age']);
        $this->assertArrayNotHasKey('notes', $normalized);
    }

    public function test_normalization_is_idempotent(): void
    {
        $once = PersianTextNormalizer::text(' مؤسسه   موسيقي ٠٩ ');

        $this->assertSame($once, PersianTextNormalizer::text((string) $once));
    }
}
