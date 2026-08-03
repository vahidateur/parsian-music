<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditRecord extends Model
{
    use HasFactory;

    public const EVENT_EXECUTION = 'execution';
    public const EVENT_REJECTED_OPERATION = 'rejected_operation';

    protected $table = 'audit_records';

    protected $fillable = [
        'actor_id',
        'event_type',
        'entity_type',
        'action',
        'selection_mode',
        'context_fingerprint',
        'total',
        'succeeded',
        'skipped',
        'failed',
        'reason_categories',
        'reason_identifiers',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'actor_id' => 'integer',
        'total' => 'integer',
        'succeeded' => 'integer',
        'skipped' => 'integer',
        'failed' => 'integer',
        'reason_categories' => 'array',
        'reason_identifiers' => 'array',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
