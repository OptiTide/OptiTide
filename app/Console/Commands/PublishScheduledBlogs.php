<?php

namespace App\Console\Commands;

use App\Enums\BlogStatus;
use App\Jobs\GenerateSocialPostsJob;
use App\Models\Blog;
use Illuminate\Console\Command;

/**
 * Publishes scheduled blog articles whose time has arrived, and queues promo
 * social posts for each (the SEO engine feeding the SMM engine). Runs on the
 * daily schedule; also invokable by hand.
 */
class PublishScheduledBlogs extends Command
{
    protected $signature = 'blogs:publish-due';

    protected $description = 'Publish scheduled blog articles whose time has come';

    public function handle(): int
    {
        $published = 0;

        Blog::dueForPublishing()->each(function (Blog $blog) use (&$published) {
            $blog->forceFill([
                'status' => BlogStatus::Published,
                'published_at' => now(),
            ])->save();

            // Draft promo posts for VA approval — never auto-distributed.
            GenerateSocialPostsJob::dispatch($blog->id);
            $published++;
        });

        $this->info("Published {$published} blog article(s).");

        return self::SUCCESS;
    }
}
