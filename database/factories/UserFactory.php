<?php

namespace Database\Factories;

use App\Enums\UserRole;
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
     * All users default to the 'member' role. Use the role states below
     * (e.g. ->coopAdmin()) or ->state(['role' => UserRole::Treasurer]) in tests.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // Default role: all users start as members unless overridden.
            'role' => UserRole::Member,
        ];
    }

    /**
     * Unverified email state — used for email-verification flow testing.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    // ─── Convenience role states ────────────────────────────────────────────
    // Use these in tests for readable, self-documenting user creation:
    //   User::factory()->coopAdmin()->create()
    //   User::factory()->treasurer()->create()

    public function member(): static
    {
        return $this->state(['role' => UserRole::Member]);
    }

    public function coopAdmin(): static
    {
        return $this->state(['role' => UserRole::CoopAdmin]);
    }

    public function systemAdmin(): static
    {
        return $this->state(['role' => UserRole::SystemAdmin]);
    }

    public function countryAdmin(): static
    {
        return $this->state(['role' => UserRole::CountryAdmin]);
    }

    public function financeOfficer(): static
    {
        return $this->state(['role' => UserRole::FinanceOfficer]);
    }

    public function treasurer(): static
    {
        return $this->state(['role' => UserRole::Treasurer]);
    }

    public function processor(): static
    {
        return $this->state(['role' => UserRole::Processor]);
    }

    public function auditor(): static
    {
        return $this->state(['role' => UserRole::Auditor]);
    }
}
