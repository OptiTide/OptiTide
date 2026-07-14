<?php

namespace Database\Factories;

use App\Enums\BlogStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Blog>
 */
class BlogFactory extends Factory
{
    public function definition(): array
    {
        $title = rtrim(fake()->sentence(6), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 100000),
            'excerpt' => fake()->sentence(12),
            'body' => '<h2>'.fake()->sentence(4).'</h2><p>'.fake()->paragraph().'</p>',
            'status' => BlogStatus::Draft,
            'author_id' => User::factory()->state(['role' => 'admin']),
            'meta' => ['meta_title' => $title, 'meta_description' => fake()->sentence(14), 'focus_keywords' => ['seo', 'web']],
            'is_ai_generated' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => BlogStatus::Published, 'published_at' => now()->subDay()]);
    }

    /** Scheduled for the past so the publish cron picks it up. */
    public function dueForPublishing(): static
    {
        return $this->state(fn () => ['status' => BlogStatus::Scheduled, 'scheduled_for' => now()->subMinute()]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => ['status' => BlogStatus::Scheduled, 'scheduled_for' => now()->addWeek()]);
    }
}
