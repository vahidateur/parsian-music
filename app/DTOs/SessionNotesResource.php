<?php

namespace App\DTOs;

use InvalidArgumentException;
use JsonSerializable;

/** Immutable response for the persisted nullable session-notes value. */
final readonly class SessionNotesResource implements JsonSerializable
{
    public function __construct(
        public int|string $session_id,
        ?string $notes,
        string $notes_display,
        public ?string $updated_at = null,
        public bool $can_update = false,
        ?string $message = null,
    ) {
        if (is_int($session_id) ? $session_id <= 0 : trim($session_id) === '') {
            throw new InvalidArgumentException('Session ID must be a stable identifier.');
        }
        $this->notes = $notes === null || trim($notes) === '' ? null : $notes;
        if (trim($notes_display) === '') {
            throw new InvalidArgumentException('Notes display text is required.');
        }
        $this->notes_display = $notes_display;
        $this->message = $message === null ? null : (trim($message) === '' ? throw new InvalidArgumentException('Message cannot be empty.') : $message);
    }

    public readonly ?string $notes;
    public readonly string $notes_display;
    public readonly ?string $message;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'session_id' => $this->session_id,
            'notes' => $this->notes,
            'notes_display' => $this->notes_display,
            'updated_at' => $this->updated_at,
            'can_update' => $this->can_update,
            'message' => $this->message,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
