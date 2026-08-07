<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use JsonSerializable;

/** One local academy-date interval; it carries no availability policy. */
final readonly class TimeRange implements JsonSerializable
{
    private function __construct(public DateTimeImmutable $start, public DateTimeImmutable $end) {}

    public static function fromLocal(string $date, string $time, int $durationMinutes, DateTimeZone $timezone): self
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! preg_match('/^\d{2}:\d{2}$/', $time) || $durationMinutes < 1) {
            throw new InvalidArgumentException('A scheduling range must have a valid local date, time, and positive duration.');
        }
        $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i', "$date $time", $timezone);
        $errors = DateTimeImmutable::getLastErrors();
        if ($start === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new InvalidArgumentException('A scheduling range has an invalid local date or time.');
        }
        $end = $start->modify("+$durationMinutes minutes");
        if ($end->format('Y-m-d') !== $start->format('Y-m-d')) {
            throw new InvalidArgumentException('A scheduling range cannot cross its local calendar date.');
        }

        return new self($start, $end);
    }

    public function durationMinutes(): int { return (int) (($this->end->getTimestamp() - $this->start->getTimestamp()) / 60); }
    public function overlaps(self $other): bool { return $this->start < $other->end && $other->start < $this->end; }
    public function isAdjacentTo(self $other): bool { return $this->end == $other->start || $other->end == $this->start; }
    public function jsonSerialize(): array { return ['date' => $this->start->format('Y-m-d'), 'start_time' => $this->start->format('H:i'), 'end_time' => $this->end->format('H:i'), 'duration_minutes' => $this->durationMinutes(), 'timezone' => $this->start->getTimezone()->getName()]; }
}
