<?php

namespace App\Models;

use App\Domain\Scheduling\BusinessCodeOwner;
use App\Enums\StudentStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'phone',
        'parent_phone',
        'status',
        'join_date',
        'notes',
    ];

    protected $casts = [
        'join_date' => 'date',
        'status' => StudentStatusEnum::class,
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $student): void {
            app(BusinessCodeOwner::class)->ensureStudentCode($student);
        });
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ClassAttendance::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /** The lead that originated this student, if converted from CRM. */
    public function lead(): HasOne
    {
        return $this->hasOne(Lead::class, 'converted_student_id');
    }
}
