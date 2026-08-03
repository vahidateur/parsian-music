<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\RoomOptionData;
use App\Enums\RoomOptionModeEnum;
use App\Models\Room;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Canonical Session owner provider for persisted Room option data.
 *
 * New sessions receive active rooms only. Filter consumers may request the
 * complete persisted set so inactive rooms remain visible historically.
 */
final class RoomOptionProvider
{
    /** @return array<int, RoomOptionData> */
    public function forSessionInput(): array
    {
        return $this->options(RoomOptionModeEnum::SessionInput, true);
    }

    /** @return array<int, RoomOptionData> */
    public function forFilter(): array
    {
        return $this->options(RoomOptionModeEnum::Filter, false);
    }

    /** @return array<int, RoomOptionData> */
    public function options(RoomOptionModeEnum $mode, bool $activeOnly = false): array
    {
        $query = Room::query()->orderBy('name');
        if ($activeOnly) {
            $query->where('is_active', true);
        }

        /** @var EloquentCollection<int, Room> $rooms */
        $rooms = $query->get(['id', 'name', 'is_active']);

        return $rooms->map(static fn (Room $room): RoomOptionData => new RoomOptionData(
            id: $room->id,
            name: $room->name,
            is_active: (bool) $room->is_active,
            mode: $mode,
        ))->all();
    }
}
