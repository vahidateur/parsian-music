<?php

namespace App\DTOs;

use App\Enums\RoomResolutionEnum;
use InvalidArgumentException;
use JsonSerializable;

/** Query-free view contract for the authorized session edit screen. */
final readonly class SessionEditViewData implements JsonSerializable
{
    /** @param array<string, mixed> $values @param array<string, mixed> $relation_options @param array<int, RoomOptionData> $room_options @param array<string, bool> $policy_flags */
    public function __construct(
        public int|string $session_id,
        public array $values = [],
        public array $relation_options = [],
        public array $room_options = [],
        RoomResolutionEnum|string|null $room_resolution = null,
        public array $policy_flags = [],
        public ?FilterContext $return_context = null,
    ) {
        if (is_int($session_id) ? $session_id <= 0 : trim($session_id) === '') {
            throw new InvalidArgumentException('Session ID must be a stable identifier.');
        }
        foreach ($room_options as $option) {
            if (! $option instanceof RoomOptionData) {
                throw new InvalidArgumentException('Room options must be RoomOptionData instances.');
            }
        }
        foreach ($policy_flags as $ability => $allowed) {
            if (! is_string($ability) || ! is_bool($allowed)) {
                throw new InvalidArgumentException('Session policy flags must map names to booleans.');
            }
        }
        $this->room_resolution = $room_resolution === null ? null : ($room_resolution instanceof RoomResolutionEnum
            ? $room_resolution
            : RoomResolutionEnum::from(trim($room_resolution)));
    }

    public readonly ?RoomResolutionEnum $room_resolution;

    public function allows(string $ability): bool
    {
        return $this->policy_flags[$ability] ?? false;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'session_id' => $this->session_id,
            'values' => $this->values,
            'relation_options' => $this->relation_options,
            'room_options' => $this->room_options,
            'room_resolution' => $this->room_resolution?->value,
            'policy_flags' => $this->policy_flags,
            'return_context' => $this->return_context?->toArray(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
