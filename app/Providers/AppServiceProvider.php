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
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $st = \App\Models\Setting::all_();
                config(['board.brand' => $st['brand'], 'board.tagline' => $st['tagline']]);
            }
        } catch (\Throwable $e) {
            // database not ready yet (e.g. during first migrate)
        }
        //
    }
}
