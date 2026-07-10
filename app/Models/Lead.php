<?php

namespace App\Models;

use App\DTOs\ConvertLeadData;
use App\Enums\LeadPriorityEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\LeadStatusEnum;
use App\Services\LeadService;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'age',
        'preferred_instrument_id',
        'preferred_teacher_id',
        'source',
        'status',
        'priority',
        'assigned_to',
        'notes',
        'next_follow_up_at',
        'converted_student_id',
        'converted_at',
    ];

    protected $casts = [
        'status'             => LeadStatusEnum::class,
        'source'             => LeadSourceEnum::class,
        'priority'           => LeadPriorityEnum::class,
        'next_follow_up_at'  => 'datetime',
        'converted_at'       => 'datetime',
        'age'                => 'integer',
    ];

    // ── Status machine ───────────────────────────────────────────────────────

    /**
     * Advance the lead to the next status.
     *
     * @throws DomainException on invalid transition.
     */
    public function transitionTo(LeadStatusEnum $next): static
    {
        if (! $this->status->canTransitionTo($next)) {
            throw new DomainException(
                "Lead #{$this->id}: cannot transition from '{$this->status->label()}' to '{$next->label()}'."
            );
        }

        $this->update(['status' => $next]);

        return $this;
    }

    // ── Extension point ──────────────────────────────────────────────────────

    /**
     * Convenience proxy — delegates to LeadService::convert().
     *
     * Use LeadService directly when you need full control over ConvertLeadData.
     * This method exists for simple one-liner call sites.
     */
    public function convertToStudent(?ConvertLeadData $data = null): Student
    {
        return app(LeadService::class)->convert($this, $data ?? new ConvertLeadData());
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isConverted(): bool
    {
        return $this->status === LeadStatusEnum::Registered
            && $this->converted_student_id !== null;
    }

    public function isOverdue(): bool
    {
        return $this->next_follow_up_at !== null
            && $this->next_follow_up_at->isPast()
            && ! $this->status->isTerminal();
    }

    // ── Relations ────────────────────────────────────────────────────────────

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function preferredInstrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class, 'preferred_instrument_id');
    }

    public function preferredTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'preferred_teacher_id');
    }

    public function convertedStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'converted_student_id');
    }
}
