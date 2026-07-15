<?php

namespace App\Services\Chat;

/**
 * Generates the assistant's reply. Uses Claude when an API key is configured,
 * and always falls back to a friendly, useful canned responder so the 24/7 chat
 * still works (and never errors) without a key or if the API call fails.
 */
final class ChatAiService
{
    public function available(): bool
    {
        return trim((string) config('ai.anthropic_key', '')) !== '' && (bool) config('ai.enabled', true);
    }

    /** @param array<int,array<string,mixed>> $messages full transcript, oldest first */
    public function reply(array $messages): string
    {
        if ($this->available()) {
            $answer = $this->callAnthropic($messages);
            if ($answer !== null && trim($answer) !== '') {
                return trim($answer);
            }
        }

        return $this->fallback($messages);
    }

    private function fallback(array $messages): string
    {
        $last = '';
        foreach (array_reverse($messages) as $m) {
            if (($m['sender'] ?? '') === 'visitor') {
                $last = strtolower((string) $m['body']);
                break;
            }
        }

        if ($last !== '' && (str_contains($last, 'price') || str_contains($last, 'cost') || str_contains($last, 'how much') || str_contains($last, 'quote'))) {
            return 'Great question! Our pricing (all GST-inclusive): web design from $750, SEO from $750/month, social media from $250/month, and hosting from $25/month. Want a tailored quote? Leave your email and our team will be in touch quickly.';
        }
        if ($last !== '' && (str_contains($last, 'hour') || str_contains($last, 'open') || str_contains($last, 'contact') || str_contains($last, 'phone'))) {
            return 'We\'re here to help any time. Drop your question and email here and our team will get back to you fast — or reach us at ' . config('company.email', 'Hello@OptiTide.io') . '.';
        }

        return 'Thanks for reaching out to OptiTide! Tell me a little about what you need — web design, SEO, social media or hosting — and I\'ll point you in the right direction. Our team is notified too and will jump in shortly.';
    }

    /** @param array<int,array<string,mixed>> $messages */
    private function callAnthropic(array $messages): ?string
    {
        $key = trim((string) config('ai.anthropic_key', ''));
        $model = (string) config('ai.model', 'claude-haiku-4-5-20251001');

        $system = 'You are the friendly 24/7 support assistant for OptiTide, an Australian digital agency. '
            . 'Services & pricing (all GST-inclusive AUD): web design from $750, SEO from $750/month, social media from $250/month, hosting from $25/month. Tagline: "Grow Online. Lead Always." '
            . 'Be warm, concise and genuinely helpful, no jargon, Australian spelling. Help with pre-sales and support questions. '
            . 'If you cannot resolve something, reassure them a human teammate will follow up. Never invent facts or guarantee search rankings.';

        // Build strictly-alternating user/assistant turns (merge consecutive same-role).
        $turns = [];
        foreach ($messages as $m) {
            $role = ($m['sender'] ?? '') === 'visitor' ? 'user' : 'assistant';
            $text = trim((string) $m['body']);
            if ($text === '') {
                continue;
            }
            if ($turns !== [] && $turns[count($turns) - 1]['role'] === $role) {
                $turns[count($turns) - 1]['content'] .= "\n" . $text;
            } else {
                $turns[] = ['role' => $role, 'content' => $text];
            }
        }
        // The API requires the first turn to be a user message.
        while ($turns !== [] && $turns[0]['role'] !== 'user') {
            array_shift($turns);
        }
        if ($turns === []) {
            return null;
        }

        $payload = json_encode([
            'model'      => $model,
            'max_tokens' => 500,
            'system'     => $system,
            'messages'   => $turns,
        ]);

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => [
                'content-type: application/json',
                'x-api-key: ' . $key,
                'anthropic-version: 2023-06-01',
            ],
        ]);
        $res = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($res === false || $code >= 400) {
            return null;
        }

        $data = json_decode((string) $res, true);

        return $data['content'][0]['text'] ?? null;
    }
}
