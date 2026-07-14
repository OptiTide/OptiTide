<?php

namespace App\Services\AI;

use Anthropic\Client;
use Anthropic\Lib\Streaming\MessageAccumulator;
use Throwable;

/**
 * Real Claude client, backed by the official Anthropic SDK. Uses adaptive
 * thinking with a configurable effort level (opus-4-8 defaults). Sampling
 * params are intentionally omitted — they 400 on the current models.
 *
 * Streaming (not blocking create()) is used so large single-page HTML
 * generations don't hit HTTP request timeouts, and so truncation is detected
 * before the output is stored.
 */
class AnthropicClaudeClient implements ClaudeClient
{
    public function __construct(
        protected Client $client,
        protected string $model,
        protected string $effort,
    ) {}

    public function generate(string $system, string $prompt, int $maxTokens = 32000): string
    {
        $accumulator = MessageAccumulator::forMessages();

        try {
            $stream = $this->client->messages->createStream(
                maxTokens: $maxTokens,
                messages: [['role' => 'user', 'content' => $prompt]],
                model: $this->model,
                system: $system,
                thinking: ['type' => 'adaptive'],
                outputConfig: ['effort' => $this->effort],
                requestOptions: ['timeout' => 600],
            );

            foreach ($stream as $event) {
                $accumulator->accumulate($event);
            }
        } catch (Throwable $e) {
            throw new ClaudeGenerationException("Claude request failed: {$e->getMessage()}", previous: $e);
        }

        $message = $accumulator->message();

        // Safety classifiers may decline with a refusal stop reason.
        if ($message->stopReason === 'refusal') {
            throw new ClaudeGenerationException('Claude declined the request (refusal).');
        }

        // A truncated document is broken — reject it rather than store it.
        if ($message->stopReason === 'max_tokens') {
            throw new ClaudeGenerationException('Claude output was truncated at the token cap; the document is incomplete.');
        }

        $text = '';
        foreach ($message->content as $block) {
            if (($block->type ?? null) === 'text') {
                $text .= $block->text;
            }
        }

        if (trim($text) === '') {
            throw new ClaudeGenerationException('Claude returned no text content.');
        }

        return $this->stripCodeFence($text);
    }

    public function stream(string $system, string $prompt, callable $onDelta, int $maxTokens = 4000): string
    {
        $accumulator = MessageAccumulator::forMessages();

        try {
            $stream = $this->client->messages->createStream(
                maxTokens: $maxTokens,
                messages: [['role' => 'user', 'content' => $prompt]],
                model: $this->model,
                system: $system,
                thinking: ['type' => 'adaptive'],
                outputConfig: ['effort' => $this->effort],
                requestOptions: ['timeout' => 600],
            );

            foreach ($stream as $event) {
                $accumulator->accumulate($event);

                // Emit text deltas live. Guarded defensively: if the SDK event
                // shape differs, streaming silently degrades to "message appears
                // on completion" rather than breaking the reply.
                if (($event->type ?? null) === 'content_block_delta') {
                    $delta = $event->delta ?? null;
                    $text = is_object($delta) ? ($delta->text ?? null) : null;
                    if (is_string($text) && $text !== '') {
                        $onDelta($text);
                    }
                }
            }
        } catch (Throwable $e) {
            throw new ClaudeGenerationException("Claude request failed: {$e->getMessage()}", previous: $e);
        }

        $message = $accumulator->message();

        if ($message->stopReason === 'refusal') {
            throw new ClaudeGenerationException('Claude declined the request (refusal).');
        }

        $text = '';
        foreach ($message->content as $block) {
            if (($block->type ?? null) === 'text') {
                $text .= $block->text;
            }
        }

        // A conversational reply that hit the token cap is still usable; unlike
        // generated documents we don't reject on max_tokens here.
        if (trim($text) === '') {
            throw new ClaudeGenerationException('Claude returned no text content.');
        }

        return trim($text);
    }

    /**
     * Strip a single fenced-code wrapper (```lang … ```) if the model wrapped
     * the whole response in one despite the instruction not to — otherwise the
     * leading fence renders as literal text in the iframe.
     */
    protected function stripCodeFence(string $text): string
    {
        $trimmed = trim($text);

        if (preg_match('/^```[a-zA-Z0-9]*\s*\n(.*)\n```$/s', $trimmed, $m)) {
            return trim($m[1]);
        }

        return $trimmed;
    }
}
