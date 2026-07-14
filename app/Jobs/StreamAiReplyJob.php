<?php

namespace App\Jobs;

use App\Enums\ChatConversationStatus;
use App\Events\AiReplyChunk;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\AI\ChatPromptBuilder;
use App\Services\AI\ClaudeClient;
use App\Services\AI\ClaudeGenerationException;
use App\Services\ChatService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * The AI support fallback: when a client messages an unassigned conversation,
 * this streams a Claude-drafted reply token-by-token (broadcast as AiReplyChunk
 * for a live "typing" effect) and then persists it as an `ai` message. The
 * client only ever sees an agent — the stealth-AI rule is enforced by
 * ChatMessage::displayRoleFor().
 */
class StreamAiReplyJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public function __construct(public int $conversationId, public int $triggerMessageId) {}

    public function handle(ClaudeClient $claude, ChatPromptBuilder $builder, ChatService $chat): void
    {
        $conversation = ChatConversation::with('user')->find($this->conversationId);

        if ($conversation === null || ! $this->shouldReply($conversation)) {
            return;
        }

        // Coalesce token deltas into whole words before broadcasting, so a long
        // reply is a handful of AiReplyChunk events, not one per token.
        $buffer = '';
        $flushWords = function () use (&$buffer, $conversation) {
            if (($cut = strrpos($buffer, ' ')) !== false) {
                $chunk = substr($buffer, 0, $cut + 1);
                $buffer = substr($buffer, $cut + 1);
                AiReplyChunk::dispatch($conversation->id, $chunk);
            }
        };

        try {
            $body = $claude->stream(
                $builder->system(),
                $builder->user($conversation),
                function (string $delta) use (&$buffer, $flushWords) {
                    $buffer .= $delta;
                    $flushWords();
                },
            );
            if ($buffer !== '') {
                AiReplyChunk::dispatch($conversation->id, $buffer);
            }
        } catch (ClaudeGenerationException) {
            // Never leave the client hanging on an AI hiccup — post a neutral
            // holding reply (still an "agent" to the client) so a human can
            // pick up.
            $body = 'Thanks for your message! One of our specialists will follow up with you shortly.';
        }

        // Re-check against the freshest state: a staff reply/claim or close, or a
        // newer client message (handled by its own job), may have landed while we
        // streamed. This also makes the job idempotent — a re-delivery after the
        // reply was already posted sees a newer message and bails.
        if (! $this->shouldReply($conversation->fresh())) {
            return;
        }

        $chat->postMessage($conversation, ChatMessage::SENDER_AI, null, trim($body));
    }

    /**
     * The AI should answer only while the conversation is open, unclaimed, and
     * the triggering client message is still the latest — so message bursts
     * collapse to one reply and retries never double-post.
     */
    protected function shouldReply(ChatConversation $conversation): bool
    {
        return $conversation->assigned_to === null
            && $conversation->status === ChatConversationStatus::Open
            && ! $conversation->messages()->where('id', '>', $this->triggerMessageId)->exists();
    }
}
