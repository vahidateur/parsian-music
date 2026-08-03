<?php

namespace App\DTOs;

use App\Enums\RoomResolutionEnum;
use App\Enums\SessionStatusEnum;
use InvalidArgumentException;
use JsonSerializable;

/** Immutable, persisted-data-only FullCalendar event contract. */
final readonly class CalendarEventData implements JsonSerializable
{
    public function __construct(
        public int|string $id,
        public string $title,
        public string $start,
        public string $end,
        SessionStatusEnum|string $status,
        public ?string $status_label = null,
        public string $student_name = '',
        public string $teacher_name = '',
        public string $instrument_name = '',
        public ?string $room = null,
        RoomResolutionEnum|string|null $room_resolution = null,
        public int|string|null $room_id = null,
        public bool $can_update_notes = false,
        public int $duration_minutes = 0,
        public int|string|null $enrollment_id = null,
        public ?string $notes = null,
        public ?string $notes_updated_at = null,
        public ?string $session_date = null,
    ) {
        if (is_int($id) ? $id <= 0 : trim($id) === '') {
            throw new InvalidArgumentException('Calendar event ID must be a stable identifier.');
        }
        if (trim($title) === '' || trim($start) === '' || trim($end) === '') {
            throw new InvalidArgumentException('Calendar event title and date bounds are required.');
        }
        if ($duration_minutes < 1) {
            throw new InvalidArgumentException('Calendar event duration must be positive.');
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
            'id' => $this->id,
            'title' => $this->title,
            'start' => $this->start,
            'end' => $this->end,
            'status' => $this->status->value,
            'statusLabel' => $this->status_label,
            'studentName' => $this->student_name,
            'teacherName' => $this->teacher_name,
            'instrumentName' => $this->instrument_name,
            'room' => $this->room,
            'roomResolution' => $this->room_resolution?->value,
            'room_id' => $this->room_id,
            'canUpdateNotes' => $this->can_update_notes,
            'extendedProps' => [
                'enrollment_id' => $this->enrollment_id,
                'duration_minutes' => $this->duration_minutes,
                'notes' => $this->notes,
                'notes_updated_at' => $this->notes_updated_at,
                'session_date' => $this->session_date,
            ],
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
