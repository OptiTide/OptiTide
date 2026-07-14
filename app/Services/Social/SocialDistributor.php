<?php

namespace App\Services\Social;

use App\Models\SocialPost;

interface SocialDistributor
{
    /**
     * Publish a post to its platform and return the platform-side post id.
     * Throws SocialDistributionException on failure.
     */
    public function publish(SocialPost $post): string;
}
