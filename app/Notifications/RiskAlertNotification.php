<?php

namespace App\Notifications;

use App\Models\Internship;
use App\Support\RiskAssessment;

class RiskAlertNotification extends BaseNotification
{
    public function __construct(
        public Internship $internship,
        public RiskAssessment $assessment,
    ) {}

    public function title(): string
    {
        return 'Student risk alert';
    }

    public function message(): string
    {
        return sprintf(
            '%s is at %s risk: %s',
            $this->internship->student->user->name,
            $this->assessment->level->label(),
            implode('; ', $this->assessment->reasons),
        );
    }

    public function url(): string
    {
        return route('lecturer.students.show', $this->internship->student_id);
    }

    public function icon(): string
    {
        return 'bi-exclamation-triangle';
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return parent::toArray($notifiable) + ['internship_id' => $this->internship->id];
    }
}
