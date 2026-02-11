<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot(): void
    {
    
        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }

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