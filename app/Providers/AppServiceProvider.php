<?php

namespace App\Providers;

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
        \Illuminate\Pagination\Paginator::defaultView('pagination::custom');
        \Illuminate\Pagination\Paginator::defaultSimpleView('pagination::custom');

        // Register the SensorReadingObserver
        \App\Models\SensorReading::observe(\App\Observers\SensorReadingObserver::class);
    }
}
