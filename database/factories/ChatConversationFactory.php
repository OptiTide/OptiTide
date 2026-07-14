<?php

namespace Database\Factories;

use App\Enums\ChatConversationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ChatConversation>
 */
class ChatConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'client']),
            'assigned_to' => null,
            'status' => ChatConversationStatus::Open,
            'last_message_at' => now(),
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => ChatConversationStatus::Closed]);
    }

    public function assignedTo(User $staff): static
    {
        return $this->state(fn () => ['assigned_to' => $staff->id]);
    }
}
