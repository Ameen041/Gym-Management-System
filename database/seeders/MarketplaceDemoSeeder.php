<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo coaches with approved profiles and packages, for local testing only.
 */
class MarketplaceDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $coaches = [
            [
                'email' => 'trainer@demo.com', 'name' => 'Demo Trainer', 'phone' => '0944000001',
                'profile' => [
                    'bio' => "Certified personal trainer with 6 years of experience in gyms in Damascus.\nI build simple plans you can follow in any gym and I follow up every week.",
                    'specialties' => ['muscle_gain', 'strength', 'fitness'], 'experience_years' => 6, 'city' => 'damascus', 'gender' => 'male',
                ],
                'packages' => [
                    ['title' => 'Workout plan', 'includes_workout' => true, 'includes_nutrition' => false, 'price' => 25],
                    ['title' => 'Full coaching', 'includes_workout' => true, 'includes_nutrition' => true, 'price' => 40,
                     'description' => 'Workout + nutrition plan, weekly updates and WhatsApp follow-up.'],
                ],
            ],
            [
                'email' => 'coach.lina@demo.com', 'name' => 'Lina Haddad', 'phone' => '0944000002',
                'profile' => [
                    'bio' => 'Women fitness coach and sports nutrition specialist. I help women lose weight and get stronger without crash diets.',
                    'specialties' => ['weight_loss', 'women', 'nutrition'], 'experience_years' => 4, 'city' => 'latakia', 'gender' => 'female',
                ],
                'packages' => [
                    ['title' => 'Monthly coaching', 'includes_workout' => true, 'includes_nutrition' => true, 'price' => 35],
                ],
            ],
            [
                'email' => 'coach.omar@demo.com', 'name' => 'Omar Khaled', 'phone' => '0944000003',
                'profile' => [
                    'bio' => 'Bodybuilding coach, national championship finalist. Hypertrophy programs and contest preparation.',
                    'specialties' => ['bodybuilding', 'muscle_gain'], 'experience_years' => 9, 'city' => 'aleppo', 'gender' => 'male',
                ],
                'packages' => [
                    ['title' => 'Hypertrophy program', 'includes_workout' => true, 'includes_nutrition' => false, 'price' => 30],
                    ['title' => 'Contest preparation', 'includes_workout' => true, 'includes_nutrition' => true, 'price' => 60],
                ],
            ],
        ];

        foreach ($coaches as $data) {
            $coach = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('Trainer@12345'), 'role' => 'trainer', 'is_active' => 1]
            );
            if (!$coach->phone) {
                $coach->update(['phone' => $data['phone']]);
            }

            $profile = $coach->ensureCoachProfile();
            if (blank($profile->bio)) {
                $profile->update($data['profile'] + ['status' => 'approved']);
            }

            if (!$coach->packages()->exists()) {
                foreach ($data['packages'] as $package) {
                    $coach->packages()->create($package + ['duration_days' => 30, 'is_active' => true]);
                }
            }
        }
    }
}
