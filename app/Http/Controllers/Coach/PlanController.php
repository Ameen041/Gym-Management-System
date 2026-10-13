<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\NutritionPlan;
use App\Models\PlanTemplate;
use App\Models\Subscription;
use App\Models\WorkoutPlan;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * The coach builds (or updates) the weekly workout / nutrition plan of a client.
 * Workout rows can be picked from the exercise library, so the client gets a
 * link to the exercise video next to each row.
 */
class PlanController extends Controller
{
    public const WEEK_DAYS = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    public function edit(Subscription $subscription, string $type)
    {
        $this->authorizePlan($subscription, $type);
        $subscription->load('trainee');

        $plan = $type === 'workout' ? $subscription->workoutPlan : $subscription->nutritionPlan;

        return view('coach.plan-builder', [
            'subscription' => $subscription,
            'type'         => $type,
            'plan'         => $plan,
            'details'      => $plan ? (json_decode($plan->plan_details, true) ?: []) : [],
            'templates'    => PlanTemplate::where('type', $type)->where('is_active', true)->orderBy('title')->get(),
            'library'      => $type === 'workout'
                ? Exercise::active()->get()->sortBy(fn ($e) => $e->display_name)->values()
                : collect(),
            'weekDays'     => self::WEEK_DAYS,
        ]);
    }

    public function save(Request $request, Subscription $subscription, string $type)
    {
        $this->authorizePlan($subscription, $type);

        $request->validate([
            'title' => 'required|string|max:255',
            'day'   => 'required|array',
            'day.*' => 'required|in:' . implode(',', self::WEEK_DAYS),
        ]);

        // Each row: [day, values...]. A row whose main field is empty is skipped (rest day).
        $fields = $type === 'workout'
            ? ['muscle', 'exercise', 'sets', 'reps']
            : ['meal_number', 'description', 'calories', 'protein', 'carbs', 'fat'];
        $mainField = $type === 'workout' ? 'exercise' : 'description';
        $validIds = $type === 'workout'
            ? Exercise::whereIn('id', array_filter((array) $request->exercise_id))->pluck('id')->all()
            : [];

        $details = [];
        foreach ((array) $request->day as $i => $day) {
            $row = [];
            foreach ($fields as $field) {
                $row[$field] = trim((string) ($request->input($field)[$i] ?? ''));
            }
            if ($row[$mainField] === '') {
                continue;
            }
            if (in_array('', $row, true)) {
                throw ValidationException::withMessages([
                    $mainField => __('Fill in every field of :day row ":name", or empty the row to make it a rest day.', [
                        'day' => __($day), 'name' => mb_strimwidth($row[$mainField], 0, 40, '…'),
                    ]),
                ]);
            }
            if ($type === 'workout') {
                $exerciseId = (int) ($request->exercise_id[$i] ?? 0);
                $row['exercise_id'] = in_array($exerciseId, $validIds, true) ? $exerciseId : null;
            }
            $details[$day][] = $row;
        }

        if (empty($details)) {
            throw ValidationException::withMessages([$mainField => __('The plan is empty. Add at least one row.')]);
        }

        $model = $type === 'workout' ? WorkoutPlan::class : NutritionPlan::class;
        $model::updateOrCreate(
            ['subscription_id' => $subscription->id],
            [
                'user_id'      => $subscription->trainee_id,
                'trainer_id'   => $subscription->coach_id,
                'title'        => $request->title,
                'plan_details' => json_encode($details, JSON_UNESCAPED_UNICODE),
            ]
        );

        return redirect()->route('coach.clients.show', $subscription)
            ->with('success', __('The plan was sent to your client.'));
    }

    private function authorizePlan(Subscription $subscription, string $type): void
    {
        abort_unless($subscription->coach_id === auth()->id() && $subscription->status === 'active', 404);
        abort_unless($type === 'workout' ? $subscription->includes_workout : $subscription->includes_nutrition, 404);
    }
}
