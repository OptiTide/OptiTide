<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => null,
            'company' => fake()->company(),
            'website_url' => 'https://'.fake()->domainName(),
            'source' => 'contact_form',
            'status' => LeadStatus::New,
        ];
    }

    public function seoAudit(): static
    {
        return $this->state(fn () => ['source' => 'seo_audit']);
    }
}
