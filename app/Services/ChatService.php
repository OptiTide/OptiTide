<?php

namespace App\Services;

use App\Enums\ChatConversationStatus;
use App\Events\ChatMessageSent;
use App\Jobs\StreamAiReplyJob;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * The single entry point for chat writes. Every message goes through
 * postMessage() so the conversation's activity timestamp, the broadcast nudge,
 * and the AI-fallback dispatch stay in lock-step — don't create ChatMessages
 * by hand elsewhere.
 */
class ChatService
{
    /** The client's current open conversation, creating one if needed. */
    public function openConversationFor(User $client): ChatConversation
    {
        if ($open = $client->chatConversations()->open()->latest()->first()) {
            return $open;
        }

        try {
            return $client->chatConversations()->create([]);
        } catch (QueryException) {
            // Lost a create race against the one-open-per-user unique index —
            // return the conversation the winner created.
            return $client->chatConversations()->open()->latest()->firstOrFail();
        }
    }

    /**
     * Append a message and fan out the side effects. A client message on an
     * unassigned conversation triggers the AI fallback reply.
     */
    public function postMessage(
        ChatConversation $conversation,
        string $senderType,
        ?User $user,
        string $body,
    ): ChatMessage {
        $message = DB::transaction(function () use ($conversation, $senderType, $user, $body) {
            $message = $conversation->messages()->create([
                'sender_type' => $senderType,
                'user_id' => $user?->id,
                'body' => $body,
            ]);

            // Activity reopens a resolved thread and bumps it up the inbox.
            $conversation->forceFill([
                'last_message_at' => now(),
                'status' => ChatConversationStatus::Open,
            ])->save();

            return $message;
        });

        ChatMessageSent::dispatch($conversation->id);

        if ($senderType === ChatMessage::SENDER_CLIENT && $conversation->assigned_to === null) {
            StreamAiReplyJob::dispatch($conversation->id, $message->id);
        }

        return $message;
    }

    /** Claim a conversation for a staff member; this silences the AI fallback. */
    public function assignToStaff(ChatConversation $conversation, User $staff): void
    {
        $conversation->forceFill(['assigned_to' => $staff->id])->save();
    }

    public function close(ChatConversation $conversation): void
    {
        $conversation->forceFill(['status' => ChatConversationStatus::Closed])->save();
    }

    public function markRead(ChatConversation $conversation, User $reader): void
    {
        $conversation->markReadBy($reader);
    }
}
