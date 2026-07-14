<?php

namespace Database\Seeders;

use App\Enums\MonitorStatus;
use App\Models\ServerMonitor;
use Illuminate\Database\Seeder;

class ServerMonitorSeeder extends Seeder
{
    public function run(): void
    {
        $monitors = [
            ['name' => 'Storefront', 'url' => 'https://optitide.io'],
            ['name' => 'Client portal', 'url' => 'https://app.optitide.io'],
            ['name' => 'WHM / cPanel', 'url' => 'https://server.optitide.io:2087'],
        ];

        foreach ($monitors as $monitor) {
            ServerMonitor::updateOrCreate(
                ['url' => $monitor['url']],
                ['name' => $monitor['name'], 'status' => MonitorStatus::Unknown, 'is_active' => true],
            );
        }
    }
}
