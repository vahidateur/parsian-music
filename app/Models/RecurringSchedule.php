<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'weekday',
        'start_time',
        'duration_minutes',
        'room',
        'is_active',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'duration_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'enrollment_id');
    }

    /**
     * Scope for active schedules only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
