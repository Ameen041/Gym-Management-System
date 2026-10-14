<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $production = app()->environment('production');

        // Admin account. Created once and never overwritten, so a changed
        // password survives the seeder that runs on every deploy.
        // On a real server ADMIN_EMAIL / ADMIN_PASSWORD must be set in .env:
        // there is no default password there, so nobody can guess it.
        $email = env('ADMIN_EMAIL') ?: ($production ? null : 'admin@demo.com');
        $password = env('ADMIN_PASSWORD') ?: ($production ? null : 'Admin@12345');

        if (!$email || !$password || ($production && strlen($password) < 10)) {
            if (!User::where('role', 'admin')->exists()) {
                $this->command?->warn('No admin account created: set ADMIN_EMAIL and ADMIN_PASSWORD (10+ characters) in .env, then run the seeder again.');
            }
        } else {
            User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'Admin',
                    'password' => Hash::make($password),
                    'role' => 'admin',
                    'is_active' => 1,
                ]
            );
        }

        // Demo trainer / trainee accounts only for local testing
        if ($production) {
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
