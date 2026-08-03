<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Reverb\ApplicationManagerServiceProvider;
use Laravel\Reverb\ReverbServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(ApplicationManagerServiceProvider::class);
        $this->app->register(ReverbServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
