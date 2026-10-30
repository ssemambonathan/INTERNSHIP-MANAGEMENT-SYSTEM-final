<?php

namespace App\Notifications;

use App\Models\Evaluation;

class EvaluationSubmittedNotification extends BaseNotification
{
    public function __construct(public Evaluation $evaluation) {}

    public function title(): string
    {
        return 'Weekly evaluation submitted';
    }

    public function message(): string
    {
        return sprintf(
            'A weekly evaluation for the week of %s was submitted. Average score: %s/%d.',
            $this->evaluation->week_start->format('d M Y'),
            number_format($this->evaluation->averageScore(), 1),
            (int) settings('evaluation_scale', 5),
        );
    }

    public function url(): string
    {
        return route('student.dashboard');
    }

    public function icon(): string
    {
        return 'bi-star';
    }
}
