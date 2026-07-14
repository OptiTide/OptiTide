<?php

namespace App\Console\Commands;

use App\Jobs\DistributeSocialPostJob;
use App\Models\SocialPost;
use Illuminate\Console\Command;

/**
 * Dispatches distribution jobs for approved/scheduled social posts whose time
 * has arrived. Runs on the schedule (every 15 min); also invokable by hand.
 */
class DistributeDueSocialPosts extends Command
{
    protected $signature = 'social:distribute-due';

    protected $description = 'Distribute approved social posts whose scheduled time has come';

    public function handle(): int
    {
        $dispatched = 0;

        SocialPost::dueForPublishing()->each(function (SocialPost $post) use (&$dispatched) {
            DistributeSocialPostJob::dispatch($post->id);
            $dispatched++;
        });

        $this->info("Dispatched {$dispatched} social post distribution(s).");

        return self::SUCCESS;
    }
}
