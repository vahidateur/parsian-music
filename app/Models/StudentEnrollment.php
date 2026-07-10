<?php

namespace App\Models;

use App\Enums\EnrollmentStatusEnum;
use App\Enums\SkillLevelEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentEnrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'instrument_id',
        'teacher_id',
        'skill_level',
        'status',
        'started_at',
        'ended_at',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'date',
        'ended_at' => 'date',
        'status' => EnrollmentStatusEnum::class,
        'skill_level' => SkillLevelEnum::class,
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }

    public function recurringSchedules(): HasMany
    {
        return $this->hasMany(RecurringSchedule::class, 'enrollment_id');
    }

    public function classSessions(): HasMany
    {
        return $this->hasMany(ClassSession::class, 'enrollment_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'enrollment_id');
    }

    /**
     * Scope for active enrollments only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', EnrollmentStatusEnum::Active);
    }
}
