<?php

namespace App\Providers;

use App\Models\Video;
use App\Observers\VideoObserver;
use App\Policies\VideoPolicy;
use Illuminate\Support\Facades\Gate;
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
        // Register Video Observer for multi-camera cleanup
        Video::observe(VideoObserver::class);

        // Policies
        Gate::policy(Video::class, VideoPolicy::class);
    }
}
