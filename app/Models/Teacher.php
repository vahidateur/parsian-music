<?php

namespace App\Models;

use App\Enums\EnrollmentStatusEnum;
use App\Enums\TeacherStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'phone',
        'status',
        'bio',
        'hire_date',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'status' => TeacherStatusEnum::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $teacher) {
            if (empty($teacher->teacher_code)) {
                $teacher->teacher_code = 'T-' . str_pad(
                    (static::max('id') ?? 0) + 1,
                    5, '0', STR_PAD_LEFT
                );
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function instruments(): BelongsToMany
    {
        return $this->belongsToMany(Instrument::class, 'teacher_instruments')
            ->withPivot(['skill_level', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Enrollments assigned to this teacher.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    /**
     * Scope for active teachers only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', TeacherStatusEnum::Active);
    }

    /**
     * Distinct count of students actively enrolled with this teacher.
     */
    public function enrolledStudentsCount(): int
    {
        return $this->enrollments()
            ->active()
            ->distinct()
            ->count('student_id');
    }
}
