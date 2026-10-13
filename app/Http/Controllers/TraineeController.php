<?php

namespace App\Http\Controllers;

use App\Models\NutritionPlan;
use App\Models\Subscription;
use App\Models\WorkoutPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TraineeController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        $subscriptions = Subscription::with(['coach', 'workoutPlan', 'nutritionPlan', 'review'])
            ->where('trainee_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->latest()
            ->get();

        return view('trainee-dashboard', [
            'user'      => $user,
            'running'   => $subscriptions->filter->isRunning()->values(),
            'pending'   => $subscriptions->whereIn('status', ['awaiting_payment', 'payment_review', 'rejected'])->values(),
            'history'   => $subscriptions->filter(fn ($s) => $s->display_status === 'expired')->values(),
        ]);
    }

    /* ==================== PLANS ==================== */

    public function showWorkoutPlan(Request $request)
    {
        $plan = $this->latestPlan(WorkoutPlan::class, $request);
        if (!$plan) {
            return redirect()->route('trainee.dashboard')->with('error', __('Your coach has not sent a workout plan yet.'));
        }

        return view('workout-plan', compact('plan'));
    }

    public function showNutritionPlan(Request $request)
    {
        $plan = $this->latestPlan(NutritionPlan::class, $request);
        if (!$plan) {
            return redirect()->route('trainee.dashboard')->with('error', __('Your coach has not sent a nutrition plan yet.'));
        }

        return view('nutrition-plan', compact('plan'));
    }

    private function latestPlan(string $model, Request $request)
    {
        return $model::with('trainer')
            ->where('user_id', auth()->id())
            ->when($request->filled('subscription'), fn ($q) => $q->where('subscription_id', $request->query('subscription')))
            ->latest()
            ->first();
    }

    /* ==================== PROFILE ==================== */

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'phone'  => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\s-]{8,20}$/'],
            'age'    => ['nullable', 'numeric', 'min:1', 'max:120'],
            'height' => ['nullable', 'numeric', 'min:1', 'max:300'],
            'weight' => ['nullable', 'numeric', 'min:1', 'max:500'],
            'photo'  => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $user->profile_photo = $request->file('photo')->store('profiles', 'public');
        }

        $user->fill([
            'name'   => $data['name'],
            'phone'  => $data['phone'] ?? null,
            'age'    => $data['age'] ?? null,
            'height' => $data['height'] ?? null,
            'weight' => $data['weight'] ?? null,
        ])->save();

        return redirect()->route('trainee.dashboard')->with('success', __('Profile updated successfully.'));
    }
}
