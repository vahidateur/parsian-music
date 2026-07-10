<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class InstituteProfile extends Model
{
    protected $table = 'institute_profile';

    protected $fillable = [
        'name', 'name_en', 'description',
        'logo_path', 'cover_path',
        'phone', 'mobile', 'email', 'website',
        'instagram', 'telegram', 'whatsapp',
        'address', 'city', 'province', 'postal_code',
        'working_days', 'working_hours_from', 'working_hours_to',
    ];

    protected $casts = [
        'working_days' => 'array',
    ];

    // ── Singleton helper ─────────────────────────────────────────────────────

    /**
     * Always returns the single institute record, creating it if absent.
     */
    public static function instance(): static
    {
        return static::firstOrCreate(['id' => 1]);
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::url($this->logo_path) : null;
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_path ? Storage::url($this->cover_path) : null;
    }
}
