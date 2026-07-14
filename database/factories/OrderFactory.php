<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $total = fake()->numberBetween(50_000, 500_000);

        return [
            'user_id' => User::factory()->state(['role' => 'client']),
            'currency' => 'AUD',
            'payment_status' => PaymentStatus::Pending,
            'subtotal' => $total,
            'total' => $total,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'payment_status' => PaymentStatus::Paid,
            'placed_at' => now(),
        ]);
    }
}
