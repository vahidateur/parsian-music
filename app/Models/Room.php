<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'capacity', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active rooms only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
