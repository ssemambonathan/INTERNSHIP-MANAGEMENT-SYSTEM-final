<?php

namespace App\Console\Commands;

use App\Enums\CompanyStatus;
use App\Enums\InternshipStatus;
use App\Enums\PeriodStatus;
use App\Models\Internship;
use App\Notifications\RiskAlertNotification;
use App\Services\NotificationService;
use App\Services\RiskService;
use Illuminate\Console\Command;

class SendRiskAlerts extends Command
{
    protected $signature = 'interntrack:risk-alerts';

    protected $description = 'Notify lecturers about active placements with new risk indicators';

    public function handle(RiskService $risk, NotificationService $notifications): int
    {
        $today = now()->startOfDay();

        $internships = Internship::query()
            ->with(['student.user', 'lecturer.user'])
            ->where('status', InternshipStatus::Active->value)
            ->whereDate('start_date', '<=', $today->toDateString())
            ->whereDate('end_date', '>=', $today->toDateString())
            ->whereHas('period', fn ($query) => $query->where('status', PeriodStatus::Active->value))
            ->whereHas('company', fn ($query) => $query->where('status', CompanyStatus::Active->value))
            ->get();

        $sent = 0;
        $flagged = 0;

        foreach ($internships as $internship) {
            $assessment = $risk->assess($internship);

            if (! $assessment->hasReasons()) {
                continue;
            }

            $flagged++;

            $user = $internship->lecturer?->user;

            if (! $user || ! $user->isActive()) {
                continue;
            }

            if ($notifications->hasNotifiedAbout(
                $user,
                RiskAlertNotification::class,
                'internship_id',
                $internship->id,
                now()->subDays(6),
            )) {
                continue;
            }

            $notifications->notifyRiskAlert($internship, $assessment);
            $sent++;
        }

        $this->info(sprintf(
            'Risk alerts sent: %d (flagged placements: %d of %d active).',
            $sent,
            $flagged,
            $internships->count(),
        ));

        return self::SUCCESS;
    }
}
