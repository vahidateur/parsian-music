<?php

namespace App\Models;

use App\Enums\SessionStatusEnum;
use App\Models\Concerns\ScopesForSessionFilters;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSession extends Model
{
    use HasFactory, ScopesForSessionFilters;

    protected $fillable = [
        'enrollment_id',
        'recurring_schedule_id',
        'session_date',
        'start_time',
        'duration_minutes',
        'status',
        'room',
        'session_fee',
        'discount',
        'notes',
    ];

    protected $casts = [
        'session_date' => 'date',
        'start_time' => 'datetime',
        'duration_minutes' => 'integer',
        'session_fee' => 'integer',
        'discount' => 'integer',
        'status' => SessionStatusEnum::class,
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'enrollment_id');
    }

    public function recurringSchedule(): BelongsTo
    {
        return $this->belongsTo(RecurringSchedule::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ClassAttendance::class, 'class_session_id');
    }
}
