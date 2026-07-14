<?php

namespace App\Services\AI;

use App\Models\ClientSubmission;
use App\Models\Order;
use Illuminate\Support\Str;

/**
 * Builds the system prompt and user prompt for Stage 3 mockup generation.
 * Injects the client's brand context as strict XML constraint tags and
 * enforces Tailwind v4 + anti-repetition rules so every mockup is bespoke.
 */
class MockupPromptBuilder
{
    public function system(): string
    {
        return <<<'PROMPT'
        You are a Senior UI/UX Interaction Designer specializing in bespoke,
        brand-specific interfaces. You produce a single, complete, self-contained
        HTML document for a marketing website homepage.

        <output_rules>
        - Return ONLY the raw HTML document. No markdown fences, no commentary,
          no explanation before or after.
        - The document must be a full, valid HTML5 page: <!DOCTYPE html>, <head>
          with a viewport meta, and <body>.
        - Load Tailwind via <script src="https://cdn.tailwindcss.com"></script>
          in the head so the mockup renders standalone in an iframe.
        - Use ONLY Tailwind v4 utility classes. NEVER use deprecated v3 utilities
          such as bg-opacity-*, text-opacity-*, or flex-shrink-* — use the modern
          equivalents (bg-white/50, shrink-0, etc.).
        - No external assets except Google Fonts via <link>. Use inline SVG for
          icons and CSS gradients / solid colour blocks for imagery placeholders.
        - Do not include <form> actions, tracking scripts, or external JS beyond
          the Tailwind CDN.
        </output_rules>

        <design_dos_and_donts>
        - NEVER use a standard centered hero with heading text over a full-bleed
          stock photo — it is a repetitive crutch. Invent a distinctive layout.
        - NEVER default to a purple-to-blue gradient, Inter/Roboto/system fonts,
          or generic three-column feature-card grids.
        - DO commit to a cohesive visual identity built from the client's actual
          brand colours and industry. Vary structure: asymmetric hero, split
          layouts, editorial typography, offset grids, considered whitespace.
        - DO use micro-interactions (hover states, transitions) and a clear
          visual hierarchy. Make deliberate, opinionated design choices.
        </design_dos_and_donts>
        PROMPT;
    }

    public function user(Order $order, ClientSubmission $submission): string
    {
        $context = $this->contextXml($submission);
        $company = e($submission->data['business_name'] ?? $order->user->company_name ?? $order->user->name);

        return <<<PROMPT
        Design a bespoke marketing homepage for the following client. Use their
        exact brand colours, reflect their industry, and follow every rule in
        your instructions.

        {$context}

        Produce the complete HTML document now for {$company}.
        PROMPT;
    }

    /**
     * Renders the submission's answers + brand assets as strict XML constraint
     * tags for injection into the prompt.
     */
    protected function contextXml(ClientSubmission $submission): string
    {
        $lines = ['<client_context>'];

        foreach ($submission->data ?? [] as $key => $value) {
            if (filled($value)) {
                $label = Str::of($key)->replace('_', ' ')->title();
                $lines[] = '  <'.$key.' label="'.e($label).'">'.e((string) $value).'</'.$key.'>';
            }
        }

        $colours = collect($submission->brand_assets ?? [])
            ->filter(fn ($value, $key) => Str::contains($key, 'color') && is_string($value));

        if ($colours->isNotEmpty()) {
            $lines[] = '  <brand_colors>';
            foreach ($colours as $key => $hex) {
                $lines[] = '    <'.$key.'>'.e($hex).'</'.$key.'>';
            }
            $lines[] = '  </brand_colors>';
        }

        $lines[] = '  <constraints>';
        $lines[] = '    Use Tailwind v4 utilities only. Build the palette from the brand colours above.';
        $lines[] = '    Do not use a centered-hero-over-stock-photo layout.';
        $lines[] = '  </constraints>';
        $lines[] = '</client_context>';

        return implode("\n", $lines);
    }
}
