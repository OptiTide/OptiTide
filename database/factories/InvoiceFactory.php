<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(50_000, 500_000);

        return [
            'user_id' => User::factory()->state(['role' => 'client']),
            'status' => InvoiceStatus::Draft,
            'currency' => 'AUD',
            'subtotal' => $subtotal,
            'tax' => 0,
            'total' => $subtotal,
            'amount_paid' => 0,
        ];
    }

    /** A sent invoice with a future due date. */
    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::Sent,
            'sent_at' => now(),
            'due_date' => now()->addDays(14)->toDateString(),
        ]);
    }

    /** A sent invoice whose due date is $days in the past. */
    public function overdueBy(int $days): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::Sent,
            'sent_at' => now()->subDays($days + 14),
            'due_date' => now()->subDays($days)->toDateString(),
        ]);
    }
}
