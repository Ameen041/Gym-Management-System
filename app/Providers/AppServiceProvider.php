<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ✅ Force HTTPS on production (Render behind proxy)
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // Recaptcha validator
        Validator::extend('recaptcha', function ($attribute, $value, $parameters, $validator) {

            $secret = config('services.recaptcha.secret_key');
            if (!$secret) return false;

            $res = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret'   => $secret,
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);

            return (bool) data_get($res->json(), 'success', false);
        });
    }
}