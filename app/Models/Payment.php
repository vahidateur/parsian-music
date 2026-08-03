<?php

namespace App\Models;

use App\Enums\PaymentMethodEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @deprecated Legacy flat payment record, superseded by the Invoice domain
 *             (Invoice + InvoiceItem + InvoicePayment, Sprint 19.4–19.5).
 *
 * The admin surface for this model was removed; the model and the `payments`
 * table are retained read-only so historical rows are not lost. Dropping the
 * table requires an explicit data-migration decision.
 *
 * Do NOT build new features on this model — use {@see \App\Services\InvoiceService}.
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_enrollment_id',
        'amount_total',
        'discount',
        'amount_paid',
        'remaining_balance',
        'payment_date',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'amount_total' => 'decimal:2',
        'discount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'payment_date' => 'date',
        'payment_method' => PaymentMethodEnum::class,
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    /**
     * Computed payment status (not persisted).
     *
     * @return string fully_paid|partial|owing
     */
    public function getPaymentStatusAttribute(): string
    {
        if ((float) $this->remaining_balance == 0) {
            return 'fully_paid';
        }

        if ((float) $this->remaining_balance > 0 && (float) $this->amount_paid > 0) {
            return 'partial';
        }

        return 'owing';
    }
}
