<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
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
    public  function boot()
{
    Schema::defaultStringLength(191); // ✅ This fixes the key length issue
    if (env('APP_ENV') === 'production') {
        URL::forceScheme('https');
    };
}
}
