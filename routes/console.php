<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
| Runs every weekday at 6:00 PM. Requires the scheduler to be running:
|     php artisan schedule:work
|
| For an academic demo you can just run the command manually instead:
|     php artisan attendance:check-warnings
*/

Schedule::command('attendance:check-warnings')
    ->weekdays()
    ->dailyAt('18:00');
