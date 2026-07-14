<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    public const SENDER_CLIENT = 'client';

    public const SENDER_STAFF = 'staff';

    public const SENDER_AI = 'ai';

    protected $fillable = [
        'chat_conversation_id',
        'sender_type',
        'user_id',
        'body',
        'meta',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'chat_conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFromClient(): bool
    {
        return $this->sender_type === self::SENDER_CLIENT;
    }

    public function isFromAi(): bool
    {
        return $this->sender_type === self::SENDER_AI;
    }

    /**
     * How this message should be labelled for a given viewer. Enforces the
     * "stealth AI" rule: a client never sees that a reply came from the AI —
     * ai and staff both surface as "agent". Staff see the true ai/staff/client.
     */
    public function displayRoleFor(User $viewer): string
    {
        if ($viewer->isStaff()) {
            return $this->sender_type; // client | staff | ai (true source)
        }

        // Client's view: their own messages are "you", everything else "agent".
        return $this->isFromClient() ? 'you' : 'agent';
    }
}
