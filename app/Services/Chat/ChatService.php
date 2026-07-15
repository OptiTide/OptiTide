<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;

/**
 * The single write path for live chat. A visitor's message triggers an instant
 * assistant reply while the conversation is in AI mode; once a human takes over
 * (from the admin), the AI stays quiet. Visitors never see whether a reply was
 * AI or a person — both are just "agent".
 */
final class ChatService
{
    public function start(int|string|null $clientId, ?string $name, ?string $email): array
    {
        $conversation = ChatConversation::create([
            'token'           => str_random(48),
            'client_id'       => $clientId ?: null,
            'name'            => $name ?: null,
            'email'           => $email ?: null,
            'status'          => ChatConversation::STATUS_OPEN,
            'mode'            => ChatConversation::MODE_AI,
            'last_message_at' => now(),
        ]);

        $this->postAgent($conversation['id'], 'Hi! 👋 Welcome to OptiTide. How can we help you today — web design, SEO, social media or hosting?', true, null);

        return ChatConversation::find($conversation['id']);
    }

    public function postVisitorMessage(array $conversation, string $body): void
    {
        $this->addMessage($conversation['id'], 'visitor', $body, false, null);
        ChatConversation::updateById($conversation['id'], [
            'last_message_at' => now(),
            'status'          => ChatConversation::STATUS_OPEN,
        ]);

        // Instant assistant reply only while no human has taken over.
        if (($conversation['mode'] ?? ChatConversation::MODE_AI) === ChatConversation::MODE_AI) {
            $reply = (new ChatAiService())->reply(ChatConversation::messages($conversation['id']));
            $this->postAgent($conversation['id'], $reply, true, null);
        }
    }

    public function postAgent(int|string $conversationId, string $body, bool $isAi, int|string|null $userId): void
    {
        $this->addMessage($conversationId, 'agent', $body, $isAi, $userId);
        ChatConversation::updateById($conversationId, ['last_message_at' => now()]);
    }

    public function takeOver(int|string $conversationId): void
    {
        ChatConversation::updateById($conversationId, ['mode' => ChatConversation::MODE_HUMAN]);
    }

    public function setStatus(int|string $conversationId, string $status): void
    {
        if (in_array($status, [ChatConversation::STATUS_OPEN, ChatConversation::STATUS_CLOSED], true)) {
            ChatConversation::updateById($conversationId, ['status' => $status]);
        }
    }

    protected function addMessage(int|string $conversationId, string $sender, string $body, bool $isAi, int|string|null $userId): void
    {
        ChatMessage::create([
            'conversation_id' => $conversationId,
            'sender'          => $sender,
            'is_ai'           => $isAi ? 1 : 0,
            'user_id'         => $userId,
            'body'            => $body,
        ]);
    }
}
