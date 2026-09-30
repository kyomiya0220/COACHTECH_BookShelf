<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
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
        // 全てのビューに対して $unreadNotificationCount を自動供給する
        View::composer('*', function ($view) {
            $count = Auth::check() ? Auth::user()->unreadNotifications->count() : 0;
            $view->with('unreadNotificationCount', $count);
        });
    }
}
