<?php

namespace App\Notifications;

use App\Models\LecturerVisit;

class VisitScheduledNotification extends BaseNotification
{
    public function __construct(public LecturerVisit $visit) {}

    public function title(): string
    {
        return 'Internship visit scheduled';
    }

    public function message(): string
    {
        return sprintf(
            '%s scheduled a visit on %s. Purpose: %s.',
            $this->visit->lecturer->user->name,
            $this->visit->visit_date->format('d M Y'),
            $this->visit->purpose,
        );
    }

    public function url(): string
    {
        return route('student.dashboard');
    }

    public function icon(): string
    {
        return 'bi-calendar-check';
    }
}
