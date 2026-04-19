<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('auxfin:snapshot')->monthlyOn(1, '00:05');
Schedule::command('auxfin:security:audit')->weeklyOn(1, '03:00');
Schedule::command('auxfin:expenses:process-recurring')->dailyAt('01:00');
Schedule::command('auxfin:liabilities:notify-due-soon --days=3')->dailyAt('01:10');
Schedule::command('auxfin:liabilities:amortize')->dailyAt('01:20');
Schedule::command('auxfin:assets:depreciate')->monthlyOn(1, '01:30');
Schedule::command('auxfin:invoices:flag-overdue')->dailyAt('02:00');
