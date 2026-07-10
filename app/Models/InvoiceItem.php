<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'title',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'total',
        'sort_order',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'unit_price' => 'decimal:2',
        'discount'   => 'decimal:2',
        'total'      => 'decimal:2',
        'sort_order' => 'integer',
    ];

    // ── Lifecycle ────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        // Auto-compute line total before every save.
        static::saving(function (self $item) {
            $item->total = max(0, ($item->quantity * $item->unit_price) - $item->discount);
        });
    }

    // ── Relations ────────────────────────────────────────────────────────────

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
