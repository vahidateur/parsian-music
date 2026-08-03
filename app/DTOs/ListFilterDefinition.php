<?php

namespace App\DTOs;

/**
 * Server-side contract for a single allow-listed list filter.
 *
 * Raw user input is never trusted: a submitted value must cast to the declared
 * type and (when `allowed` is not empty) belong to the declared value set.
 * Anything else is ignored and the documented default is used instead.
 */
final readonly class ListFilterDefinition
{
    public const TYPE_STRING = 'string';

    public const TYPE_INT = 'int';

    public const TYPE_BOOL = 'bool';

    /**
     * @param 'string'|'int'|'bool' $type
     * @param array<int, string|int|bool> $allowed empty means "any value of the declared type"
     * @param string|int|bool|null $default null means "filter absent from the context"
     */
    public function __construct(
        public string $name,
        public string $type = self::TYPE_STRING,
        public array $allowed = [],
        public string|int|bool|null $default = null,
    ) {}

    /**
     * Cast and validate a raw submitted value.
     *
     * @return string|int|bool|null null when the value is not acceptable
     */
    public function accept(mixed $value): string|int|bool|null
    {
        $casted = $this->cast($value);

        if ($casted === null) {
            return null;
        }

        if ($this->allowed !== [] && ! in_array($casted, $this->allowed, true)) {
            return null;
        }

        return $casted;
    }

    private function cast(mixed $value): string|int|bool|null
    {
        if (is_array($value) || $value === null) {
            return null;
        }

        return match ($this->type) {
            self::TYPE_INT => $this->castInt($value),
            self::TYPE_BOOL => $this->castBool($value),
            default => $this->castString($value),
        };
    }

    private function castInt(mixed $value): ?int
    {
        if (is_bool($value)) {
            return null;
        }

        $value = is_string($value) ? trim($value) : $value;

        return is_numeric($value) && (string) (int) $value === (string) $value
            ? (int) $value
            : null;
    }

    private function castBool(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return match (is_string($value) ? strtolower(trim($value)) : $value) {
            '1', 'true', 'on', 'yes', 1 => true,
            '0', 'false', 'off', 'no', 0 => false,
            default => null,
        };
    }

    private function castString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
