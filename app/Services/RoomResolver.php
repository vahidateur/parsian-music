<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RoomResolutionEnum;
use App\Models\Room;
use App\Support\PersianTextNormalizer;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Resolves persisted room names for the legacy ClassSession.room contract.
 *
 * Room records are authoritative for whether a name is selectable, historical,
 * or unresolved. This service never creates a Room or substitutes a fallback.
 */
final class RoomResolver
{
    /** The current class_sessions.room column is a string(20). */
    public const LEGACY_ROOM_NAME_MAX_LENGTH = 20;

    public function resolve(?string $name): ?RoomResolutionEnum
    {
        $normalized = $this->normalize($name);
        if ($normalized === null) {
            return null;
        }

        return $this->resolutionFor($this->find($normalized));
    }

    /**
     * Resolve a batch of persisted room names with one parameterized query.
     * Null/blank values have a null resolution and are omitted because an
     * associative name map cannot represent a null key.
     *
     * @param array<int, string|null> $names
     * @return array<string, RoomResolutionEnum>
     */
    public function resolveMany(array $names): array
    {
        $normalized = $this->normalizedUniqueNames($names);
        if ($normalized === []) {
            return [];
        }

        $rooms = $this->findMany($normalized);

        return array_combine(
            $normalized,
            array_map(
                fn (string $name): RoomResolutionEnum => $this->resolutionFor($rooms[$name] ?? null),
                $normalized,
            ),
        ) ?: [];
    }

    /**
     * Find one Room by its canonical persisted name. Matching remains exact;
     * no case-insensitive or partial lookup is performed.
     */
    public function find(?string $name): ?Room
    {
        $normalized = $this->normalize($name);
        if ($normalized === null) {
            return null;
        }

        return Room::query()
            ->where('name', $normalized)
            ->first(['id', 'name', 'capacity', 'is_active']);
    }

    /**
     * Find a batch of Rooms by exact canonical names with one query.
     *
     * @param array<int, string|null> $names
     * @return array<string, Room>
     */
    public function findMany(array $names): array
    {
        $normalized = $this->normalizedUniqueNames($names);
        if ($normalized === []) {
            return [];
        }

        /** @var EloquentCollection<int, Room> $rooms */
        $rooms = Room::query()
            ->whereIn('name', $normalized)
            ->get(['id', 'name', 'capacity', 'is_active'])
            ->keyBy('name');

        return $rooms->all();
    }

    /**
     * Return the exact active Room used for a new session/replacement.
     * Capacity is checked separately so callers can return a room-specific
     * validation error rather than silently truncating the legacy string.
     */
    public function active(string $name): ?Room
    {
        $room = $this->find($name);

        return $room !== null && $room->is_active ? $room : null;
    }

    /** Whether the canonical name is an exact match to an active Room. */
    public function isExactActive(?string $name): bool
    {
        return $this->active((string) $name) !== null;
    }

    /**
     * Whether the value can be persisted in ClassSession's legacy room column.
     * Null represents the nullable column and is therefore compatible.
     */
    public function fitsLegacyCapacity(?string $name): bool
    {
        $normalized = $this->normalize($name);

        return $normalized === null || mb_strlen($normalized) <= self::LEGACY_ROOM_NAME_MAX_LENGTH;
    }

    /** Return the canonical name or null without changing persisted data. */
    public function normalize(?string $name): ?string
    {
        if (! is_string($name)) {
            return null;
        }

        return PersianTextNormalizer::text($name);
    }

    /** @param array<int, string|null> $names @return array<int, string> */
    private function normalizedUniqueNames(array $names): array
    {
        $result = [];
        $seen = [];

        foreach ($names as $name) {
            $normalized = $this->normalize($name);
            if ($normalized === null || isset($seen[$normalized])) {
                continue;
            }

            $seen[$normalized] = true;
            $result[] = $normalized;
        }

        return $result;
    }

    private function resolutionFor(?Room $room): RoomResolutionEnum
    {
        if ($room === null) {
            return RoomResolutionEnum::UnresolvedLegacy;
        }

        return $room->is_active
            ? RoomResolutionEnum::ResolvedActive
            : RoomResolutionEnum::ResolvedInactive;
    }
}
