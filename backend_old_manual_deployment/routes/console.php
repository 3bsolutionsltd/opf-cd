<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule alert evaluations to run daily at midnight
Schedule::command('alerts:evaluate')->daily();

// Schedule business metrics calculation to run daily at 1 AM
Schedule::command('metrics:calculate')->dailyAt('01:00');
