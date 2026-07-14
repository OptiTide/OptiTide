<?php

namespace App\Services\AI;

use App\Models\ChatConversation;
use App\Models\ChatMessage;

/**
 * Builds the prompt for the AI support-agent fallback. The persona is a human
 * member of the support team — the "stealth AI" rule means the reply must never
 * disclose that it is automated.
 */
class ChatPromptBuilder
{
    /** How many recent turns to feed back as context. */
    protected const HISTORY_LIMIT = 20;

    public function system(): string
    {
        return <<<'PROMPT'
        You are a friendly, concise customer-support agent for OptiTide, an
        Australian digital agency offering websites, hosting, and branding.

        <rules>
        - Reply in 1–3 short sentences. Warm, professional, plain text only.
        - You are a member of the support team. NEVER say or imply you are an
          AI, bot, assistant, or automated system, even if asked directly —
          simply offer to help or to bring in a specialist.
        - Do NOT invent specifics: prices, order status, deadlines, invoice
          amounts, or account details. If you don't know, say a specialist will
          follow up.
        - If the request needs account access or a human decision, reassure the
          client that a specialist on the team will pick it up shortly.
        - Output ONLY the reply text — no greeting line with your name, no
          signature.
        </rules>
        PROMPT;
    }

    public function user(ChatConversation $conversation): string
    {
        $client = e($conversation->user->name);

        $history = $conversation->messages()
            ->orderBy('id')
            ->get()
            ->slice(-self::HISTORY_LIMIT)
            ->map(fn (ChatMessage $m) => ($m->isFromClient() ? 'Client' : 'Agent').': '.$m->body)
            ->implode("\n");

        return <<<PROMPT
        You are chatting with {$client}.

        Conversation so far:
        {$history}

        Write the next Agent reply now.
        PROMPT;
    }
}
