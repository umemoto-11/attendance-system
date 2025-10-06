<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('layouts.auth', function ($view) {
            $status = null;
            if (Auth::check()) {
                $today = Attendance::where('user_id', Auth::id())
                    ->whereDate('date', today())
                    ->first();

                $status = ($today && $today->clock_out) ? '退勤済' : null;
            }

            $view->with('status', $status);
        });
    }
}
