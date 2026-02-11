<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ Fix Mixed Content on Render / proxies (force https)
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // ✅ reCAPTCHA custom validation rule
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