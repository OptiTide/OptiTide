<?php

namespace App\Jobs;

use App\Enums\BlogStatus;
use App\Models\Blog;
use App\Services\AI\BlogPromptBuilder;
use App\Services\AI\ClaudeClient;
use App\Services\AI\ClaudeGenerationException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

/**
 * Fills a draft Blog stub with an AI-generated, SEO-optimised article, then
 * moves it to PendingReview for a human to check before it is scheduled. On
 * failure the stub stays a draft with the error recorded in meta, so nothing
 * half-generated ever reaches the public site.
 */
class GenerateBlogJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public int $blogId) {}

    public function handle(ClaudeClient $claude, BlogPromptBuilder $builder): void
    {
        $blog = Blog::find($this->blogId);

        if ($blog === null) {
            return;
        }

        $topic = $blog->meta['topic'] ?? $blog->title;
        $keywords = $blog->focusKeywords();

        try {
            $raw = $claude->generate($builder->system(), $builder->user($topic, $keywords));
            $data = json_decode($raw, true);

            if (! is_array($data) || blank($data['title'] ?? null) || blank($data['body'] ?? null)) {
                throw new ClaudeGenerationException('Blog generation returned malformed JSON.');
            }
        } catch (ClaudeGenerationException $e) {
            $this->recordError($blog, $e->getMessage());

            return;
        }

        $meta = $blog->meta ?? [];
        unset($meta['generation_error']);
        $meta['topic'] = $topic;
        $meta['meta_title'] = $data['meta_title'] ?? $data['title'];
        $meta['meta_description'] = $data['meta_description'] ?? null;
        $meta['focus_keywords'] = $data['focus_keywords'] ?? $keywords;

        $blog->forceFill([
            'title' => $data['title'],
            // Re-slug from the real article title (still a fresh, unpublished
            // draft), keeping it unique against the constraint.
            'slug' => Blog::uniqueSlug(Str::slug($data['title']), $blog->id),
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'],
            'status' => BlogStatus::PendingReview,
            'is_ai_generated' => true,
            'meta' => $meta,
        ])->save();
    }

    public function failed(Throwable $e): void
    {
        if ($blog = Blog::find($this->blogId)) {
            $this->recordError($blog, $e->getMessage());
        }
    }

    protected function recordError(Blog $blog, string $message): void
    {
        $blog->forceFill([
            'meta' => array_merge($blog->meta ?? [], ['generation_error' => $message]),
        ])->save();
    }
}
