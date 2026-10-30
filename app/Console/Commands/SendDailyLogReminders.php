<?php

namespace App\Console\Commands;

use App\Enums\CompanyStatus;
use App\Enums\InternshipStatus;
use App\Enums\PeriodStatus;
use App\Models\Internship;
use App\Notifications\DailyLogReminderNotification;
use App\Services\AttendanceStatsService;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendDailyLogReminders extends Command
{
    protected $signature = 'interntrack:remind-logs';

    protected $description = 'Remind students who attended today but have not submitted a daily log';

    public function handle(AttendanceStatsService $stats, NotificationService $notifications): int
    {
        $today = now()->startOfDay();

        if (! $stats->isWorkingDay($today)) {
            $this->info('Today is not a configured working day; no log reminders sent.');

            return self::SUCCESS;
        }

        $internships = Internship::query()
            ->with('student.user')
            ->where('status', InternshipStatus::Active->value)
            ->whereDate('start_date', '<=', $today->toDateString())
            ->whereDate('end_date', '>=', $today->toDateString())
            ->whereHas('period', fn ($query) => $query->where('status', PeriodStatus::Active->value))
            ->whereHas('company', fn ($query) => $query->where('status', CompanyStatus::Active->value))
            ->get();

        $sent = 0;

        foreach ($internships as $internship) {
            $user = $internship->student?->user;

            if (! $user || ! $user->isActive()) {
                continue;
            }

            $attendance = $internship->attendances()
                ->whereDate('date', $today->toDateString())
                ->first();

            if (! $attendance || ! $attendance->qualifiesForLog()) {
                continue;
            }

            if ($internship->dailyLogs()->whereDate('date', $today->toDateString())->exists()) {
                continue;
            }

            if ($notifications->hasNotifiedSince($user, DailyLogReminderNotification::class, now()->subHours(20))) {
                continue;
            }

            $notifications->notifyLogReminder($user);
            $sent++;
        }

        $this->info(sprintf('Daily log reminders sent: %d (checked %d active placements).', $sent, $internships->count()));

        return self::SUCCESS;
    }
}
