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

class TraineeController extends Controller
{
    /* ==================== WORKOUT PLAN METHODS ==================== */
    
    public function showWorkoutPlan(Request $request)
    {
        $user = Auth::user();
        $trainerId = $request->query('trainer_id');
    
        $workoutPlan = WorkoutPlan::where('user_id', $user->id)->first();
    
        if ($workoutPlan && $trainerId) {
            return redirect()->route('trainee.schedule')->with('error', 'You already have a workout plan. Please delete it before requesting a new one.');
        }
    
        if ($workoutPlan) {
            return view('workout-plan', [
                'workoutPlan' => $workoutPlan,
                'plan' => $workoutPlan,
            ]);
        }
    
        if ($trainerId) {
            return view('workout-plan', [
                'trainerId' => $trainerId,
            ]);
        }
    
        return view('workout-plan');
    }

    /* ==================== NUTRITION PLAN METHODS ==================== */
    
    public function showNutritionPlan(Request $request)
    {
        $plan = NutritionPlan::where('user_id', auth()->id())->first();
    
        if ($plan) {
            return view('nutrition-plan', compact('plan'));
        }
    
        $trainerId = $request->query('trainer_id');
    
        if (!$trainerId || !User::where('id', $trainerId)->exists()) {
            return view('nutrition-plan');
        }
    
        return view('nutrition-plan', [
            'trainerId' => $trainerId
        ]);
    }

    /* ==================== REQUEST METHODS ==================== */
    
    public function requestWorkout(Request $request)
{
    $user = auth()->user();

    // 🔍 هل عنده اشتراك فعّال اليوم؟
    $hasActivePayment = Payment::where('user_id', $user->id)
        ->whereDate('period_start', '<=', now()->toDateString())
        ->whereDate('period_end', '>=', now()->toDateString())
        ->exists();

    if (!$hasActivePayment) {
        return redirect()
            ->route('trainee.dashboard')
            ->with('error', 'You must have an active subscription to request a workout plan.');
    }

    // ✅ Validation + صور متعددة
    $data = $request->validate([
        'goal'          => 'required|string|max:255',
        'notes'         => 'nullable|string',
        'trainer_id'    => 'required|exists:users,id',
        'body_photos'   => 'nullable|array',
        'body_photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
    ]);

    // ✅ رفع الصور وتخزين المسارات
    $photoPaths = [];
    if ($request->hasFile('body_photos')) {
        foreach ($request->file('body_photos') as $file) {
            $photoPaths[] = $file->store('body_photos', 'public');
        }
    }

    WorkoutRequest::create([
        'user_id'     => $user->id,
        'trainer_id'  => $data['trainer_id'],
        'goal'        => $data['goal'],
        'notes'       => $data['notes'] ?? null,
        'body_photos' => $photoPaths ? json_encode($photoPaths) : null,
        'status'      => 'pending',
    ]);

    return redirect()
        ->route('trainee.dashboard')
        ->with('success', 'Request has been sent to the trainer successfully');
}

public function requestNutrition(Request $request)
{
    $user = auth()->user();

    // 🔍 هل عنده اشتراك فعّال اليوم؟
    $hasActivePayment = Payment::where('user_id', $user->id)
        ->whereDate('period_start', '<=', now()->toDateString())
        ->whereDate('period_end', '>=', now()->toDateString())
        ->exists();

    if (!$hasActivePayment) {
        return redirect()
            ->route('trainee.dashboard')
            ->with('error', 'You must have an active subscription to request a nutrition plan.');
    }

    // ✅ Validation + صور متعددة
    $data = $request->validate([
        'goal'          => 'required|string|max:255',
        'notes'         => 'nullable|string',
        'trainer_id'    => 'required|exists:users,id',
        'body_photos'   => 'nullable|array',
        'body_photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
    ]);

    // ✅ رفع الصور وتخزين المسارات
    $photoPaths = [];
    if ($request->hasFile('body_photos')) {
        foreach ($request->file('body_photos') as $file) {
            $photoPaths[] = $file->store('body_photos', 'public');
        }
    }

    NutritionRequest::create([
        'user_id'     => $user->id,
        'trainer_id'  => $data['trainer_id'],
        'goal'        => $data['goal'],
        'notes'       => $data['notes'] ?? null,
        'body_photos' => $photoPaths ? json_encode($photoPaths) : null,
        'status'      => 'pending',
    ]);

    return redirect()
        ->route('trainee.dashboard')
        ->with('success', 'Request has been sent to the trainer successfully');
}

    /* ==================== TRAINER DISPLAY METHODS ==================== */
    
    public function showTrainers(Request $request)
    {
        $type = $request->query('type');
        $trainers = User::where('role', 'trainer')->get();

        return view('trainers', compact('trainers', 'type'));
    }



public function editProfile()
{
    return view('trainee.profile-edit');
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
        // حذف القديمة (اختياري)
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

    return redirect()->route('trainee.dashboard')->with('success', 'Profile updated successfully.');
}
}