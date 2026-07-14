<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\HelpdeskTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HelpdeskTicket>
 */
class HelpdeskTicketFactory extends Factory
{
    protected $model = HelpdeskTicket::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject' => fake()->sentence(4),
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::Normal,
        ];
    }

    public function resolved(): static
    {
        return $this->state(['status' => TicketStatus::Resolved, 'resolved_at' => now()]);
    }
}
