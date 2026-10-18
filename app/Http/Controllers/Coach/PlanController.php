<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\Food;
use App\Models\NutritionPlan;
use App\Models\PlanTemplate;
use App\Models\Subscription;
use App\Models\WorkoutPlan;
use App\Support\Notify;
use App\Support\NutritionMath;
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
        // a renewal starts from the last plan, so the coach only changes what is needed
        $draftFrom = $plan ? null : $this->lastPlan($subscription, $type);
        $source = $plan ?? $draftFrom;

        if ($type === 'nutrition') {
            return view('coach.nutrition-builder', [
                'subscription' => $subscription,
                'plan'         => $plan,
                'draftFrom'    => $draftFrom,
                'targets'      => $source?->targets ?? [],
                // to show protein and fat per kg of body weight
                'weight'       => (float) ($subscription->trainee->weight ?: ($subscription->intake['weight'] ?? 0)),
                'days'         => NutritionMath::builderDays($source ? (json_decode($source->plan_details, true) ?: []) : [], self::WEEK_DAYS),
                'templates'    => PlanTemplate::where('type', 'nutrition')->where('is_active', true)->orderBy('title')->get(),
                'foods'        => Food::active()->get()->sortBy(fn ($f) => $f->display_name)->values(),
                'weekDays'     => self::WEEK_DAYS,
            ]);
        }

        return view('coach.workout-builder', [
            'subscription' => $subscription,
            'plan'         => $plan,
            'draftFrom'    => $draftFrom,
            'details'      => $source ? (json_decode($source->plan_details, true) ?: []) : [],
            'templates'    => PlanTemplate::where('type', 'workout')->where('is_active', true)->orderBy('title')->get(),
            'library'      => Exercise::active()->get()->sortBy(fn ($e) => $e->display_name)->values(),
            'weekDays'     => self::WEEK_DAYS,
        ]);
    }

    public function save(Request $request, Subscription $subscription, string $type)
    {
        $this->authorizePlan($subscription, $type);

        if ($type === 'nutrition') {
            $rules = ['title' => 'required|string|max:255', 'meals' => 'nullable|array|max:100'];
            $names = [];
            foreach (NutritionMath::TARGET_LIMITS as $macro => [$min, $max]) {
                $rules["targets.$macro"] = ['required', 'numeric', "between:$min,$max"];
                $names["targets.$macro"] = __(ucfirst($macro));
            }
            $request->validate($rules, [], $names);

            $targets = [];
            foreach (NutritionMath::MACROS as $macro) {
                $targets[$macro] = round((float) $request->input("targets.$macro"), $macro === 'calories' ? 0 : 1);
            }
            $details = NutritionMath::fromRequest((array) $request->input('meals', []), self::WEEK_DAYS);

            return $this->store($subscription, $type, $request->title, $details, $targets);
        }

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

        return $this->store($subscription, $type, $request->title, $details);
    }

    /** Renewal: send the last plan again as it is. */
    public function repeat(Subscription $subscription, string $type)
    {
        $this->authorizePlan($subscription, $type);
        $last = $this->lastPlan($subscription, $type);
        abort_unless($last && !($type === 'workout' ? $subscription->workoutPlan : $subscription->nutritionPlan), 404);

        return $this->store($subscription, $type, $last->title, json_decode($last->plan_details, true) ?: [],
            $type === 'nutrition' ? $last->targets : null);
    }

    /** The plan of this type from the client's previous subscription with this coach. */
    private function lastPlan(Subscription $subscription, string $type)
    {
        $previous = $subscription->previousWithCoach();

        return $type === 'workout' ? $previous?->workoutPlan : $previous?->nutritionPlan;
    }

    private function store(Subscription $subscription, string $type, string $title, array $details, ?array $targets = null)
    {
        $model = $type === 'workout' ? WorkoutPlan::class : NutritionPlan::class;
        $isUpdate = $model::where('subscription_id', $subscription->id)->exists();
        $model::updateOrCreate(
            ['subscription_id' => $subscription->id],
            [
                'user_id'      => $subscription->trainee_id,
                'trainer_id'   => $subscription->coach_id,
                'title'        => $title,
                'plan_details' => json_encode($details, JSON_UNESCAPED_UNICODE),
            ] + ($type === 'nutrition' ? ['targets' => $targets] : [])
        );

        $message = match (true) {
            $type === 'workout' && $isUpdate  => ':coach updated your workout plan.',
            $type === 'workout'               => ':coach sent your workout plan.',
            $isUpdate                         => ':coach updated your nutrition plan.',
            default                           => ':coach sent your nutrition plan.',
        };
        Notify::user($subscription->trainee, $message, ['coach' => $subscription->coach->name],
            Notify::link($type === 'workout' ? 'trainee.schedule' : 'trainee.diet', ['subscription' => $subscription->id]),
            $type === 'workout' ? 'fa-dumbbell' : 'fa-apple-alt');

        return redirect()->route('coach.clients.show', $subscription)
            ->with('success', __('The plan was sent to your client.'));
    }

    private function authorizePlan(Subscription $subscription, string $type): void
    {
        abort_unless($subscription->coach_id === auth()->id() && $subscription->status === 'active', 404);
        abort_unless($type === 'workout' ? $subscription->includes_workout : $subscription->includes_nutrition, 404);
    }
}
