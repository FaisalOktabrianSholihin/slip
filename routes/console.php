<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Retensi data slip otomatis tiap hari (butuh scheduler: `php artisan schedule:work`
// atau cron `* * * * * php artisan schedule:run`).
\Illuminate\Support\Facades\Schedule::command('slip:retensi')->dailyAt('01:00');
