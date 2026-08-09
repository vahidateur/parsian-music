<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\RoomOptionData;
use App\Enums\RoomOptionModeEnum;
use App\Models\Room;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Canonical provider for Room options backed by persisted Room records.
 *
 * New and edited sessions receive active rooms only. Filter consumers receive
 * the complete persisted set so inactive rooms remain available historically.
 * Unknown legacy ClassSession.room values are never added as options.
 */
final class RoomOptionProvider
{
    /** @return array<int, RoomOptionData> */
    public function forSessionInput(): array
    {
        return $this->options(RoomOptionModeEnum::SessionInput);
    }

    /** @return array<int, RoomOptionData> */
    public function forFilter(): array
    {
        return $this->options(RoomOptionModeEnum::Filter);
    }

    /**
     * @return array<int, RoomOptionData>
     */
    public function options(RoomOptionModeEnum $mode, bool $activeOnly = false): array
    {
        // Session-input mode is never allowed to expose inactive records, even
        // when a caller omits the optional activeOnly argument.
        $activeOnly = $mode === RoomOptionModeEnum::SessionInput || $activeOnly;

        $query = Room::query()->orderBy('name');
        if ($activeOnly) {
            $query->where('is_active', true);
        }

        /** @var EloquentCollection<int, Room> $rooms */
        $rooms = $query->get(['id', 'name', 'is_active']);

        return $rooms->map(static fn (Room $room): RoomOptionData => new RoomOptionData(
            id: $room->getKey(),
            name: $room->name,
            is_active: (bool) $room->is_active,
            mode: $mode,
        ))->values()->all();
    }
}
