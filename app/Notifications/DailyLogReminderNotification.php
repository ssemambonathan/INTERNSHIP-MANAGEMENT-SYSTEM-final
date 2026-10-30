<?php

namespace App\Notifications;

class DailyLogReminderNotification extends BaseNotification
{
    public function title(): string
    {
        return 'Daily log reminder';
    }

    public function message(): string
    {
        return 'You attended your internship today but have not submitted a daily log yet.';
    }

    public function url(): string
    {
        return route('student.logs.index');
    }

    public function icon(): string
    {
        return 'bi-journal-plus';
    }
}
