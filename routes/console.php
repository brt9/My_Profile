<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('telemetry:maintain')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('calendar:sync')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::command('duolingo:sync')
    ->everySixHours()
    ->withoutOverlapping();

Schedule::command('weather:capture-natal')
    ->everyFifteenMinutes()
    ->withoutOverlapping();
