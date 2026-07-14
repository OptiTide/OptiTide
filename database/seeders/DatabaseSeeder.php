<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Model events stay enabled so User::created generates referral codes.
     */
    public function run(): void
    {
        // Local development logins — change these credentials before any deploy.
        User::updateOrCreate(
            ['email' => 'michaeldemae@gmail.com'],
            ['name' => 'Michael', 'password' => 'password', 'role' => 'admin'],
        );

        User::updateOrCreate(
            ['email' => 'va@optitide.io'],
            ['name' => 'Virtual Assistant', 'password' => 'password', 'role' => 'va'],
        );

        User::updateOrCreate(
            ['email' => 'client@example.com'],
            ['name' => 'Demo Client', 'password' => 'password', 'role' => 'client', 'company_name' => 'Demo Pty Ltd'],
        );

        $this->call([
            ProductSeeder::class,
            FormSchemaSeeder::class,
            CmsPageSeeder::class,
            ServerMonitorSeeder::class,
        ]);
    }
}
