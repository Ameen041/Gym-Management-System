<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::updateOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make('Admin@12345'),
                'role' => 'admin',
                'is_active' => 1,
                'age' => 30,
                'height' => 175,
                'weight' => 75,
            ]
        );

        // Trainer
        User::updateOrCreate(
            ['email' => 'trainer@demo.com'],
            [
                'name' => 'Demo Trainer',
                'password' => Hash::make('Trainer@12345'),
                'role' => 'trainer',
                'is_active' => 1,
                'age' => 28,
                'height' => 178,
                'weight' => 78,
            ]
        );

        // Trainee
        User::updateOrCreate(
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