<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    protected $table = 'login_logs';

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'login_at',
        'success',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'success' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
