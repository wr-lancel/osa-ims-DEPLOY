<?php

namespace App\Providers;

use App\Models\Notification as AppNotification;
use App\Observers\NotificationObserver;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('Helpers/notification_helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        AppNotification::observe(NotificationObserver::class);
    }
}
