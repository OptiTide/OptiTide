<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\ReferralRelationship>
 */
class ReferralRelationshipFactory extends Factory
{
    public function definition(): array
    {
        return [
            'referrer_id' => User::factory()->state(['role' => 'client']),
            'referred_id' => User::factory()->state(['role' => 'client']),
            'referral_code' => strtoupper(Str::random(8)),
            'converted_at' => null,
        ];
    }

    public function converted(): static
    {
        return $this->state(fn () => ['converted_at' => now()]);
    }
}
