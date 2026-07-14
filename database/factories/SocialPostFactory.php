<?php

namespace Database\Factories;

use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\SocialPost>
 */
class SocialPostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => null, // agency's own channels
            'blog_id' => null,
            'platform' => fake()->randomElement(SocialPlatform::cases()),
            'content' => fake()->sentence(12).' https://optitide.test/blog/example',
            'status' => SocialPostStatus::PendingReview,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => SocialPostStatus::Approved,
            'scheduled_for' => now()->subMinute(),
        ]);
    }

    public function pendingReview(): static
    {
        return $this->state(fn () => ['status' => SocialPostStatus::PendingReview]);
    }
}
