<?php

namespace App\Notifications;

class AttendanceReminderNotification extends BaseNotification
{
    public function title(): string
    {
        return 'Attendance reminder';
    }

    public function message(): string
    {
        return 'You have not checked in today. Remember to check in from your internship location.';
    }

    public function url(): string
    {
        return route('student.attendance.index');
    }

    public function icon(): string
    {
        return 'bi-clock';
    }
}
