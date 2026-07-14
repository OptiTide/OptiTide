<?php

namespace App\Jobs;

use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Models\Blog;
use App\Models\SocialPost;
use App\Services\AI\ClaudeClient;
use App\Services\AI\ClaudeGenerationException;
use App\Services\AI\SocialPostPromptBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Drafts one promo social post per platform for a published blog article, each
 * left as PendingReview for VA approval — never auto-distributed. Idempotent:
 * skips a platform that already has a post for this blog, so a retry or a
 * re-publish can't duplicate drafts.
 */
class GenerateSocialPostsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public int $blogId) {}

    public function handle(ClaudeClient $claude, SocialPostPromptBuilder $builder): void
    {
        $blog = Blog::find($this->blogId);

        if ($blog === null) {
            return;
        }

        $url = route('blog.show', $blog);

        foreach (SocialPlatform::cases() as $platform) {
            $exists = SocialPost::where('blog_id', $blog->id)
                ->where('platform', $platform)
                ->exists();

            if ($exists) {
                continue;
            }

            try {
                $content = $claude->generate($builder->system(), $builder->user($platform, $blog, $url));
            } catch (ClaudeGenerationException) {
                continue; // skip this platform; a VA can add it by hand
            }

            SocialPost::create([
                'client_id' => null, // agency's own channels
                'blog_id' => $blog->id,
                'platform' => $platform,
                'content' => trim($content),
                'status' => SocialPostStatus::PendingReview,
            ]);
        }
    }
}
