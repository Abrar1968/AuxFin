<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('finerp:snapshot')->monthlyOn(1, '00:05');
Schedule::command('finerp:security:audit')->weeklyOn(1, '03:00');
Schedule::command('finerp:expenses:process-recurring')->dailyAt('01:00');
Schedule::command('finerp:liabilities:notify-due-soon --days=3')->dailyAt('01:10');
Schedule::command('finerp:liabilities:amortize')->dailyAt('01:20');
Schedule::command('finerp:assets:depreciate')->monthlyOn(1, '01:30');
Schedule::command('finerp:invoices:flag-overdue')->dailyAt('02:00');
