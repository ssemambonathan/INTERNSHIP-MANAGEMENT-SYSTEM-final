<?php

namespace App\Notifications;

use App\Enums\ApprovalAction;
use App\Models\DailyLog;

class LogReviewedNotification extends BaseNotification
{
    public function __construct(
        public DailyLog $log,
        public ApprovalAction $action,
        public ?string $comments = null,
    ) {}

    public function title(): string
    {
        return match ($this->action) {
            ApprovalAction::Approved => 'Daily log approved',
            ApprovalAction::CorrectionRequested => 'Correction requested for your daily log',
            ApprovalAction::Rejected => 'Daily log rejected',
            default => 'Daily log updated',
        };
    }

    public function message(): string
    {
        $base = sprintf('Your daily log for %s was %s.', $this->log->date->format('d M Y'), $this->action->label());

        if ($this->action === ApprovalAction::Approved) {
            $base = sprintf('Your daily log for %s has been approved.', $this->log->date->format('d M Y'));
        } elseif ($this->action === ApprovalAction::CorrectionRequested) {
            $base = sprintf('A correction was requested for your daily log for %s.', $this->log->date->format('d M Y'));
        } elseif ($this->action === ApprovalAction::Rejected) {
            $base = sprintf('Your daily log for %s was rejected.', $this->log->date->format('d M Y'));
        }

        return $this->comments ? $base.' Reason: '.$this->comments : $base;
    }

    public function url(): string
    {
        return route('student.logs.show', $this->log);
    }

    public function icon(): string
    {
        return $this->action === ApprovalAction::Approved ? 'bi-check-circle' : 'bi-exclamation-circle';
    }
}
