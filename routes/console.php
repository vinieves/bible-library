<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Dispara notificações push agendadas/recorrentes (requer `php artisan schedule:run` via cron).
Schedule::command('push:dispatch-scheduled')
    ->everyMinute()
    ->withoutOverlapping();
