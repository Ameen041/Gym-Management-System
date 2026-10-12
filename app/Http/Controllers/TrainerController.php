<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NutritionPlan;
use App\Models\WorkoutPlan;
use App\Models\WorkoutRequest;
use App\Models\NutritionRequest;
use App\Models\PlanTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TrainerController extends Controller
{
    /* ==================== NUTRITION PLAN METHODS ==================== */

    public function addNutrition(Request $request)
    {
        $request->validate([
            'request_id'     => 'required|integer',
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
        ]);

        $nutritionRequest = $this->pendingRequest(NutritionRequest::class, $request->request_id);
        if (!$nutritionRequest) {
            return redirect()->route('trainer.requests')->with('error', __('This request is no longer available.'));
        }

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

        DB::transaction(function () use ($nutritionRequest, $request, $nutritionDetails) {
            // The new plan replaces the trainee's previous nutrition plan
            NutritionPlan::where('user_id', $nutritionRequest->user_id)->delete();

            NutritionPlan::create([
                'user_id'      => $nutritionRequest->user_id,
                'trainer_id'   => auth()->id(),
                'title'        => $request->title,
                'plan_details' => json_encode($nutritionDetails, JSON_UNESCAPED_UNICODE),
            ]);

            $nutritionRequest->update(['status' => 'approved']);
        });

        return redirect()->route('trainer.requests')
            ->with('success', __('Nutrition plan has been submitted successfully.'));
    }

    /* ==================== DASHBOARD & REQUESTS METHODS ==================== */

    public function dashboard(Request $request)
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

        // Plan form: only for a pending request sent to this trainer
        $selectedType  = null;
        $activeRequest = null;
        $type = $request->query('type');

        if (in_array($type, ['workout', 'nutrition'], true) && $request->filled('request_id')) {
            $model = $type === 'workout' ? WorkoutRequest::class : NutritionRequest::class;
            $activeRequest = $this->pendingRequest($model, $request->query('request_id'));

            if (!$activeRequest) {
                return redirect()->route('trainer.requests')->with('error', __('This request is no longer available.'));
            }

            $selectedType = $type;
        }

        $trainee = $activeRequest?->user;

        return view('trainer-dashboard', compact(
            'pendingRequests',
            'pendingNutritionRequests',
            'workoutTemplates',
            'nutritionTemplates',
            'selectedType',
            'activeRequest',
            'trainee'
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

    public function addWorkout(Request $request)
    {
        $request->validate([
            'request_id'  => 'required|integer',
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
        ]);

        $workoutRequest = $this->pendingRequest(WorkoutRequest::class, $request->request_id);
        if (!$workoutRequest) {
            return redirect()->route('trainer.requests')->with('error', __('This request is no longer available.'));
        }

        $structured = [];
        foreach ($request->day as $i => $day) {
            $structured[$day][] = [
                'muscle'   => $request->muscle[$i],
                'exercise' => $request->exercise[$i],
                'sets'     => $request->sets[$i],
                'reps'     => $request->reps[$i],
            ];
        }

        DB::transaction(function () use ($workoutRequest, $request, $structured) {
            // The new plan replaces the trainee's previous workout plan
            WorkoutPlan::where('user_id', $workoutRequest->user_id)->delete();

            WorkoutPlan::create([
                'user_id'      => $workoutRequest->user_id,
                'trainer_id'   => auth()->id(),
                'title'        => $request->title,
                'plan_details' => json_encode($structured, JSON_UNESCAPED_UNICODE),
            ]);

            $workoutRequest->update(['status' => 'approved']);
        });

        return redirect()->route('trainer.requests')
            ->with('success', __('Workout plan has been submitted successfully.'));
    }

    /* ==================== REQUEST MANAGEMENT METHODS ==================== */

    public function rejectWorkout($id)
    {
        $request = WorkoutRequest::where('trainer_id', auth()->id())->findOrFail($id);
        $request->status = 'rejected';
        $request->save();

        return back()->with('success', __('Request has been rejected successfully.'));
    }

    public function rejectNutrition($id)
    {
        $request = NutritionRequest::where('trainer_id', auth()->id())->findOrFail($id);
        $request->status = 'rejected';
        $request->save();

        return back()->with('success', __('Request has been rejected successfully.'));
    }

    public function updateProfile(Request $request)
    {
        $trainer = auth()->user();

        $data = $request->validate([
            'name'   => ['required','string','max:255'],
            'age'    => ['nullable','numeric','min:1','max:120'],
            'photo'  => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
        ]);

        if ($request->hasFile('photo')) {
            if ($trainer->profile_photo) {
                Storage::disk('public')->delete($trainer->profile_photo);
            }
            $path = $request->file('photo')->store('profiles', 'public');
            $trainer->profile_photo = $path;
        }

        $trainer->name = $data['name'];
        $trainer->age  = $data['age'] ?? null;
        $trainer->save();

        return redirect()->route('trainer.dashboard')->with('success', __('Profile updated successfully.'));
    }

    /**
     * A pending request of the given type that was sent to the logged-in trainer.
     */
    private function pendingRequest(string $model, $id)
    {
        return $model::with('user')
            ->where('id', $id)
            ->where('trainer_id', auth()->id())
            ->where('status', 'pending')
            ->first();
    }
}
