<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Admin account. Created once and never overwritten, so a changed
        // password survives the seeder that runs on every deploy.
        // On a real server set ADMIN_EMAIL / ADMIN_PASSWORD in .env.
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@demo.com')],
            [
                'name' => 'Admin',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'Admin@12345')),
                'role' => 'admin',
                'is_active' => 1,
            ]
        );

        // Demo trainer / trainee accounts only for local testing
        if (app()->environment('production')) {
            return;
        }

        User::firstOrCreate(
            ['email' => 'trainer@demo.com'],
            [
                'name' => 'Demo Trainer',
                'password' => Hash::make('Trainer@12345'),
                'role' => 'trainer',
                'is_active' => 1,
                'age' => 28,
            ]
        );

        User::firstOrCreate(
            ['email' => 'trainee@demo.com'],
            [
                'name' => 'Demo Trainee',
                'password' => Hash::make('Trainee@12345'),
                'role' => 'trainee',
                'is_active' => 1,
                'age' => 24,
                'height' => 172,
                'weight' => 70,
            ]
        );
    }
}
