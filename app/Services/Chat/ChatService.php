<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Support\Features;

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

        // Brand + service lines come from Settings/the catalogue, so the greeting
        // can't drift from what's actually on sale.
        $lines = \App\Services\Chat\ChatAiService::serviceLineList();
        $brand = config('company.brand_name') ?: config('app.name');

        // The greeting is the one message that isn't a reply, so it states who is
        // about to answer. With AI off, promising an instant answer would be a
        // lie — the visitor is waiting on a person.
        $greeting = Features::enabled('ai_chat')
            ? 'Hi! 👋 I\'m the ' . $brand
                . ' AI assistant — I can answer most questions instantly, and a human teammate can jump in whenever you need. '
                . 'How can I help today' . ($lines ? ' — ' . $lines . '?' : '?')
            : 'Hi! 👋 Thanks for getting in touch with ' . $brand
                . '. Leave your question here and a teammate will reply in this window'
                . ($lines ? ' — we can help with ' . $lines . '.' : '.');

        // Stays flagged is_ai even with AI off: nobody typed it, and is_ai = 0
        // renders as "You / team" in the admin thread — i.e. it would credit a
        // teammate with a message they never sent.
        $this->postAgent($conversation['id'], $greeting, true, null);

        return ChatConversation::find($conversation['id']);
    }

    /** Returns the stored visitor message (so the caller can advance the poll cursor). */
    public function postVisitorMessage(array $conversation, string $body): array
    {
        $visitor = $this->addMessage($conversation['id'], 'visitor', $body, false, null);
        ChatConversation::updateById($conversation['id'], [
            'last_message_at' => now(),
            'status'          => ChatConversation::STATUS_OPEN,
        ]);

        // Instant assistant reply only while no human has taken over. The reply is
        // stored now but delivered to the visitor via the SAME poll channel as a
        // human reply — so a visitor can't tell AI from human by how it arrives.
        //
        // The ai_chat gate belongs HERE, at the dispatch, not in ChatAiService:
        // reply() always returns something (it falls back to a canned responder
        // when there's no API key), so gating it there would still auto-answer.
        if (Features::enabled('ai_chat')
            && ($conversation['mode'] ?? ChatConversation::MODE_AI) === ChatConversation::MODE_AI) {
            $reply = (new ChatAiService())->reply(ChatConversation::messages($conversation['id']));
            $this->postAgent($conversation['id'], $reply, true, null);
        }

        return $visitor;
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

    protected function addMessage(int|string $conversationId, string $sender, string $body, bool $isAi, int|string|null $userId): array
    {
        return ChatMessage::create([
            'conversation_id' => $conversationId,
            'sender'          => $sender,
            'is_ai'           => $isAi ? 1 : 0,
            'user_id'         => $userId,
            'body'            => $body,
        ]);
    }
}
