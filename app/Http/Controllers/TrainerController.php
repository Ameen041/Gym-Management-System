<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NutritionPlan;
use App\Models\WorkoutPlan;
use App\Models\WorkoutRequest;
use App\Models\User;
use App\Models\NutritionRequest;
use App\Models\PlanTemplate;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;

class TrainerController extends Controller
{
    /* ==================== NUTRITION PLAN METHODS ==================== */
    
    public function addNutrition(Request $request)
    {
        $request->validate([
            'user_id'        => 'required|exists:users,id',
            'title'          => 'required|string|max:255',
            'day'            => 'required|array',
            'day.*'          => 'required|string',
            'meal_number'    => 'required|array',
            'meal_number.*'  => 'required',
            'description'    => 'required|array',
            'description.*'  => 'required|string',
            'calories'       => 'required|array',
            'calories.*'     => 'required',
            'protein'        => 'required|array',
            'protein.*'      => 'required',
            'carbs'          => 'required|array',
            'carbs.*'        => 'required',
            'fat'            => 'required|array',
            'fat.*'          => 'required',
            'request_id'     => 'nullable|integer',
        ]);
    
        $nutritionDetails = [];
        foreach ($request->day as $index => $day) {
            $nutritionDetails[$day][] = [
                'meal_number' => $request->meal_number[$index],
                'description' => $request->description[$index],
                'calories'    => $request->calories[$index],
                'protein'     => $request->protein[$index],
                'carbs'       => $request->carbs[$index],
                'fat'         => $request->fat[$index],
            ];
        }
    
        NutritionPlan::create([
            'user_id'      => $request->user_id,
            'trainer_id'   => auth()->id(),
            'title'        => $request->title,
            'plan_details' => json_encode($nutritionDetails, JSON_UNESCAPED_UNICODE),
        ]);
    
        if ($request->filled('request_id')) {
            NutritionRequest::where('id', $request->request_id)
                ->where('trainer_id', auth()->id())
                ->update(['status' => 'approved', 'updated_at' => now()]);
        } else {
            NutritionRequest::where('user_id', $request->user_id)
                ->where('trainer_id', auth()->id())
                ->where('status', 'pending')
                ->orderByDesc('id')
                ->limit(1)
                ->update(['status' => 'approved', 'updated_at' => now()]);
        }
    
        return redirect()->route('trainer.dashboard', [
            'user_id' => $request->user_id,
            'type' => 'nutrition',
            'request_id' => $request->request_id
        ])->with('success', 'Nutrition plan has been submitted successfully.');
    }

    /* ==================== DASHBOARD & REQUESTS METHODS ==================== */
    
    public function dashboard()
    {
        $pendingRequests = WorkoutRequest::where('trainer_id', auth()->id())
                        ->where('status', 'pending')
                        ->get();
    
        $pendingNutritionRequests = NutritionRequest::where('trainer_id', auth()->id())
                            ->where('status', 'pending')
                            ->get();
    
        $workoutTemplates = PlanTemplate::where('type', 'workout')
            ->where('is_active', true)
            ->orderBy('title')
            ->get();
    
        $nutritionTemplates = PlanTemplate::where('type', 'nutrition')
            ->where('is_active', true)
            ->orderBy('title')
            ->get();
    
        return view('trainer-dashboard', compact(
            'pendingRequests',
            'pendingNutritionRequests',
            'workoutTemplates',
            'nutritionTemplates'
        ));
    }

    public function showRequests()
    {
        $workoutRequests = WorkoutRequest::with(['user:id,name,email,age,weight,height,profile_photo'])
                        ->where('trainer_id', auth()->id())
                        ->where('status', 'pending')
                        ->get();
    
        $nutritionRequests = NutritionRequest::with(['user:id,name,email,age,weight,height,profile_photo'])
                            ->where('trainer_id', auth()->id())
                            ->where('status', 'pending')
                            ->get();
    
        return view('trainer-requests', compact('workoutRequests', 'nutritionRequests'));
    }

    /* ==================== WORKOUT PLAN METHODS ==================== */
    
