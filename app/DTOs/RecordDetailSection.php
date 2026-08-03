<?php

namespace App\DTOs;

/**
 * One section of a Record_Detail screen.
 *
 * The section carries a stable machine-readable identifier so a test (or a
 * deep link) can address it without depending on rendered copy, plus already
 * resolved persisted values. Scalar values live in `fields`; related
 * operational data lives in `rows` and reuses the list row contract, so a Blade
 * template never touches an Eloquent relation.
 */
final readonly class RecordDetailSection
{
    /**
     * @param string $id stable machine-readable section identifier
     * @param array<int, array{label: string, value: string|null, dir: string|null, multiline: bool}> $fields
     * @param array<int, OperationalRowData> $rows related operational records
     * @param string|null $empty_message localized Empty_State message for this section
     */
    public function __construct(
        public string $id,
        public string $title,
        public array $fields = [],
        public array $rows = [],
        public ?string $empty_message = null,
    ) {}

    /**
     * @return array{label: string, value: string|null, dir: string|null, multiline: bool}
     */
    public static function field(string $label, ?string $value, ?string $dir = null, bool $multiline = false): array
    {
        return [
            'label' => $label,
            'value' => ($value === null || $value === '') ? null : $value,
            'dir' => $dir,
            'multiline' => $multiline,
        ];
    }

    public function isEmpty(): bool
    {
        return $this->fields === [] && $this->rows === [];
    }

    public function rowCount(): int
    {
        return count($this->rows);
    }
}
