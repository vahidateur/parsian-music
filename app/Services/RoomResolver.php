<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RoomResolutionEnum;
use App\Models\Room;
use Illuminate\Support\Collection;

/**
 * Resolves persisted room names for the Session owner contract.
 *
 * Room names remain the legacy ClassSession value. Room records are the
 * authority for whether a name is selectable, historical, or unresolved.
 */
final class RoomResolver
{
    public function resolve(?string $name): ?RoomResolutionEnum
    {
        $normalized = $this->normalize($name);
        if ($normalized === null) {
            return null;
        }

        $room = Room::query()->where('name', $normalized)->first(['id', 'name', 'is_active']);

        return $room === null
            ? RoomResolutionEnum::UnresolvedLegacy
            : ($room->is_active ? RoomResolutionEnum::ResolvedActive : RoomResolutionEnum::ResolvedInactive);
    }

    /** @param array<int, string|null> $names @return array<string, RoomResolutionEnum|null> */
    public function resolveMany(array $names): array
    {
        $normalized = collect($names)
            ->map(fn (?string $name): ?string => $this->normalize($name))
            ->filter()
            ->unique()
            ->values();

        if ($normalized->isEmpty()) {
            return [];
        }

        /** @var Collection<int, Room> $rooms */
        $rooms = Room::query()
            ->whereIn('name', $normalized->all())
            ->get(['id', 'name', 'is_active'])
            ->keyBy('name');

        return $normalized->mapWithKeys(function (string $name) use ($rooms): array {
            $room = $rooms->get($name);

            return [$name => $room === null
                ? RoomResolutionEnum::UnresolvedLegacy
                : ($room->is_active ? RoomResolutionEnum::ResolvedActive : RoomResolutionEnum::ResolvedInactive)];
        })->all();
    }

    public function active(string $name): ?Room
    {
        $normalized = $this->normalize($name);
        if ($normalized === null) {
            return null;
        }

        return Room::query()
            ->where('name', $normalized)
            ->where('is_active', true)
            ->first();
    }

    public function normalize(?string $name): ?string
    {
        $normalized = is_string($name) ? trim($name) : null;

        return $normalized === '' ? null : $normalized;
    }
}
