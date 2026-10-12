<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WorkoutRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\NutritionRequest;
use App\Models\WorkoutPlan;
use App\Models\NutritionPlan;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TraineeController extends Controller
{
    /* ==================== WORKOUT PLAN METHODS ==================== */

    public function showWorkoutPlan(Request $request)
    {
        $plan = WorkoutPlan::where('user_id', auth()->id())->first();
        $trainer = $this->trainerFromQuery($request);

        if ($plan && $trainer) {
            return redirect()->route('trainee.schedule')
                ->with('error', __('You already have a workout plan. Use "Request a new plan" first.'));
        }

        if ($plan) {
            return view('workout-plan', compact('plan'));
        }

        if ($trainer) {
            return view('workout-plan', ['trainerId' => $trainer->id, 'trainer' => $trainer]);
        }

        return view('workout-plan');
    }

    /* ==================== NUTRITION PLAN METHODS ==================== */

    public function showNutritionPlan(Request $request)
    {
        $plan = NutritionPlan::where('user_id', auth()->id())->first();
        $trainer = $this->trainerFromQuery($request);

        if ($plan && $trainer) {
            return redirect()->route('trainee.diet')
                ->with('error', __('You already have a nutrition plan. Use "Request a new plan" first.'));
        }

        if ($plan) {
            return view('nutrition-plan', compact('plan'));
        }

        if ($trainer) {
            return view('nutrition-plan', ['trainerId' => $trainer->id, 'trainer' => $trainer]);
        }

        return view('nutrition-plan');
    }

    /* ==================== REQUEST METHODS ==================== */

    public function requestWorkout(Request $request)
    {
        return $this->storeRequest($request, WorkoutRequest::class,
            __('You must have an active subscription to request a workout plan.'));
    }

    public function requestNutrition(Request $request)
    {
        return $this->storeRequest($request, NutritionRequest::class,
            __('You must have an active subscription to request a nutrition plan.'));
    }

    private function storeRequest(Request $request, string $model, string $noSubscriptionMessage)
    {
        $user = auth()->user();

        $hasActivePayment = Payment::where('user_id', $user->id)
            ->whereDate('period_start', '<=', now()->toDateString())
            ->whereDate('period_end', '>=', now()->toDateString())
            ->exists();

        if (!$hasActivePayment) {
            return redirect()->route('trainee.dashboard')->with('error', $noSubscriptionMessage);
        }

        $data = $request->validate([
            'goal'          => 'required|string|max:255',
            'notes'         => 'nullable|string',
            'trainer_id'    => ['required', $this->activeTrainerRule()],
            'body_photos'   => 'nullable|array',
            'body_photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $alreadyPending = $model::where('user_id', $user->id)
            ->where('trainer_id', $data['trainer_id'])
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            return redirect()->route('trainee.dashboard')
                ->with('error', __('You already have a pending request with this trainer.'));
        }

        $photoPaths = [];
        if ($request->hasFile('body_photos')) {
            foreach ($request->file('body_photos') as $file) {
                $photoPaths[] = $file->store('body_photos', 'public');
            }
        }

        $model::create([
            'user_id'     => $user->id,
            'trainer_id'  => $data['trainer_id'],
            'goal'        => $data['goal'],
            'notes'       => $data['notes'] ?? null,
            'body_photos' => $photoPaths ? json_encode($photoPaths) : null,
            'status'      => 'pending',
        ]);

        return redirect()->route('trainee.dashboard')
            ->with('success', __('Request has been sent to the trainer successfully'));
    }

    private function trainerFromQuery(Request $request): ?User
    {
        $trainerId = $request->query('trainer_id');
        if (!$trainerId) {
            return null;
        }

        return User::where('id', $trainerId)->where('role', 'trainer')->where('is_active', true)->first();
    }

    /* ==================== TRAINER DISPLAY METHODS ==================== */
    
    public function showTrainers(Request $request)
    {
        $type = $request->query('type');
        $trainers = User::where('role', 'trainer')->where('is_active', true)->orderBy('name')->get();

        return view('trainers', compact('trainers', 'type'));
    }

    /* ==================== DELETE PLAN (to request a new one) ==================== */

    public function deleteWorkoutPlan()
    {
        WorkoutPlan::where('user_id', auth()->id())->delete();

        return redirect()->route('trainers', ['type' => 'workout'])
            ->with('success', __('Your plan was deleted. Choose a trainer to request a new one.'));
    }

    public function deleteNutritionPlan()
    {
        NutritionPlan::where('user_id', auth()->id())->delete();

        return redirect()->route('trainers', ['type' => 'nutrition'])
            ->with('success', __('Your plan was deleted. Choose a trainer to request a new one.'));
    }

    private function activeTrainerRule()
    {
        return Rule::exists('users', 'id')->where('role', 'trainer')->where('is_active', true);
    }

public function updateProfile(Request $request)
{
    $user = auth()->user();

    $data = $request->validate([
        'name'   => ['required','string','max:255'],
        'age'    => ['nullable','numeric','min:1','max:120'],
        'height' => ['nullable','numeric','min:1','max:300'],
        'weight' => ['nullable','numeric','min:1','max:500'],
        'photo'  => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
    ]);

    if ($request->hasFile('photo')) {
    
        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }
        $path = $request->file('photo')->store('profiles', 'public');
        $user->profile_photo = $path;
    }

    $user->name   = $data['name'];
    $user->age    = $data['age'] ?? null;
    $user->height = $data['height'] ?? null;
    $user->weight = $data['weight'] ?? null;
    $user->save();

    return redirect()->route('trainee.dashboard')->with('success', __('Profile updated successfully.'));
}
}