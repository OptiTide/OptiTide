<?php

namespace App\Services\AI;

/**
 * Stand-in Claude client used in tests and when no API key is configured.
 * Produces clearly-labelled placeholder output so a keyless local
 * environment can still exercise the full pipeline without pretending to be
 * real AI output. Records the last call for test assertions.
 */
class FakeClaudeClient implements ClaudeClient
{
    public ?string $lastSystem = null;

    public ?string $lastPrompt = null;

    public ?string $nextResponse = null;

    public bool $shouldThrow = false;

    /** Text deltas emitted by the most recent stream() call (test assertions). */
    public array $streamedDeltas = [];

    public function generate(string $system, string $prompt, int $maxTokens = 16000): string
    {
        $this->lastSystem = $system;
        $this->lastPrompt = $prompt;

        if ($this->shouldThrow) {
            throw new ClaudeGenerationException('Simulated generation failure.');
        }

        if ($this->nextResponse !== null) {
            return $this->nextResponse;
        }

        // Return type-appropriate placeholder so downstream guards pass: a
        // plain-text email for reminders, JSON for blogs, text for social, JS
        // for logic, HTML otherwise.
        if (str_contains($system, 'payment reminder email')) {
            return "Hi there,\n\nThis is a friendly reminder that your invoice is currently outstanding. "
                ."We'd appreciate it if you could arrange payment at your earliest convenience. "
                ."If you've already paid or there's anything we can help with, just reply to this email.";
        }

        if (str_contains($system, 'SEO audit report')) {
            return json_encode([
                'overall_score' => 62,
                'summary' => 'A solid foundation with clear quick wins. Placeholder audit from the fake '
                    .'Claude client — configure ANTHROPIC_API_KEY for a real analysis.',
                'findings' => [
                    ['area' => 'Title tag', 'severity' => 'warning', 'detail' => 'Title is present but generic.', 'recommendation' => 'Lead with your primary keyword and location.'],
                    ['area' => 'Meta description', 'severity' => 'critical', 'detail' => 'No meta description found.', 'recommendation' => 'Add a 150-160 character description.'],
                    ['area' => 'Mobile', 'severity' => 'good', 'detail' => 'Viewport meta tag is present.', 'recommendation' => 'Keep testing on real devices.'],
                ],
                'quick_wins' => ['Add a meta description', 'Compress hero images', 'Add alt text to images'],
            ]);
        }

        // Check social BEFORE blog — the social system prompt itself mentions
        // "blog article", so the more specific marker must win.
        if (str_contains($system, 'social media post')) {
            return "Placeholder social post from the fake Claude client. "
                .'Configure ANTHROPIC_API_KEY for real, platform-tailored copy. #OptiTide';
        }

        if (str_contains($system, 'blog article')) {
            return json_encode([
                'title' => 'Placeholder Blog Article',
                'excerpt' => 'A stand-in article produced by the fake Claude client for keyless local development.',
                'body' => '<h2>Introduction</h2><p>This is placeholder blog content. Configure ANTHROPIC_API_KEY '
                    .'to generate a real, SEO-optimised article.</p><h2>Why it matters</h2><p>Search visibility '
                    .'compounds over time.</p>',
                'meta_title' => 'Placeholder Blog Article | OptiTide',
                'meta_description' => 'A stand-in meta description used for keyless local development and tests, '
                    .'sized to a realistic length for search engine result snippets.',
                'focus_keywords' => ['placeholder', 'seo', 'optitide'],
            ]);
        }

        if (str_contains($system, 'JavaScript module')) {
            return <<<'JS'
            // Placeholder application logic from the fake Claude client.
            document.addEventListener('DOMContentLoaded', () => {
                const nav = document.querySelector('[data-nav-toggle]');
                nav?.addEventListener('click', () => document.body.classList.toggle('nav-open'));
            });
            JS;
        }

        return <<<'HTML'
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <script src="https://cdn.tailwindcss.com"></script>
            <title>Placeholder Mockup</title>
        </head>
        <body class="bg-slate-50 text-slate-800">
            <header class="border-b border-slate-200 bg-white px-8 py-5">
                <p class="text-lg font-bold">Placeholder Mockup</p>
            </header>
            <main class="mx-auto max-w-3xl px-8 py-16">
                <h1 class="text-3xl font-bold">Generated placeholder</h1>
                <p class="mt-3 text-slate-600">
                    This is stand-in output from the fake Claude client. Configure
                    ANTHROPIC_API_KEY to generate a real, brand-specific mockup.
                </p>
            </main>
        </body>
        </html>
        HTML;
    }

    public function stream(string $system, string $prompt, callable $onDelta, int $maxTokens = 4000): string
    {
        $this->lastSystem = $system;
        $this->lastPrompt = $prompt;
        $this->streamedDeltas = [];

        if ($this->shouldThrow) {
            throw new ClaudeGenerationException('Simulated streaming failure.');
        }

        $reply = $this->nextResponse
            ?? "Thanks for reaching out! I can help with that. Could you share a little "
              .'more detail so I can point you in the right direction?';

        // Emit word-by-word so tests can assert incremental delta broadcasting.
        foreach (preg_split('/(\s+)/', $reply, -1, PREG_SPLIT_DELIM_CAPTURE) as $chunk) {
            if ($chunk === '') {
                continue;
            }
            $this->streamedDeltas[] = $chunk;
            $onDelta($chunk);
        }

        return $reply;
    }
}
