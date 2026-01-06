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
            return redirect()->route('login_form')
                ->with('error', 'يرجى تسجيل الدخول أولاً');
        }

        // يدعم: role:admin أو role:trainee أو role:trainer,admin
        if (!in_array($u->role, $roles, true)) {
            return redirect()->route('login_form')
                ->with('error', 'غير مسموح لك بالدخول إلى هذه الصفحة! 🙃');
        }

        return $next($request);
    }
}