<?php

namespace App\Notifications;

use App\Models\Attendance;

class AttendanceCorrectedNotification extends BaseNotification
{
    public function __construct(
        public Attendance $attendance,
        public string $reason,
    ) {}

    public function title(): string
    {
        return 'Attendance record corrected';
    }

    public function message(): string
    {
        return sprintf(
            'Your attendance record for %s was corrected by the internship office: in %s, out %s, status %s. Reason: %s',
            $this->attendance->date->format('d M Y'),
            $this->attendance->check_in_time?->format('H:i') ?? '—',
            $this->attendance->check_out_time?->format('H:i') ?? '—',
            $this->attendance->status->label(),
            $this->reason,
        );
    }

    public function url(): string
    {
        return route('student.attendance.index');
    }

    public function icon(): string
    {
        return 'bi-pencil-square';
    }
}
