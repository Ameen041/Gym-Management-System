<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsTrainer
{
    public function handle($request, Closure $next)
    {
        if (!Auth::check() || Auth::user()->role !== 'trainer') {
            return redirect()->route('login.form')->with('error', 'غير مصرح لك بالوصول إلى هذه الصفحة.');
        }

        return $next($request);
    }
}