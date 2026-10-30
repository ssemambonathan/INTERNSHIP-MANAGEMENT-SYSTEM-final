<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('interntrack:finalize-checkouts')
    ->dailyAt('00:15')
    ->withoutOverlapping()
    ->description('Mark previous days without a check-out as incomplete');

Schedule::command('interntrack:remind-attendance')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->description('Remind active students who have not checked in today');

Schedule::command('interntrack:remind-logs')
    ->dailyAt('17:15')
    ->withoutOverlapping()
    ->description('Remind students who attended but have not submitted a daily log');

Schedule::command('interntrack:remind-approvals')
    ->dailyAt('07:30')
    ->withoutOverlapping()
    ->description('Send supervisors a digest of logs awaiting review');

Schedule::command('interntrack:risk-alerts')
    ->weeklyOn(1, '07:00')
    ->withoutOverlapping()
    ->description('Alert lecturers to active placements with risk indicators');
