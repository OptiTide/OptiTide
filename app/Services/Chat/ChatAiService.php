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

        $brand = config('company.brand_name') ?: config('app.name', 'OptiTide');

        if ($last !== '' && (str_contains($last, 'price') || str_contains($last, 'cost') || str_contains($last, 'how much') || str_contains($last, 'quote'))) {
            // Real prices from the live catalogue — never hardcode, or the bot
            // will quote figures the admin has already changed.
            $lines = self::priceLines();

            return $lines
                ? 'Great question! Our current pricing (GST-inclusive ' . (config('company.currency') ?: 'AUD') . '): ' . implode('; ', $lines)
                    . '. Want a tailored quote? Leave your email and our team will be in touch quickly.'
                : 'Happy to help with pricing — leave your email and our team will send you a tailored quote quickly.';
        }
        if ($last !== '' && (str_contains($last, 'hour') || str_contains($last, 'open') || str_contains($last, 'contact') || str_contains($last, 'phone'))) {
            $reach = config('company.email');
            if (config('company.phone')) {
                $reach .= ' or ' . config('company.phone');
            }
            if (config('company.hours')) {
                $reach .= ' (' . config('company.hours') . ')';
            }

            return 'We\'re here to help. Drop your question and email here and our team will get back to you fast — or reach us at ' . $reach . '.';
        }

        $offer = self::serviceLineList();

        return 'Thanks for reaching out to ' . $brand . '! Tell me a little about what you need'
            . ($offer ? ' — ' . $offer . ' — ' : ' ')
            . 'and I\'ll point you in the right direction. Our team is notified too and will jump in shortly.';
    }

    /** "Starter Website $750.00, …" per service line, from the live catalogue. */
    public static function priceLines(): array
    {
        $lines = [];
        foreach (\App\Support\Catalog::grouped() as $group) {
            $plans = [];
            foreach ($group['plans'] as $plan) {
                $plans[] = (int) $plan['price_cents'] === 0
                    ? $plan['name'] . ' (custom quote)'
                    : $plan['name'] . ' ' . money((int) $plan['price_cents'], $plan['currency'] ?? 'AUD')->format()
                        . \App\Support\Catalog::suffix($plan);
            }
            $lines[] = $group['line']['name'] . ': ' . implode(', ', $plans);
        }

        return $lines;
    }

    /** "web design, SEO or hosting" — the real service lines, not a frozen list. */
    public static function serviceLineList(): string
    {
        // Drop the trailing acronym — "Search Engine Optimisation (SEO)" reads
        // as a mouthful mid-sentence in a chat greeting.
        $names = array_map(
            fn ($n) => trim(preg_replace('/\s*\([^)]*\)\s*$/', '', (string) $n)),
            array_column(\App\Models\ServiceCategory::ordered(), 'name')
        );
        $names = array_values(array_filter($names));
        if ($names === []) {
            return '';
        }
        if (count($names) === 1) {
            return $names[0];
        }
        $last = array_pop($names);

        return implode(', ', $names) . ' or ' . $last;
    }

    /** @param array<int,array<string,mixed>> $messages */
    /**
     * Built from the LIVE catalogue + company settings, never hardcoded — so the
     * bot can't quote a customer a price that's been changed in the admin.
     */
    private function systemPrompt(): string
    {
        $company = config('company');
        $name = config('company.brand_name');

        $lines = self::priceLines();
        $pricing = $lines
            ? 'Current services & pricing (GST-inclusive ' . ($company['currency'] ?: 'AUD') . ') — ' . implode(' | ', $lines) . '. '
            : 'If asked about pricing, offer to have a human send a tailored quote. ';

        $contact = 'Contact: ' . $company['email'];
        if (! empty($company['phone'])) {
            $contact .= ', phone ' . $company['phone'];
        }
        if (! empty($company['hours'])) {
            $contact .= ' (' . $company['hours'] . ')';
        }

        return "You are the friendly 24/7 AI assistant for {$name}, an Australian digital agency. "
            . $pricing
            . $contact . '. Tagline: "Grow Online. Lead Always." '
            . 'Be warm, concise and genuinely helpful, no jargon, Australian spelling. Help with pre-sales and support questions. '
            . 'Only quote the prices listed above — if something is not listed, say you will have a teammate confirm. '
            . 'If you cannot resolve something, reassure them a human teammate will follow up. Never invent facts or guarantee search rankings.';
    }

    private function callAnthropic(array $messages): ?string
    {
        $key = trim((string) config('ai.anthropic_key', ''));
        $model = (string) config('ai.model', 'claude-haiku-4-5-20251001');

        $system = $this->systemPrompt();

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
