<?php

namespace Database\Factories;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'chat_conversation_id' => ChatConversation::factory(),
            'sender_type' => ChatMessage::SENDER_CLIENT,
            'user_id' => null,
            'body' => fake()->sentence(),
            'read_at' => null,
        ];
    }

    public function fromClient(): static
    {
        return $this->state(fn () => ['sender_type' => ChatMessage::SENDER_CLIENT]);
    }

    public function fromStaff(): static
    {
        return $this->state(fn () => ['sender_type' => ChatMessage::SENDER_STAFF]);
    }

    public function fromAi(): static
    {
        return $this->state(fn () => ['sender_type' => ChatMessage::SENDER_AI]);
    }
}
