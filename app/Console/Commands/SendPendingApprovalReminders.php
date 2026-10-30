<?php

namespace App\Console\Commands;

use App\Enums\DailyLogStatus;
use App\Models\CompanySupervisor;
use App\Models\DailyLog;
use App\Notifications\PendingApprovalsReminderNotification;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendPendingApprovalReminders extends Command
{
    protected $signature = 'interntrack:remind-approvals';

    protected $description = 'Send supervisors a daily digest of daily logs awaiting review';

    public function handle(NotificationService $notifications): int
    {
        $counts = DailyLog::query()
            ->join('internships', 'internships.id', '=', 'daily_logs.internship_id')
            ->whereIn('daily_logs.status', [
                DailyLogStatus::Submitted->value,
                DailyLogStatus::UnderReview->value,
            ])
            ->whereNull('internships.deleted_at')
            ->selectRaw('internships.company_supervisor_id as supervisor_id, count(*) as pending')
            ->groupBy('internships.company_supervisor_id')
            ->pluck('pending', 'supervisor_id');

        $supervisors = CompanySupervisor::query()
            ->with('user')
            ->whereIn('id', $counts->keys())
            ->get();

        $sent = 0;

        foreach ($supervisors as $supervisor) {
            $user = $supervisor->user;
            $pending = (int) $counts->get($supervisor->id, 0);

            if (! $user || ! $user->isActive() || $pending === 0) {
                continue;
            }

            if ($notifications->hasNotifiedSince($user, PendingApprovalsReminderNotification::class, now()->subHours(20))) {
                continue;
            }

            $notifications->notifyPendingApprovals($user, $pending);
            $sent++;
        }

        $this->info(sprintf('Pending approval reminders sent: %d (supervisors with pending logs: %d).', $sent, $counts->count()));

        return self::SUCCESS;
    }
}
