<?php

namespace App\DTOs;

use App\Enums\BulkItemResultStatusEnum;
use InvalidArgumentException;
use JsonSerializable;

/** Immutable result for one processed bulk target. */
final readonly class BulkItemResultData implements JsonSerializable
{
    public function __construct(
        int|string $id,
        BulkItemResultStatusEnum|string $status,
        ?string $reason_category = null,
        ?string $reason_message = null,
        ?string $reason_identifier = null,
    ) {
        $this->id = self::identifier($id);
        $this->status = $status instanceof BulkItemResultStatusEnum
            ? $status
            : BulkItemResultStatusEnum::from(trim($status));
        $this->reason_category = self::optional($reason_category, 'reason category');
        $this->reason_message = self::optional($reason_message, 'reason message');
        $this->reason_identifier = self::optional($reason_identifier, 'reason identifier');
        if ($this->status === BulkItemResultStatusEnum::Succeeded
            && ($this->reason_category !== null || $this->reason_message !== null || $this->reason_identifier !== null)) {
            throw new InvalidArgumentException('Succeeded items cannot contain a failure reason.');
        }
        if ($this->status !== BulkItemResultStatusEnum::Succeeded
            && ($this->reason_category === null || $this->reason_message === null)) {
            throw new InvalidArgumentException('Skipped and failed items require a localized reason.');
        }
    }

    public readonly int|string $id;
    public readonly BulkItemResultStatusEnum $status;
    public readonly ?string $reason_category;
    public readonly ?string $reason_message;
    public readonly ?string $reason_identifier;

    private static function identifier(int|string $id): int|string
    {
        if (is_int($id) && $id > 0) {
            return $id;
        }
        $id = is_string($id) ? trim($id) : '';
        if ($id === '') {
            throw new InvalidArgumentException('Item result ID must be a non-empty stable identifier.');
        }
        return $id;
    }

    private static function optional(?string $value, string $label): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);
        return $value === '' ? throw new InvalidArgumentException("{$label} cannot be empty.") : $value;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'reason' => $this->reason_category === null ? null : [
                'category' => $this->reason_category,
                'message' => $this->reason_message,
                'identifier' => $this->reason_identifier,
            ],
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
