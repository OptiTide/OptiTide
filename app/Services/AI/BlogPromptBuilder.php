<?php

namespace App\Services\AI;

/**
 * Builds the prompt for an SEO-optimised blog article. The model returns a
 * strict JSON object (title, excerpt, HTML body, SEO meta) that GenerateBlogJob
 * decodes — no prose or code fences around it.
 */
class BlogPromptBuilder
{
    public function system(): string
    {
        return <<<'PROMPT'
        You write SEO-optimised blog articles for OptiTide, an Australian digital
        agency (web design, SEO, social media, and hosting). You are producing a
        "blog article" for the public marketing site.

        <output_rules>
        - Respond with ONE JSON object and NOTHING else — no prose, no markdown,
          no code fences.
        - Schema:
          {
            "title": string,            // compelling, <= 65 chars
            "excerpt": string,          // 1-2 sentence summary, plain text
            "body": string,             // the article as clean semantic HTML
            "meta_title": string,       // <= 60 chars, keyword-led
            "meta_description": string, // 150-160 chars, plain text
            "focus_keywords": string[]  // 3-6 keywords/phrases
          }
        - body is HTML using ONLY <h2> <h3> <p> <ul> <ol> <li> <strong> <em>
          <a> <blockquote>. No <script>, <style>, <iframe>, inline styles, <h1>
          (the page renders the <h1> from title), or class attributes.
        - 600-1000 words, Australian English, helpful and specific — not fluffy.
          Use headings to structure it. Do not invent statistics or fake quotes.
        - Never include the client's private data; this is public content.
        </output_rules>
        PROMPT;
    }

    public function user(string $topic, array $keywords = []): string
    {
        $topic = e($topic);
        $kw = $keywords === [] ? '(none supplied — choose sensible ones)' : e(implode(', ', $keywords));

        return <<<PROMPT
        Write the blog article now.

        Topic: {$topic}
        Target keywords: {$kw}

        Return the JSON object.
        PROMPT;
    }
}
