<?php

namespace App\DTOs;

use App\Enums\RoomResolutionEnum;
use App\Enums\SessionStatusEnum;
use InvalidArgumentException;
use JsonSerializable;

/** Immutable response contract for persisted, editable ClassSession fields. */
final readonly class SessionEditResource implements JsonSerializable
{
    /** @param array<string, mixed> $relation @param array<int, string> $protected_fields */
    public function __construct(
        public int|string $session_id,
        public int|string $student_id,
        public int|string $teacher_id,
        public int|string $instrument_id,
        public string $session_date,
        public string $start_time,
        public int $duration_minutes,
        SessionStatusEnum|string $status,
        public ?string $room = null,
        public ?string $notes = null,
        public array $relation = [],
        public array $protected_fields = ['enrollment_id', 'session_fee', 'discount', 'recurring_schedule_id'],
        RoomResolutionEnum|string|null $room_resolution = null,
        public int|string|null $room_id = null,
        public ?string $updated_at = null,
    ) {
        foreach ([$session_id, $student_id, $teacher_id, $instrument_id] as $id) {
            if (is_int($id) ? $id <= 0 : trim($id) === '') {
                throw new InvalidArgumentException('Session relation IDs must be stable identifiers.');
            }
        }
        if ($duration_minutes < 1) {
            throw new InvalidArgumentException('Session duration must be positive.');
        }
        $this->status = $status instanceof SessionStatusEnum ? $status : SessionStatusEnum::from(trim($status));
        $this->room_resolution = $room_resolution === null ? null : ($room_resolution instanceof RoomResolutionEnum
            ? $room_resolution
            : RoomResolutionEnum::from(trim($room_resolution)));
    }

    public readonly SessionStatusEnum $status;
    public readonly ?RoomResolutionEnum $room_resolution;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'session_id' => $this->session_id,
            'student_id' => $this->student_id,
            'teacher_id' => $this->teacher_id,
            'instrument_id' => $this->instrument_id,
            'session_date' => $this->session_date,
            'start_time' => $this->start_time,
            'duration_minutes' => $this->duration_minutes,
            'status' => $this->status->value,
            'room' => $this->room,
            'notes' => $this->notes,
            'relation' => $this->relation,
            'protected_fields' => $this->protected_fields,
            'room_resolution' => $this->room_resolution?->value,
            'room_id' => $this->room_id,
            'updated_at' => $this->updated_at,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
