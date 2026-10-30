<?php

namespace App\Console\Commands;

use App\Enums\CompanyStatus;
use App\Enums\InternshipStatus;
use App\Enums\PeriodStatus;
use App\Models\Internship;
use App\Notifications\AttendanceReminderNotification;
use App\Services\AttendanceStatsService;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendAttendanceReminders extends Command
{
    protected $signature = 'interntrack:remind-attendance';

    protected $description = 'Remind students with an active placement who have not checked in today';

    public function handle(AttendanceStatsService $stats, NotificationService $notifications): int
    {
        $today = now()->startOfDay();

        if (! $stats->isWorkingDay($today)) {
            $this->info('Today is not a configured working day; no attendance reminders sent.');

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

            if ($internship->attendances()->whereDate('date', $today->toDateString())->exists()) {
                continue;
            }

            if ($notifications->hasNotifiedSince($user, AttendanceReminderNotification::class, now()->subHours(20))) {
                continue;
            }

            $notifications->notifyAttendanceReminder($user);
            $sent++;
        }

        $this->info(sprintf('Attendance reminders sent: %d (checked %d active placements).', $sent, $internships->count()));

        return self::SUCCESS;
    }
}
