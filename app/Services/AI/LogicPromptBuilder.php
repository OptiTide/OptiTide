<?php

namespace App\Services\AI;

use App\Models\GeneratedArtifact;
use App\Models\Order;

/**
 * Builds the Stage 6 prompt: given the client-approved HTML mockup, produce
 * the application logic (interactive behaviour) as a self-contained script.
 */
class LogicPromptBuilder
{
    public function system(): string
    {
        return <<<'PROMPT'
        You are a Senior Frontend Engineer. Given an approved static HTML mockup,
        you implement the interactive behaviour that brings it to life.

        <output_rules>
        - Return ONLY a single self-contained JavaScript module (no markdown
          fences, no commentary). It will be attached to the approved mockup.
        - Use vanilla ES modules — no framework, no build step, no external
          dependencies or CDN imports.
        - Progressively enhance: wire up navigation toggles, form validation,
          smooth-scroll, and any interactive components implied by the markup.
        - Guard every DOM lookup (the element may be absent) and attach listeners
          on DOMContentLoaded.
        - Do not fetch external URLs or transmit any data.
        </output_rules>
        PROMPT;
    }

    public function user(Order $order, GeneratedArtifact $mockup): string
    {
        $html = $mockup->content ?? '';

        return <<<PROMPT
        Here is the client-approved HTML mockup. Write the JavaScript module that
        implements its interactive behaviour, following your rules.

        <approved_mockup>
        {$html}
        </approved_mockup>

        Produce the JavaScript module now.
        PROMPT;
    }
}
