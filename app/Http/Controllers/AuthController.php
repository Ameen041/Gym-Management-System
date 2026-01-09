<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\WorkoutPlan;
use App\Models\NutritionPlan;
use App\Models\Payment;
use App\Models\PlanTemplate;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    /* ==================== AUTHENTICATION METHODS ==================== */
      
    
    public function showLoginForm()
    {
        $trainersCount = User::where('role', 'trainer')->count();
        $traineesCount = User::where('role', 'trainee')->count();
    
        $latestWorkout = PlanTemplate::where('type', 'workout')
            ->where('is_active', 1)
            ->latest()
            ->first(); 
    
        $latestNutrition = PlanTemplate::where('type', 'nutrition')
            ->where('is_active', 1)
            ->latest()
            ->first();
    
        return view('home', compact(
            'trainersCount',
            'traineesCount',
            'latestWorkout',
            'latestNutrition'
        ));
    }

    public function login(Request $request)
{
    $request->validate([
        'email'    => ['required','email'],
        'password' => ['required','string'],
    ]);

    if (Auth::attempt($request->only('email','password'), $request->boolean('remember'))) {
        $request->session()->regenerate();

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->with('error', 'Your account is suspended. Please contact the administrator.');
        }

        if ($user->role === 'admin')   return redirect('/admin/dashboard');
        if ($user->role === 'trainer') return redirect('/trainer-dashboard');
        return redirect('/trainee-dashboard');
    }

    return back()->withErrors(['email' => 'Invalid credentials. Please try again.']);
}

public function logout(Request $request)
{
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
}

    /* ==================== REGISTRATION METHODS ==================== */
    
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'role' => 'required|in:trainer,trainee',
            'age' => 'nullable|integer|min:10|max:100',
            'height' => 'nullable|numeric|min:50|max:300',
            'weight' => 'nullable|numeric|min:20|max:300',
        ]);
    
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'age' => $request->age,
            'height' => $request->height,
            'weight' => $request->weight,
        ]);
    
        Auth::login($user);
    
        return redirect($user->role === 'trainer' ? route('trainer.dashboard') : '/trainee-dashboard');
    }

    /* ==================== DASHBOARD METHODS ==================== */
    
    public function trainerDashboard()
    {
        if (auth()->check() && auth()->user()->role === 'trainer') {
            return view('trainer-dashboard');
        }

        return redirect()->route('login.form')->with('show_signup', true);
    }

    public function traineeDashboard()
    {
        $user = Auth::user();
    
        if (!$user || $user->role !== 'trainee') {
            return redirect()
                ->route('login.form')
                ->with('unauthorized', 'Unauthorized access.');
        }
    
        $workoutPlan   = WorkoutPlan::where('user_id', $user->id)->first();
        $nutritionPlan = NutritionPlan::where('user_id', $user->id)->first();
    
        $payments = Payment::where('user_id', $user->id)
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get();
    
        $activePayment = Payment::where('user_id', $user->id)
            ->whereDate('period_start', '<=', now())
            ->whereDate('period_end', '>=', now())
            ->orderByDesc('period_end')
            ->first();
    
        return view('trainee-dashboard', [
            'user'           => $user,
            'workoutPlan'    => $workoutPlan,
            'nutritionPlan'  => $nutritionPlan,
            'payments'       => $payments,
            'activePayment'  => $activePayment,
            'hasSubscription'=> (bool) $activePayment, 
        ]);
    }

    /* ==================== PLAN DISPLAY METHODS ==================== */
    
    public function showWorkoutPlan()
    {
        $user = Auth::user();
        $plan = WorkoutPlan::where('user_id', $user->id)->first();

        if (!$plan) {
            return redirect()->route('trainee.dashboard')->with('error', 'No workout plan available.');
        }

        return view('workout-plan', compact('plan'));
    }

    public function showNutritionPlan(Request $request)
    {
        $trainerId = $request->query('trainer_id'); 
        return view('nutrition-plan', compact('trainerId'));
    }
}