<?php

namespace Database\Factories;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role_id' => fn () => Role::firstOrCreate(
                ['name' => RoleName::Student->value],
                ['label' => 'Student'],
            )->id,
            'status' => UserStatus::Active,
            'remember_token' => Str::random(10),
        ];
    }

    public function withRole(RoleName $role): static
    {
        return $this->state(fn () => [
            'role_id' => Role::firstOrCreate(
                ['name' => $role->value],
                ['label' => $role->label()],
            )->id,
        ]);
    }

    public function admin(): static
    {
        return $this->withRole(RoleName::Admin);
    }

    public function student(): static
    {
        return $this->withRole(RoleName::Student);
    }

    public function supervisor(): static
    {
        return $this->withRole(RoleName::Supervisor);
    }

    public function lecturer(): static
    {
        return $this->withRole(RoleName::Lecturer);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => UserStatus::Inactive]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
