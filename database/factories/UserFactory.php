<?php

namespace Database\Factories;

use App\Enums\RoleEnum;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'phone' => fake()->unique()->numerify('09#########'),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => RoleEnum::ADMIN->value,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the account is disabled (cannot authenticate).
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /** Role state: `super_admin`. */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => RoleEnum::SUPER_ADMIN->value,
        ]);
    }

    /** Role state: `admin`. */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => RoleEnum::ADMIN->value,
        ]);
    }

    /** Role state: `teacher`. */
    public function teacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => RoleEnum::TEACHER->value,
        ]);
    }

    /** Role state: `student`. */
    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => RoleEnum::STUDENT->value,
        ]);
    }

    /** Actor authorized by the current admin policies. */
    public function policyActor(): static
    {
        return $this->admin();
    }

    /** Actor that can authenticate but is not authorized for admin mutations. */
    public function unauthorizedActor(): static
    {
        return $this->teacher();
    }

    /**
     * Account linked to a teacher profile, which blocks a clean deletion.
     */
    public function withDeletionDependency(): static
    {
        return $this->afterCreating(function (User $user): void {
            Teacher::factory()
                ->create()
                ->forceFill(['user_id' => $user->id])
                ->saveQuietly();
        });
    }

    /** Account without any dependent record. */
    public function independent(): static
    {
        return $this->state(fn (array $attributes) => []);
    }
}
