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
    
        // Real trainers for the home page (newest first)
        $featuredTrainers = User::where('role', 'trainer')
            ->where('is_active', true)
            ->latest()
            ->take(3)
            ->get();

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
            'latestNutrition',
            'featuredTrainers'
        ));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
    
        if (Auth::attempt($credentials)) {
    
            $request->session()->regenerate();
            $user = Auth::user();
    
            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
    
                return redirect()->route('login.form')
                    ->with('error', __('Your account is suspended. Please contact the administrator.'));
            }
    
            return match ($user->role) {
                'admin'   => redirect()->route('admin.dashboard'),
                'trainer' => redirect()->route('trainer.dashboard'),
                default   => redirect()->route('trainee.dashboard'),
            };
        }
    
        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __('Invalid credentials. Please try again.')]);
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
        // Separate error bag so the sign-up tab re-opens with its errors
        $request->validateWithBag('register', [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'role' => 'required|in:trainer,trainee',
            'age' => 'nullable|integer|min:10|max:100',
            'height' => 'nullable|numeric|min:50|max:300',
            'weight' => 'nullable|numeric|min:20|max:300',
        ]);

        $isTrainee = $request->role === 'trainee';

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'age' => $request->age,
            // Body measurements are only relevant for trainees
            'height' => $isTrainee ? $request->height : null,
            'weight' => $isTrainee ? $request->weight : null,
        ]);

        Auth::login($user);

        return redirect()->route($isTrainee ? 'trainee.dashboard' : 'trainer.dashboard');
    }

    /* ==================== DASHBOARD METHODS ==================== */
    
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
}
