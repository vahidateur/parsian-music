<?php

namespace App\DTOs;

/**
 * Everything a Blade template needs to render one Record_Detail screen.
 *
 * Only persisted values reach this DTO; an absent value is rendered through the
 * localized placeholder instead of a fabricated substitute. Action permissions
 * are display-only flags — every state-changing endpoint re-evaluates the same
 * named ability server-side.
 */
final readonly class RecordDetailData
{
    /**
     * @param array<int, RecordDetailSection> $sections ordered detail sections
     * @param array<string, bool> $policy_flags abilities the current actor holds for this record
     * @param string $placeholder localized text for an absent persisted value
     */
    public function __construct(
        public string $entity,
        public int|string $id,
        public string $label,
        public ?string $status = null,
        public ?string $status_label = null,
        public array $sections = [],
        public array $policy_flags = [],
        public string $placeholder = '',
    ) {}

    public function allows(string $ability): bool
    {
        return $this->policy_flags[$ability] ?? false;
    }

    public function section(string $id): ?RecordDetailSection
    {
        foreach ($this->sections as $section) {
            if ($section->id === $id) {
                return $section;
            }
        }

        return null;
    }

    /**
     * Renderable text for a persisted value: the value itself, or the localized
     * placeholder when the record simply has no value for that field.
     */
    public function display(?string $value): string
    {
        return ($value === null || $value === '') ? $this->placeholder : $value;
    }
}
