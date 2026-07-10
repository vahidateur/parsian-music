<?php

namespace App\Models;

use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'invoice_number',
        'student_id',
        'enrollment_id',
        'issue_date',
        'due_date',
        'subtotal',
        'discount',
        'tax',
        'total',
        'currency',
        'status',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date'   => 'date',
        'subtotal'   => 'decimal:2',
        'discount'   => 'decimal:2',
        'tax'        => 'decimal:2',
        'total'      => 'decimal:2',
        'status'     => InvoiceStatusEnum::class,
    ];

    // ── Lifecycle ────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (self $invoice) {
            $invoice->uuid           ??= (string) Str::uuid();
            $invoice->invoice_number ??= static::generateNumber();
            $invoice->currency       ??= 'IRR';
        });

        static::saving(function (self $invoice) {
            $invoice->total = max(0, $invoice->subtotal - $invoice->discount + $invoice->tax);
        });
    }

    // ── Balance helpers ───────────────────────────────────────────────────────

    /** Sum of all completed payments. */
    public function amountPaid(): float
    {
        return (float) $this->payments()
            ->where('status', PaymentStatusEnum::Completed)
            ->sum('amount');
    }

    /** Remaining balance (never negative). */
    public function amountDue(): float
    {
        return max(0, (float) $this->total - $this->amountPaid());
    }

    /**
     * Recompute and persist the invoice status based on actual payment totals.
     * Called automatically by InvoiceService::registerPayment().
     *
     * Transition map:
     *   paid == 0              → stays as-is (Issued / Overdue)
     *   0 < paid < total       → PartiallyPaid
     *   paid >= total          → Paid
     */
    public function syncStatusFromPayments(): static
    {
        $paid  = $this->amountPaid();
        $total = (float) $this->total;

        $target = match (true) {
            $paid >= $total && $total > 0 => InvoiceStatusEnum::Paid,
            $paid > 0                     => InvoiceStatusEnum::PartiallyPaid,
            default                       => null, // no change
        };

        if ($target && $this->status->canTransitionTo($target)) {
            $this->update(['status' => $target]);
        }

        return $this;
    }

    // ── Other helpers ─────────────────────────────────────────────────────────

    public static function generateNumber(): string
    {
        $year = now()->year;
        $last = static::withTrashed()->whereYear('created_at', $year)->max('id') ?? 0;
        $seq  = str_pad($last + 1, 5, '0', STR_PAD_LEFT);

        return "INV-{$year}-{$seq}";
    }

    public function recalculate(): static
    {
        $this->subtotal = $this->items()->sum('total');
        $this->discount = $this->items()->sum('discount');
        $this->save();

        return $this;
    }

    public function isOverdue(): bool
    {
        return $this->status !== InvoiceStatusEnum::Paid
            && $this->due_date->isPast();
    }

    // ── Relations ────────────────────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'enrollment_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class)->orderBy('paid_at');
    }
}
