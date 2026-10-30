<?php

namespace App\Notifications;

use App\Models\Attendance;

class AttendanceReviewedNotification extends BaseNotification
{
    public function __construct(
        public Attendance $attendance,
        public string $decision,
        public ?string $reason = null,
    ) {}

    public function title(): string
    {
        return $this->decision === 'verify' ? 'Attendance verified' : 'Attendance rejected';
    }

    public function message(): string
    {
        $base = $this->decision === 'verify'
            ? sprintf('Your attendance for %s was verified.', $this->attendance->date->format('d M Y'))
            : sprintf('Your attendance for %s was rejected.', $this->attendance->date->format('d M Y'));

        return $this->reason ? $base.' Reason: '.$this->reason : $base;
    }

    public function url(): string
    {
        return route('student.attendance.index');
    }

    public function icon(): string
    {
        return $this->decision === 'verify' ? 'bi-patch-check' : 'bi-x-circle';
    }
}
