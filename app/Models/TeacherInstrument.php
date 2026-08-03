<?php

namespace App\Models;

use App\Enums\SkillLevelEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherInstrument extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'instrument_id',
        'skill_level',
        'is_primary',
    ];

    protected $casts = [
        'skill_level' => SkillLevelEnum::class,
        'is_primary' => 'boolean',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }
}