    public function showWorkoutForm(Request $request)
{
    $request->validate([
        'goal' => 'required|string|max:255',
        'notes' => 'nullable|string',
        'trainer_id' => 'required|exists:users,id',
    ]);

    WorkoutRequest::create([
        'user_id' => auth()->id(),
        'trainer_id' => $request->trainer_id,
        'goal' => $request->goal,
        'notes' => $request->notes,
        'status' => 'pending',
    ]);

    return redirect()->route('trainee.dashboard')->with('success', 'Request has been sent to the trainer successfully');
}

public function addWorkout(Request $request)
{
    $request->validate([
        'user_id'     => 'required|exists:users,id',
        'title'       => 'required|string|max:255',
        'day'         => 'required|array',
        'day.*'       => 'required|string',
        'muscle'      => 'required|array',
        'muscle.*'    => 'required|string',
        'exercise'    => 'required|array',
        'exercise.*'  => 'required|string',
        'sets'        => 'required|array',
        'sets.*'      => 'required',
        'reps'        => 'required|array',
        'reps.*'      => 'required',
        'request_id'  => 'nullable|integer',
    ]);

    $structured = [];
    foreach ($request->day as $i => $day) {
        $structured[$day][] = [
            'muscle'   => $request->muscle[$i],
            'exercise' => $request->exercise[$i],
            'sets'     => $request->sets[$i],
            'reps'     => $request->reps[$i],
        ];
    }

    WorkoutPlan::create([
        'user_id'      => $request->user_id,
        'trainer_id'   => auth()->id(),
        'title'        => $request->title,
        'plan_details' => json_encode($structured, JSON_UNESCAPED_UNICODE),
    ]);

    if ($request->filled('request_id')) {
        WorkoutRequest::where('id', $request->request_id)
            ->where('trainer_id', auth()->id())
            ->update(['status' => 'approved', 'updated_at' => now()]);
    } else {
        WorkoutRequest::where('user_id', $request->user_id)
            ->where('trainer_id', auth()->id())
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->limit(1)
            ->update(['status' => 'approved', 'updated_at' => now()]);
    }

    return redirect()->route('trainer.dashboard', [
        'user_id' => $request->user_id,
        'type' => 'workout',
        'request_id' => $request->request_id
    ])->with('success', 'Workout plan has been submitted successfully.');
}

    /* ==================== REQUEST MANAGEMENT METHODS ==================== */
    
    public function rejectWorkout($id)
    {
        $request = WorkoutRequest::findOrFail($id);
        $request->status = 'rejected';
        $request->save();

        return back()->with('success', 'Request has been rejected successfully.');
    }

    public function rejectNutrition($id)
    {
        $request = NutritionRequest::findOrFail($id);
        $request->status = 'rejected';
        $request->save();

        return back()->with('success', 'Request has been rejected successfully.');
    }

    public function showNutritionRequests()
    {
        $requests = NutritionRequest::with('user')
                    ->where('status', 'pending')
                    ->where('trainer_id', auth()->id())
                    ->get();

        return view('nutrition-requests', compact('requests'));
    }

    public function requestNutrition(Request $request)
{
    $request->validate([
        'goal' => 'required|string|max:255',
        'notes' => 'nullable|string',
        'trainer_id' => 'required|exists:users,id',
    ]);

    NutritionRequest::create([
        'user_id' => auth()->id(),
        'trainer_id' => $request->trainer_id,
        'goal' => $request->goal,
        'notes' => $request->notes,
        'status' => 'pending',
    ]);

    return redirect()->route('trainee.dashboard')->with('success', 'Request has been sent to the trainer successfully');
}

public function updateProfile(Request $request)
{
    $trainer = auth()->user();

    $data = $request->validate([
        'name'   => ['required','string','max:255'],
        'age'    => ['nullable','numeric','min:1','max:120'],
        'height' => ['nullable','numeric','min:1','max:300'],
        'weight' => ['nullable','numeric','min:1','max:500'],
        'photo'  => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
    ]);

    if ($request->hasFile('photo')) {
        if ($trainer->profile_photo) {
            Storage::disk('public')->delete($trainer->profile_photo);
        }
        $path = $request->file('photo')->store('profiles', 'public');
        $trainer->profile_photo = $path;
    }

    $trainer->name   = $data['name'];
    $trainer->age    = $data['age'] ?? null;
    $trainer->height = $data['height'] ?? null;
    $trainer->weight = $data['weight'] ?? null;
    $trainer->save();

    return redirect()->route('trainer.dashboard')->with('success', 'Profile updated successfully.');
}

}