<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Role
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $u = Auth::user();
        if (!$u) {
            return redirect()->route('login.form')
                ->with('error', __('Please log in first.'));
        }

        // Supports: role:admin, role:trainee or role:trainer,admin
        if (!in_array($u->role, $roles, true)) {
            // Logged in with another account type: send them to their own dashboard
            return redirect()->route(self::homeRoute($u->role))
                ->with('error', __('You are not allowed to access this page!'));
        }

        return $next($request);
    }

    public static function homeRoute(?string $role): string
    {
        return match ($role) {
            'admin'   => 'admin.dashboard',
            'trainer' => 'trainer.dashboard',
            default   => 'trainee.dashboard',
        };
    }
}
