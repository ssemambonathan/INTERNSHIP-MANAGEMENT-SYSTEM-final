<?php

namespace App\Notifications;

use App\Models\DateReopening;

class LogReopenedNotification extends BaseNotification
{
    public function __construct(public DateReopening $reopening) {}

    public function title(): string
    {
        return 'A missed log date was reopened';
    }

    public function message(): string
    {
        return sprintf(
            '%s reopened %s for log submission. Reason: %s',
            $this->reopening->reopenedBy->name,
            $this->reopening->date->format('d M Y'),
            $this->reopening->reason,
        );
    }

    public function url(): string
    {
        return route('student.logs.index');
    }

    public function icon(): string
    {
        return 'bi-unlock';
    }
}
