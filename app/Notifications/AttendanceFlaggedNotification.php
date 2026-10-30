<?php

namespace App\Notifications;

use App\Models\Attendance;

class AttendanceFlaggedNotification extends BaseNotification
{
    public function __construct(public Attendance $attendance) {}

    public function title(): string
    {
        return 'Attendance needs review';
    }

    public function message(): string
    {
        return sprintf(
            '%s has a flagged attendance record for %s (%s).',
            $this->attendance->student->user->name,
            $this->attendance->date->format('d M Y'),
            $this->attendance->status->label(),
        );
    }

    public function url(): string
    {
        return route('supervisor.attendance.index');
    }

    public function icon(): string
    {
        return 'bi-geo-alt';
    }
}
