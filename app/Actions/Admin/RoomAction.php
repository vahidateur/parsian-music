<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\Room;
use App\Support\PersianTextNormalizer;
use Illuminate\Support\Facades\DB;

/**
 * Room mutations.
 *
 * A new room starts active; that default belongs to the domain, not to the form.
 *
 * Requirements: 6.4, 6.6, 6.9, 6.10, 6.13, 16.3
 */
final class RoomAction
{
    /**
     * Canonical form of every persisted text field, shared with RoomRequest.
     *
     * @var array<string, string>
     */
    public const NORMALIZED_FIELDS = ['name' => PersianTextNormalizer::TEXT];

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Room
    {
        return Room::create(PersianTextNormalizer::fields($data, self::NORMALIZED_FIELDS) + ['is_active' => true]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Room $room, array $data): Room
    {
        $room->update(PersianTextNormalizer::fields($data, self::NORMALIZED_FIELDS));

        return $room;
    }

    public function delete(Room $room): void
    {
        DB::transaction(static function () use ($room): void {
            $room->delete();
        });
    }

    public function toggle(Room $room): Room
    {
        $room->update(['is_active' => ! $room->is_active]);

        return $room;
    }
}
