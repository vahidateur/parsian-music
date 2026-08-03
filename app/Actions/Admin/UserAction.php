<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\User;
use App\Support\PersianTextNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Account mutations.
 *
 * The action never decides who may act: the named UserPolicy abilities and the
 * self-management boundary are resolved by the controller before it is called.
 * It owns the persistence rules only — hashing, the active default, the created_by
 * link and the temporary-password contract.
 *
 * Requirements: 6.4, 6.6, 6.9, 6.10, 6.13, 16.3
 */
final class UserAction
{
    /** Length of a generated temporary password. */
    private const TEMPORARY_PASSWORD_LENGTH = 12;

    /**
     * Canonical form of every persisted text field, shared with the account requests.
     *
     * The password is never normalized: it is persisted exactly as submitted, hashed.
     *
     * @var array<string, string>
     */
    public const NORMALIZED_FIELDS = [
        'full_name' => PersianTextNormalizer::TEXT,
        'phone' => PersianTextNormalizer::TEXT,
        'email' => PersianTextNormalizer::TEXT,
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): User
    {
        $data = PersianTextNormalizer::fields($data, self::NORMALIZED_FIELDS);

        return User::create([
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
            'is_active' => true,
            'created_by' => $actor->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $user->update(PersianTextNormalizer::fields($data, self::NORMALIZED_FIELDS));

        return $user;
    }

    public function delete(User $user): void
    {
        DB::transaction(static function () use ($user): void {
            $user->delete();
        });
    }

    public function toggle(User $user): User
    {
        $user->update(['is_active' => ! $user->is_active]);

        return $user;
    }

    /**
     * Set a temporary password and require a change on the next sign-in.
     *
     * Both fields move together, so they are written in one transaction: an
     * account can never end up with a temporary password that it is not forced
     * to replace.
     */
    public function resetPassword(User $user): string
    {
        $temporary = Str::password(self::TEMPORARY_PASSWORD_LENGTH, symbols: false);

        DB::transaction(static function () use ($user, $temporary): void {
            $user->update([
                'password' => Hash::make($temporary),
                'force_password_change' => true,
            ]);
        });

        return $temporary;
    }
}
