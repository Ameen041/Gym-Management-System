<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Exercise;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

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

        // Slow down password guessing: 5 tries per account and 30 per network per minute
        $accountKey = 'login:' . Str::lower($request->email) . '|' . $request->ip();
        $networkKey = 'login-ip:' . $request->ip();
        foreach ([[$accountKey, 5], [$networkKey, 30]] as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return back()->withInput($request->only('email'))->withErrors(['email' => __(
                    'Too many login attempts. Please try again in :seconds seconds.',
                    ['seconds' => RateLimiter::availableIn($key)]
                )]);
            }
        }

        if (Auth::attempt($credentials)) {
            RateLimiter::clear($accountKey);
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
    
        RateLimiter::hit($accountKey, 60);
        RateLimiter::hit($networkKey, 60);

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
        // Stop scripted sign-ups: 20 new accounts per network per hour
        $signupKey = 'register:' . $request->ip();
        if (RateLimiter::tooManyAttempts($signupKey, 20)) {
            return back()->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['email' => __('Too many new accounts from this network. Please try again later.')], 'register');
        }

        // Separate error bag so the sign-up tab re-opens with its errors
        $request->validateWithBag('register', [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'role' => 'required|in:trainer,trainee',
            'age' => 'nullable|integer|min:10|max:100',
            'height' => 'nullable|numeric|min:50|max:300',
            'weight' => 'nullable|numeric|min:20|max:300',
            'terms' => 'accepted',
        ], [
            'terms.accepted' => __('Please accept the terms and the privacy policy.'),
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
        $user->forceFill(['terms_accepted_at' => now()])->save();

        RateLimiter::hit($signupKey, 3600);
        Auth::login($user);

        return redirect()->route($isTrainee ? 'trainee.dashboard' : 'trainer.dashboard');
    }
}
