<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RoleEnum;
use App\Models\Student;
use App\Models\Teacher;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'login_attempts',
        'locked_until',
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
            'locked_until'          => 'datetime',
            'role'                  => RoleEnum::class,
        ];
    }

    /**
     * چک کن که آیا حساب قفل شده است
     */
    public function isLocked(): bool
    {
        if (!$this->locked_until) {
            return false;
        }

        if (now()->isAfter($this->locked_until)) {
            // قفل تاریخ گذاشته، آن را بردار
            $this->update([
                'locked_until' => null,
                'login_attempts' => 0,
            ]);
            return false;
        }

        return true;
    }

    /**
     * افزایش تلاش های ناموفق
     */
    public function incrementLoginAttempts(): void
    {
        $attempts = $this->login_attempts + 1;

        if ($attempts >= 3) {
            // بعد از 3 تلاش ناموفق، قفل کن برای 30 دقیقه
            $this->update([
                'login_attempts' => $attempts,
                'locked_until' => now()->addMinutes(30),
            ]);
        } else {
            $this->update(['login_attempts' => $attempts]);
        }
    }

    /**
     * ریست کردن تلاش های ناموفق بعد از لاگین موفق
     */
    public function resetLoginAttempts(): void
    {
        $this->update([
            'login_attempts' => 0,
            'locked_until' => null,
        ]);
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

    /**
     * Login logs for this user.
     */
    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    /** Resolved URL for the user's avatar (or null if not set). */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path
            ? asset('storage/' . $this->avatar_path)
            : null;
    }
}
