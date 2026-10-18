<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public const LOCALES = ['en', 'ar'];

    public function handle(Request $request, Closure $next)
    {
        $locale = $request->session()->get('locale', $request->cookie('locale'));

        if (in_array($locale, self::LOCALES, true)) {
            App::setLocale($locale);
            Carbon::setLocale($locale);

            // remembered on the account, for notifications sent to the phone
            $user = $request->user();
            if ($user && $user->locale !== $locale) {
                $user->forceFill(['locale' => $locale])->saveQuietly();
            }
        }

        return $next($request);
    }
}
