<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Exceptions\RecordInUseException;
use App\Models\Instrument;
use App\Support\PersianTextNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Instrument mutations.
 *
 * Owns the three instrument business rules that used to live in the controller:
 * the English name falls back to the Persian name, the slug is derived and made
 * unique, and an instrument still used by an enrollment or a teacher is never
 * deleted. Creation reads the existing slugs and then writes, so it runs inside
 * one transaction.
 *
 * Requirements: 6.4, 6.6, 6.9, 6.10, 6.13, 16.3
 */
final class InstrumentAction
{
    /**
     * Canonical form of every persisted text field, shared with InstrumentRequest.
     *
     * @var array<string, string>
     */
    public const NORMALIZED_FIELDS = [
        'name_fa' => PersianTextNormalizer::TEXT,
        'name' => PersianTextNormalizer::TEXT,
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Instrument
    {
        $data = PersianTextNormalizer::fields($data, self::NORMALIZED_FIELDS);
        $englishName = $this->englishName($data);

        return DB::transaction(fn (): Instrument => Instrument::create([
            'name' => $englishName,
            'name_fa' => $data['name_fa'],
            'slug' => $this->uniqueSlug($englishName),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Instrument $instrument, array $data): Instrument
    {
        $data = PersianTextNormalizer::fields($data, self::NORMALIZED_FIELDS);

        $instrument->update([
            'name' => $this->englishName($data),
            'name_fa' => $data['name_fa'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return $instrument;
    }

    /**
     * @throws RecordInUseException when a teacher or an enrollment still uses it.
     */
    public function delete(Instrument $instrument): void
    {
        if ($instrument->enrollments()->exists() || $instrument->teachers()->exists()) {
            throw new RecordInUseException(__('admin.instrument_in_use_error'));
        }

        DB::transaction(static function () use ($instrument): void {
            $instrument->delete();
        });
    }

    public function toggle(Instrument $instrument): Instrument
    {
        $instrument->update(['is_active' => ! $instrument->is_active]);

        return $instrument;
    }

    /**
     * The English name is optional on the form and falls back to the Persian name.
     *
     * @param  array<string, mixed>  $data
     */
    private function englishName(array $data): string
    {
        $name = is_string($data['name'] ?? null) ? $data['name'] : '';

        return $name !== '' ? $name : (string) $data['name_fa'];
    }

    private function uniqueSlug(string $englishName): string
    {
        $baseSlug = Str::slug($englishName);
        $slug = $baseSlug;
        $counter = 1;

        while (Instrument::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        return $slug;
    }
}
