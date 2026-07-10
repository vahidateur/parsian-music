<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RoleEnum;
use App\Models\Student;
use App\Models\Teacher;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'avatar_path',
        'locale',
        'timezone',
        'password',
        'role',
        'is_active',
        'force_password_change',
        'created_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password'              => 'hashed',
            'is_active'             => 'boolean',
            'force_password_change' => 'boolean',
            'last_login_at'         => 'datetime',
            'role'                  => RoleEnum::class,
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The Teacher profile linked to this user account.
     * Null when no teacher record has been associated (e.g. admin/student accounts).
     */
    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * The Student profile linked to this user account.
     * Null when no student record has been associated (e.g. admin/teacher accounts).
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /** Resolved URL for the user's avatar (or null if not set). */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path
            ? asset('storage/' . $this->avatar_path)
            : null;
    }
}
