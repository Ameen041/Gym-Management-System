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

        // يدعم: role:admin أو role:trainee أو role:trainer,admin
        if (!in_array($u->role, $roles, true)) {
            return redirect()->route('login.form')
                ->with('error', __('You are not allowed to access this page!'));
        }

        return $next($request);
    }
}