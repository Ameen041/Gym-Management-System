<?php

namespace Database\Seeders;

use App\Models\CheckIn;
use App\Models\Exercise;
use App\Models\NutritionPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WorkoutPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Local demo of weekly check-ins: trainee rami@demo.com, five weeks into a
 * "Full coaching" subscription with trainer@demo.com, losing weight.
 * The last check-in has no reply yet, so the coach sees it waiting.
 */
class CheckInDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $coach = User::where('email', 'trainer@demo.com')->first();
        if (!$coach) {
            return;
        }

        $trainee = User::firstOrCreate(
            ['email' => 'rami@demo.com'],
            ['name' => 'Rami Saleh', 'password' => Hash::make('Trainee@12345'), 'role' => 'trainee', 'is_active' => 1,
             'phone' => '0944000010', 'age' => 29, 'height' => 178, 'weight' => 92.4]
        );
        if (CheckIn::where('trainee_id', $trainee->id)->exists()) {
            return;
        }

        // the last check-in was 6 days ago, so the next one opens today
        $start = today()->subDays(41)->setTime(10, 0);
        $sub = Subscription::create([
            'trainee_id' => $trainee->id, 'coach_id' => $coach->id,
            'package_id' => $coach->packages()->where('includes_nutrition', true)->value('id'),
            'package_title' => 'Full coaching', 'includes_workout' => true, 'includes_nutrition' => true,
            'duration_days' => 60, 'price' => 40, 'commission_rate' => 10, 'commission_amount' => 4, 'coach_amount' => 36,
            'status' => 'active', 'payment_method' => 'sham_cash', 'payment_reference' => 'DEMO-1001',
            'intake' => ['goal' => 'weight_loss', 'level' => 'beginner', 'days_per_week' => 4, 'age' => 29, 'height' => 178, 'weight' => 92.4,
                         'goal_details' => 'Lose the belly before summer.'],
            'payment_submitted_at' => $start->copy()->subDay(), 'confirmed_at' => $start, 'starts_at' => $start, 'ends_at' => $start->copy()->addDays(60),
        ]);

        $bench = Exercise::where('name', 'Barbell Bench Press')->value('id');
        $squat = Exercise::where('name', 'like', '%Squat%')->value('id');
        WorkoutPlan::create([
            'user_id' => $trainee->id, 'trainer_id' => $coach->id, 'subscription_id' => $sub->id, 'title' => 'Fat loss - 4 days',
            'plan_details' => json_encode([
                'Saturday'  => [['muscle' => 'Chest', 'exercise' => 'Barbell Bench Press', 'sets' => '4', 'reps' => '8-10', 'exercise_id' => $bench]],
                'Monday'    => [['muscle' => 'Legs', 'exercise' => 'Barbell Squat', 'sets' => '4', 'reps' => '8-10', 'exercise_id' => $squat]],
            ]),
            'created_at' => $start->copy()->addDay(), 'updated_at' => $start->copy()->addDay(),
        ]);
        NutritionPlan::create([
            'user_id' => $trainee->id, 'trainer_id' => $coach->id, 'subscription_id' => $sub->id, 'title' => '2200 kcal',
            'targets' => ['calories' => 2200, 'protein' => 165, 'carbs' => 220, 'fat' => 70],
            'plan_details' => json_encode(['Saturday' => [[
                'meal_number' => 1, 'description' => '', 'calories' => 350, 'protein' => 20, 'carbs' => 45, 'fat' => 10,
                'items' => [['food_id' => null, 'name' => 'Oats with milk', 'qty' => null, 'unit' => 'g', 'grams' => null,
                             'calories' => 350, 'protein' => 20, 'carbs' => 45, 'fat' => 10]],
            ]]]),
            'created_at' => $start->copy()->addDay(), 'updated_at' => $start->copy()->addDay(),
        ]);

        $weeks = [
            [91.6, 96, 3, 4, 3, 3, 'First week was hard, I was hungry in the evenings.', 'Good start! Add a snack at 5 pm (yogurt + fruit) so the evenings are easier.'],
            [91.0, 95, 4, 4, 4, 4, 'Better. The snack helped.', 'Great, keep it. Try to add one more set on bench press.'],
            [90.7, 94.5, 4, 3, 3, 2, 'Bad sleep this week, exams.', 'Sleep affects hunger and recovery. Aim for 7 hours, even on busy days.'],
            [89.9, 93.5, 4, 4, 4, 4, 'Feeling strong, bench went up to 70 kg.', 'Excellent progress, 2.5 kg down in a month.'],
            [89.4, 93, 3, 4, 4, 4, 'Missed one workout because of work.', null],
        ];
        foreach ($weeks as $i => [$weight, $waist, $workouts, $diet, $energy, $sleep, $note, $reply]) {
            $at = $start->copy()->addDays(7 * ($i + 1))->setTime(19, 30);
            CheckIn::create([
                'subscription_id' => $sub->id, 'trainee_id' => $trainee->id, 'coach_id' => $coach->id,
                'weight' => $weight, 'waist' => $waist, 'workouts_done' => $workouts, 'diet_adherence' => $diet,
                'energy' => $energy, 'sleep' => $sleep, 'note' => $note,
                'coach_reply' => $reply, 'replied_at' => $reply ? $at->copy()->addHours(14) : null,
            ])->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
        }
        $trainee->update(['weight' => end($weeks)[0]]);
    }
}
