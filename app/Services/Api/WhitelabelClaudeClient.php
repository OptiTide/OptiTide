<?php

namespace App\Services\Api;

/**
 * Thin upstream client for the resold API. Calls the provider's Messages API and
 * returns the completion text ALONG WITH token usage (so we can meter + bill).
 * The provider is never named in anything the client receives — the controller
 * re-brands the response envelope.
 */
final class WhitelabelClaudeClient
{
    /** The resale endpoint can serve only when enabled AND an upstream key is set. */
    public function available(): bool
    {
        return (bool) config('api_credits.enabled')
            && trim((string) config('ai.anthropic_key', '')) !== '';
    }

    /**
     * @param  array  $messages  [['role'=>'user'|'assistant','content'=>string], ...]
     * @return array{ok:bool,content:?string,usage:array{input_tokens:int,output_tokens:int},error:?string,status:int}
     */
    public function complete(string $realModel, array $messages, int $maxTokens, ?string $system = null): array
    {
        $key = trim((string) config('ai.anthropic_key', ''));

        $body = [
            'model'      => $realModel,
            'max_tokens' => $maxTokens,
            'messages'   => $messages,
        ];
        if ($system !== null && $system !== '') {
            $body['system'] = $system;
        }

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER     => [
                'content-type: application/json',
                'x-api-key: ' . $key,
                'anthropic-version: 2023-06-01',
            ],
        ]);
        $res = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $usage = ['input_tokens' => 0, 'output_tokens' => 0];

        if ($res === false) {
            return ['ok' => false, 'content' => null, 'usage' => $usage, 'error' => 'Upstream request failed.', 'status' => 502];
        }

        $data = json_decode((string) $res, true);
        if ($status >= 400 || ! is_array($data)) {
            // Never surface the provider's raw error verbatim (it names them).
            return ['ok' => false, 'content' => null, 'usage' => $usage, 'error' => 'The model could not process this request.', 'status' => $status >= 400 ? 502 : 502];
        }

        $usage = [
            'input_tokens'  => (int) ($data['usage']['input_tokens'] ?? 0),
            'output_tokens' => (int) ($data['usage']['output_tokens'] ?? 0),
        ];

        // Concatenate any text blocks.
        $text = '';
        foreach (($data['content'] ?? []) as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= $block['text'] ?? '';
            }
        }

        return ['ok' => true, 'content' => $text, 'usage' => $usage, 'error' => null, 'status' => 200];
    }
}
