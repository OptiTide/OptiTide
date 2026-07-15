<?php

namespace App\Models;

use App\Core\Model;

class ChatConversation extends Model
{
    protected static string $table = 'chat_conversations';

    public const MODE_AI = 'ai';
    public const MODE_HUMAN = 'human';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    public static function byToken(string $token): ?array
    {
        $token = trim($token);

        return $token === '' ? null : static::firstWhere('token', $token);
    }

    public static function messages(int|string $id): array
    {
        return ChatMessage::query()->where('conversation_id', $id)->orderBy('id', 'asc')->get();
    }

    /** Conversations for the staff queue, most recent activity first. */
    public static function queue(): array
    {
        return static::query()->orderBy('last_message_at', 'desc')->orderBy('id', 'desc')->get();
    }
}
