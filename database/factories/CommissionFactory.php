<?php

namespace Database\Factories;

use App\Enums\CommissionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Commission>
 */
class CommissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'referrer_id' => User::factory()->state(['role' => 'client']),
            'order_id' => null,
            'amount' => fake()->numberBetween(5_000, 50_000),
            'currency' => 'AUD',
            'rate_basis_points' => 1000,
            'status' => CommissionStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => CommissionStatus::Approved, 'approved_at' => now()]);
    }

    public function credited(): static
    {
        return $this->state(fn () => ['status' => CommissionStatus::Credited, 'approved_at' => now(), 'settled_at' => now()]);
    }
}
