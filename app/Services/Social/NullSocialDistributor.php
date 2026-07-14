<?php

namespace App\Services\Social;

use App\Models\SocialPost;
use Illuminate\Support\Facades\Log;

/**
 * Default distributor: no real platform driver is wired yet, so it FAILS CLOSED
 * — nothing is silently marked "published" without actually going out. Wiring a
 * real driver (e.g. hamzahassanm/laravel-social-auto-post) + platform
 * credentials means binding a replacement to the SocialDistributor interface.
 */
class NullSocialDistributor implements SocialDistributor
{
    public function publish(SocialPost $post): string
    {
        Log::warning('Social distribution attempted but no platform driver is configured.', [
            'social_post_id' => $post->id,
            'platform' => $post->platform->value,
        ]);

        throw new SocialDistributionException(
            'Social distribution is not configured. Install and bind a platform '
            .'driver (e.g. hamzahassanm/laravel-social-auto-post) to enable posting.'
        );
    }
}
