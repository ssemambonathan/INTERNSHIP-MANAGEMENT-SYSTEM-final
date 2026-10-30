<?php

namespace App\Notifications;

class PendingApprovalsReminderNotification extends BaseNotification
{
    public function __construct(public int $pendingCount) {}

    public function title(): string
    {
        return 'Daily logs awaiting your review';
    }

    public function message(): string
    {
        return sprintf('You have %d daily log(s) awaiting review from your assigned students.', $this->pendingCount);
    }

    public function url(): string
    {
        return route('supervisor.approvals.index');
    }

    public function icon(): string
    {
        return 'bi-hourglass-split';
    }
}
