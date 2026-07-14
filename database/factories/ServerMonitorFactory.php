<?php

namespace Database\Factories;

use App\Enums\MonitorStatus;
use App\Models\ServerMonitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServerMonitor>
 */
class ServerMonitorFactory extends Factory
{
    protected $model = ServerMonitor::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).' server',
            'url' => 'https://'.fake()->domainName(),
            'status' => MonitorStatus::Unknown,
            'consecutive_failures' => 0,
            'is_active' => true,
        ];
    }

    public function up(): static
    {
        return $this->state(['status' => MonitorStatus::Up, 'last_checked_at' => now()]);
    }

    public function down(): static
    {
        return $this->state([
            'status' => MonitorStatus::Down,
            'consecutive_failures' => 3,
            'last_checked_at' => now(),
        ]);
    }
}
