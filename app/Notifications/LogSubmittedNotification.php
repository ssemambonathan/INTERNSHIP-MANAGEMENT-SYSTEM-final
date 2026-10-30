<?php

namespace App\Notifications;

use App\Models\DailyLog;

class LogSubmittedNotification extends BaseNotification
{
    public function __construct(public DailyLog $log) {}

    public function title(): string
    {
        return 'New daily log submitted';
    }

    public function message(): string
    {
        return sprintf(
            '%s submitted the daily log for %s. Review it when you have a moment.',
            $this->log->student->user->name,
            $this->log->date->format('d M Y'),
        );
    }

    public function url(): string
    {
        return route('supervisor.approvals.show', $this->log);
    }

    public function icon(): string
    {
        return 'bi-journal-text';
    }
}
