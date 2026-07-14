<?php

namespace Database\Factories;

use App\Enums\CmsPageStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\CmsPage>
 */
class CmsPageFactory extends Factory
{
    public function definition(): array
    {
        $title = rtrim(fake()->sentence(3), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 100000),
            'excerpt' => fake()->sentence(10),
            'body' => '<h2>'.fake()->sentence(3).'</h2><p>'.fake()->paragraph().'</p>',
            'status' => CmsPageStatus::Draft,
            'show_in_footer' => false,
            'meta' => [],
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => CmsPageStatus::Published]);
    }

    public function inFooter(): static
    {
        return $this->state(fn () => ['status' => CmsPageStatus::Published, 'show_in_footer' => true]);
    }
}
