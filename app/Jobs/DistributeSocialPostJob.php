<?php

namespace App\Jobs;

use App\Enums\SocialPostStatus;
use App\Models\SocialPost;
use App\Services\Social\SocialDistributor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Distributes one approved/scheduled social post to its platform. Uses an
 * atomic compare-and-swap CLAIM so a re-dispatch or concurrent run can't post
 * the same content twice — this is deliberately AT-MOST-ONCE (a dropped post is
 * far safer than a public duplicate). A distribution failure records the error
 * and marks the post Failed.
 */
class DistributeSocialPostJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public function __construct(public int $postId) {}

    public function handle(SocialDistributor $distributor): void
    {
        // Only the worker that flips it out of a sendable state proceeds.
        $claimed = SocialPost::where('id', $this->postId)
            ->whereIn('status', [SocialPostStatus::Approved, SocialPostStatus::Scheduled])
            ->update([
                'status' => SocialPostStatus::Published,
                'published_at' => now(),
            ]);

        if ($claimed === 0) {
            return; // already published/failed, or claimed by another worker
        }

        $post = SocialPost::find($this->postId);

        try {
            $externalId = $distributor->publish($post);
            $post->forceFill(['external_id' => $externalId, 'error' => null])->save();
        } catch (Throwable $e) {
            // Catch ANY driver error (not just SocialDistributionException) so a
            // post is never left claimed-as-Published but not actually sent.
            // Failed is a terminal state; a VA re-queues it with the Retry action.
            $post->forceFill([
                'status' => SocialPostStatus::Failed,
                'published_at' => null,
                'error' => $e->getMessage(),
            ])->save();
        }
    }
}
