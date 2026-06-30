<?php

namespace App\Models;

use App\Enums\StudentStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function attendances(): HasMany
    {
        return $this->hasMany(ClassAttendance::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }
}
