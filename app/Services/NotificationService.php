<?php

namespace App\Services;

use App\Enums\ApprovalAction;
use App\Models\Attendance;
use App\Models\DailyLog;
use App\Models\DateReopening;
use App\Models\Evaluation;
use App\Models\Internship;
use App\Models\LecturerVisit;
use App\Models\User;
use App\Notifications\AttendanceCorrectedNotification;
use App\Notifications\AttendanceFlaggedNotification;
use App\Notifications\AttendanceReminderNotification;
use App\Notifications\AttendanceReviewedNotification;
use App\Notifications\DailyLogReminderNotification;
use App\Notifications\EvaluationSubmittedNotification;
use App\Notifications\LogReopenedNotification;
use App\Notifications\LogReviewedNotification;
use App\Notifications\LogSubmittedNotification;
use App\Notifications\PendingApprovalsReminderNotification;
use App\Notifications\RiskAlertNotification;
use App\Notifications\VisitScheduledNotification;
use App\Support\RiskAssessment;
use Carbon\CarbonInterface;
use Illuminate\Notifications\Notification;

class NotificationService
{
    public function send(?User $user, Notification $notification): void
    {
        if (! $user || ! $user->isActive()) {
            return;
        }

        $user->notify($notification);
    }

    public function notifyLogSubmitted(DailyLog $log): void
    {
        $this->send($log->internship->supervisor?->user, new LogSubmittedNotification($log));
    }

    public function notifyLogReviewed(DailyLog $log, ApprovalAction $action, ?string $comments = null): void
    {
        if ($action === ApprovalAction::Commented) {
            return;
        }

        $this->send($log->student->user, new LogReviewedNotification($log, $action, $comments));
    }

    public function notifyLogReopened(DateReopening $reopening): void
    {
        $this->send($reopening->internship->student->user, new LogReopenedNotification($reopening));
    }

    public function notifyAttendanceFlagged(Attendance $attendance): void
    {
        $this->send($attendance->internship->supervisor?->user, new AttendanceFlaggedNotification($attendance));
    }

    public function notifyAttendanceReviewed(Attendance $attendance, string $decision, ?string $reason = null): void
    {
        $this->send($attendance->student->user, new AttendanceReviewedNotification($attendance, $decision, $reason));
    }

    public function notifyAttendanceCorrected(Attendance $attendance, User $admin, string $reason): void
    {
        $this->send($attendance->student->user, new AttendanceCorrectedNotification($attendance, $reason));
    }

    public function notifyVisitScheduled(LecturerVisit $visit): void
    {
        $this->send($visit->internship->student->user, new VisitScheduledNotification($visit));
    }

    public function notifyEvaluationSubmitted(Evaluation $evaluation): void
    {
        $this->send($evaluation->internship->student->user, new EvaluationSubmittedNotification($evaluation));
    }

    public function notifyRiskAlert(Internship $internship, RiskAssessment $assessment): void
    {
        $this->send($internship->lecturer?->user, new RiskAlertNotification($internship, $assessment));
    }

    public function notifyAttendanceReminder(User $student): void
    {
        $this->send($student, new AttendanceReminderNotification);
    }

    public function notifyLogReminder(User $student): void
    {
        $this->send($student, new DailyLogReminderNotification);
    }

    public function notifyPendingApprovals(User $supervisor, int $count): void
    {
        $this->send($supervisor, new PendingApprovalsReminderNotification($count));
    }

    public function hasNotifiedSince(User $user, string $notificationClass, CarbonInterface $since): bool
    {
        return $user->notifications()
            ->where('type', $notificationClass)
            ->where('created_at', '>=', $since)
            ->exists();
    }

    public function hasNotifiedAbout(
        User $user,
        string $notificationClass,
        string $dataKey,
        mixed $value,
        CarbonInterface $since,
    ): bool {
        return $user->notifications()
            ->where('type', $notificationClass)
            ->where('data->'.$dataKey, $value)
            ->where('created_at', '>=', $since)
            ->exists();
    }
}
