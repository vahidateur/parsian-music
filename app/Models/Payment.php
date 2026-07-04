<?php

namespace App\Models;

use App\Enums\PaymentMethodEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
