<?php

namespace App\Services\AI;

use App\Enums\SocialPlatform;
use App\Models\Blog;

/**
 * Builds the prompt for a platform-tailored social media post promoting a blog
 * article. The model returns the post text only.
 */
class SocialPostPromptBuilder
{
    public function system(): string
    {
        return <<<'PROMPT'
        You write engaging social media post copy for OptiTide, an Australian
        digital agency. Each post promotes a blog article and drives clicks.

        <rules>
        - Output ONLY the post text — no quotes, no markdown, no "Here's your post".
        - Match the platform's norms (see the platform brief in the message).
        - Include the article URL. Add a light call to action.
        - Australian English. Do not invent statistics or testimonials.
        </rules>
        PROMPT;
    }

    public function user(SocialPlatform $platform, Blog $blog, string $url): string
    {
        $brief = match ($platform) {
            SocialPlatform::X => 'X (Twitter): under 280 characters, punchy, 1-2 relevant hashtags.',
            SocialPlatform::LinkedIn => 'LinkedIn: professional tone, 2-4 short paragraphs, up to 3 hashtags.',
            SocialPlatform::Instagram => 'Instagram: warm and visual, a few emoji, 5-8 hashtags on their own line.',
            SocialPlatform::Facebook => 'Facebook: friendly and conversational, 1-2 short paragraphs, minimal hashtags.',
        };

        $title = e($blog->title);
        $summary = e($blog->excerpt ?: $blog->metaDescription());

        return <<<PROMPT
        Write a post for this platform.

        <platform_brief>{$brief}</platform_brief>

        Article title: {$title}
        Article summary: {$summary}
        Article URL: {$url}

        Produce the post text now.
        PROMPT;
    }
}
