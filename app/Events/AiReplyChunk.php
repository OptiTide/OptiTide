<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A live token delta from a streaming AI reply, rendered into a transient
 * "typing" bubble. Carries only the text delta — never a 'sender_type: ai'
 * marker — so the stealth-AI rule holds: the client sees an agent typing, not
 * a bot. When the stream finishes the job persists the message and fires
 * ChatMessageSent, which replaces the transient bubble with the stored (masked)
 * message.
 */
class AiReplyChunk implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $conversationId, public string $delta) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.conversation.'.$this->conversationId)];
    }

    public function broadcastAs(): string
    {
        // Source-neutral wire name: the event name travels in cleartext to the
        // client's browser, so it must not encode "ai" (stealth-AI rule).
        return 'agent.typing';
    }

    public function broadcastWith(): array
    {
        return ['delta' => $this->delta];
    }
}
