<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        $applicationUrl = (string) config('app.url', '');
        $isHttpsApplicationUrl = str_starts_with(strtolower($applicationUrl), 'https://');
        $forceHttpsFromEnvironment = filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOL);

        if ($isHttpsApplicationUrl || $forceHttpsFromEnvironment) {
            URL::forceScheme('https');
        }
    }
}
