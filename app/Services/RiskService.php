<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Enums\RiskLevel;
use App\Enums\VisitStatus;
use App\Models\Approval;
use App\Models\DailyLog;
use App\Models\Internship;
use App\Models\LecturerVisit;
use App\Support\RiskAssessment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RiskService
{
    public function __construct(private readonly AttendanceStatsService $stats) {}

    public function assess(Internship $internship): RiskAssessment
    {
        $expectedDays = $this->stats->expectedWorkingDays($internship);
        $attendedDays = $this->stats->attendedDays($internship);
        $percentage = $this->stats->attendancePercentage($internship);
        $consecutiveAbsences = $this->stats->consecutiveAbsences($internship);

        $logs = $internship->dailyLogs()->get(['status', 'date']);
        $logsSubmitted = $logs->count();
        $pendingLogs = $logs->filter(fn ($log) => $log->status->isPendingReview())->count();
        $rejectedLogs = $logs->filter(fn ($log) => $log->status === DailyLogStatus::Rejected)->count();
        $missedLogDates = $this->stats->missedLogDates($internship)
            ->filter(fn (Carbon $date) => $date->lt(today()));
        $missedLogs = $missedLogDates->count();

        $incompleteCheckouts = $internship->attendances()
            ->where('status', AttendanceStatus::Incomplete)
            ->whereDate('date', '>=', now()->subDays(30)->toDateString())
            ->count();

        $lastSupervisorAction = Approval::query()
            ->where('subject_type', DailyLog::class)
            ->whereIn('subject_id', $internship->dailyLogs()->pluck('id'))
            ->max('created_at');

        if (! $lastSupervisorAction) {
            $lastSupervisorAction = $internship->evaluations()->max('created_at');
        }

        $supervisorActive = $lastSupervisorAction !== null
            && Carbon::parse($lastSupervisorAction)->gte(now()->subDays(14));

        $reasons = [];

        if ($consecutiveAbsences >= 3) {
            $reasons[] = sprintf('No attendance for the last %d working days', $consecutiveAbsences);
        }

        if ($missedLogs >= 2) {
            $reasons[] = sprintf('%d attended day(s) have no daily log', $missedLogs);
        } elseif ($missedLogs === 1) {
            $reasons[] = 'One attended day has no daily log';
        }

        if ($rejectedLogs >= 2) {
            $reasons[] = sprintf('%d daily logs were rejected', $rejectedLogs);
        }

        if ($expectedDays >= 5 && ! $supervisorActive) {
            $reasons[] = 'No supervisor review activity in the last 14 days';
        }

        if ($expectedDays >= 5 && $percentage < 70) {
            $reasons[] = sprintf('Low attendance (%s%%)', number_format($percentage, 1));
        }

        if ($incompleteCheckouts >= 2) {
            $reasons[] = sprintf('%d incomplete check-out(s) in the last 30 days', $incompleteCheckouts);
        }

        $level = RiskLevel::Normal;

        if ($reasons !== []) {
            $level = RiskLevel::Warning;
        }

        if (($expectedDays >= 5 && $percentage < 50)
            || $consecutiveAbsences >= 5
            || count($reasons) >= 3) {
            $level = RiskLevel::HighRisk;
        }

        return new RiskAssessment(
            level: $level,
            reasons: $reasons,
            attendancePercentage: $percentage,
            expectedDays: $expectedDays,
            attendedDays: $attendedDays,
            logsSubmitted: $logsSubmitted,
            pendingLogs: $pendingLogs,
            missedLogs: $missedLogs,
            rejectedLogs: $rejectedLogs,
            incompleteCheckouts: $incompleteCheckouts,
            consecutiveAbsences: $consecutiveAbsences,
            supervisorActive: $supervisorActive,
            lastAttendanceDate: $this->stats->lastAttendanceDate($internship),
        );
    }

    /**
     * @param  Collection<int, Internship>  $internships
     * @return Collection<int, RiskAssessment> keyed by internship id
     */
    public function assessMany(Collection $internships): Collection
    {
        return $internships->mapWithKeys(fn (Internship $internship) => [$internship->id => $this->assess($internship)]);
    }

    /**
     * @param  Collection<int, Internship>  $internships
     * @return array{normal: int, warning: int, high_risk: int}
     */
    public function distribution(Collection $internships): array
    {
        $counts = ['normal' => 0, 'warning' => 0, 'high_risk' => 0];

        foreach ($internships as $internship) {
            $counts[$this->assess($internship)->level->value]++;
        }

        return $counts;
    }

    public function studentsAtRisk(Collection $internships): int
    {
        return $internships->filter(fn (Internship $internship) => $this->assess($internship)->hasReasons())->count();
    }

    /**
     * @param  Collection<int, Internship>  $internships
     */
    public function upcomingVisits(Collection $internships): int
    {
        return LecturerVisit::query()
            ->whereIn('internship_id', $internships->pluck('id'))
            ->where('status', VisitStatus::Scheduled)
            ->whereDate('visit_date', '>=', today())
            ->count();
    }
}
