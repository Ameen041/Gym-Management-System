<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Exercise;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    /* ==================== AUTHENTICATION METHODS ==================== */
      
    
    public function showLoginForm()
    {
        $listed = CoachDirectoryController::listedCoaches();

        return view('home', [
            'coachesCount'     => $listed->count(),
            'traineesCount'    => User::where('role', 'trainee')->count(),
            'exercisesCount'   => Exercise::active()->count(),
            // featured first, then best rated
            'featuredTrainers' => $listed->sortByDesc(fn ($c) => [(int) $c->coachProfile->is_featured, $c->coachProfile->rating_avg])->take(3)->values(),
            'latestReviews'    => Review::with(['trainee:id,name', 'coach:id,name'])
                ->whereNotNull('comment')->where('rating', '>=', 4)->latest()->take(3)->get(),
        ]);
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
}
