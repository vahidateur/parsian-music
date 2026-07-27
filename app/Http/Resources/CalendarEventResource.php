<?php

namespace App\Http\Resources;

use App\Enums\SessionStatusEnum;
use App\Models\ClassSession;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin ClassSession
 */
class CalendarEventResource extends JsonResource
{
    /**
     * Transform a class session into the FullCalendar event contract.
     *
     * Relations are read only when they were eager-loaded by the caller. This
     * keeps serialization query-free for both enrollment-backed and direct
     * sessions.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $student = $this->resolveSessionRelation('student');
        $teacher = $this->resolveSessionRelation('teacher');
        $instrument = $this->resolveSessionRelation('instrument');

        $studentName = $this->personName($student);
        $teacherName = $this->personName($teacher);
        $instrumentName = $this->instrumentName($instrument);
        $start = $this->sessionStart();
        $end = $start->copy()->addMinutes((int) $this->duration_minutes);

        return [
            'id' => (int) $this->id,
            'title' => Str::limit($studentName.' — '.$instrumentName, 255, ''),
            'start' => $start->format('Y-m-d\\TH:i:s'),
            'end' => $end->format('Y-m-d\\TH:i:s'),
            'status' => $this->sessionStatus()->value,
            'studentName' => $studentName,
            'teacherName' => $teacherName,
            'instrumentName' => $instrumentName,
            'room' => $this->room,
            'extendedProps' => [
                'enrollment_id' => $this->enrollment_id,
                'session_fee' => $this->session_fee,
                'duration_minutes' => (int) $this->duration_minutes,
                'notes' => $this->notes,
                'session_date' => $this->sessionDate()->toDateString(),
            ],
        ];
    }

    private function resolveSessionRelation(string $relation): ?Model
    {
        $enrollment = $this->loadedRelation('enrollment');

        if ($enrollment !== null) {
            $enrollmentRelation = $this->loadedRelationFrom($enrollment, $relation);

            if ($enrollmentRelation !== null) {
                return $enrollmentRelation;
            }
        }

        return $this->loadedRelation($relation);
    }

    private function loadedRelation(string $relation): ?Model
    {
        return $this->loadedRelationFrom($this->resource, $relation);
    }

    private function loadedRelationFrom(Model $model, string $relation): ?Model
    {
        if (! $model->relationLoaded($relation)) {
            return null;
        }

        $related = $model->getRelation($relation);

        return $related instanceof Model ? $related : null;
    }

    private function personName(?Model $person): string
    {
        return $person?->getAttribute('full_name') ?: '—';
    }

    private function instrumentName(?Model $instrument): string
    {
        if ($instrument === null) {
            return '—';
        }

        return $instrument->getAttribute('display_name')
            ?: $instrument->getAttribute('name')
            ?: '—';
    }

    private function sessionStatus(): SessionStatusEnum
    {
        return $this->status;
    }

    private function sessionDate(): CarbonInterface
    {
        $sessionDate = $this->getAttribute('session_date');

        return $sessionDate instanceof CarbonInterface
            ? $sessionDate
            : Carbon::parse($sessionDate);
    }

    private function sessionStart(): Carbon
    {
        $sessionDate = $this->sessionDate();
        $startTime = $this->getAttribute('start_time');
        $startTime = $startTime instanceof CarbonInterface
            ? $startTime
            : Carbon::parse($startTime);

        return Carbon::create(
            $sessionDate->year,
            $sessionDate->month,
            $sessionDate->day,
            $startTime->hour,
            $startTime->minute,
            $startTime->second,
            $sessionDate->getTimezone(),
        );
    }
}
