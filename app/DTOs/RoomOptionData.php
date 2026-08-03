<?php

namespace App\DTOs;

use App\Enums\RoomOptionModeEnum;
use InvalidArgumentException;
use JsonSerializable;

/** Persisted room option; IDs are never synthetic or display-only. */
final readonly class RoomOptionData implements JsonSerializable
{
    public function __construct(
        public int|string $id,
        string $name,
        public bool $is_active,
        RoomOptionModeEnum|string $mode,
    ) {
        if (is_int($id) ? $id <= 0 : trim($id) === '') {
            throw new InvalidArgumentException('Room option ID must be a stable identifier.');
        }
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Room option name is required.');
        }
        $this->name = $name;
        $this->mode = $mode instanceof RoomOptionModeEnum ? $mode : RoomOptionModeEnum::from(trim($mode));
    }

    public readonly string $name;
    public readonly RoomOptionModeEnum $mode;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'mode' => $this->mode->value,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
