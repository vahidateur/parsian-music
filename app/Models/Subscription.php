<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'teacher_id',
        'instrument_id',
        'sessions_allocated',
        'sessions_used',
        'monthly_fee',
        'payment_status',
        'renewal_date',
        'notes',
    ];

    protected $casts = [
        'renewal_date' => 'date',
        'sessions_allocated' => 'integer',
        'sessions_used' => 'integer',
        'monthly_fee' => 'integer',
        'payment_status' => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $subscription) {
            if (is_null($subscription->renewal_date)) {
                $subscription->renewal_date = now()->addDays(30)->toDateString();
            }
        });
    }

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

    public function getRemainingSessionsAttribute(): int
    {
        return $this->sessions_allocated - $this->sessions_used;
    }

    public function getOverageCountAttribute(): int
    {
        return max(0, $this->sessions_used - $this->sessions_allocated);
    }
}
