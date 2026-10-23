<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// needs one cron line on the server: * * * * * php artisan schedule:run
Schedule::command('portal:invoice-reminders')->dailyAt('08:00')->withoutOverlapping();
