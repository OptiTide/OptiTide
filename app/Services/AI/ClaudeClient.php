<?php

namespace App\Services\AI;

interface ClaudeClient
{
    /**
     * Generate a completion from a system prompt + user prompt, returning the
     * assistant's text. Throws ClaudeGenerationException on refusal or error.
     */
    public function generate(string $system, string $prompt, int $maxTokens = 16000): string;

    /**
     * Stream a completion, invoking $onDelta($textDelta) for each text chunk as
     * it arrives, and returning the full concatenated text. Used for live chat
     * replies. Throws ClaudeGenerationException on refusal or error.
     *
     * @param  callable(string): void  $onDelta
     */
    public function stream(string $system, string $prompt, callable $onDelta, int $maxTokens = 4000): string;
}
