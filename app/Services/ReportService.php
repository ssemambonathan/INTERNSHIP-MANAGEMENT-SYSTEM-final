<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Models\Evaluation;
use App\Models\Internship;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(
        private readonly AttendanceStatsService $stats,
        private readonly RiskService $risk,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function studentReport(Internship $internship, bool $includeRisk = false): array
    {
        $internship->load([
            'student.user',
            'student.faculty',
            'student.department',
            'student.course',
            'student.academicYear',
            'company',
            'supervisor.user',
            'lecturer.user',
            'period.academicYear',
        ]);

        $attendances = $internship->attendances()
            ->orderBy('date')
            ->get();

        $logs = $internship->dailyLogs()
            ->withCount('evidenceFiles')
            ->orderBy('date')
            ->get();

        $evaluations = $internship->evaluations()
            ->orderBy('week_start')
            ->get();

        $visits = $internship->visits()
            ->withCount('evidenceFiles')
            ->orderBy('visit_date')
            ->get();

        $attendanceCounts = $attendances
            ->groupBy(fn ($attendance) => $attendance->status->value)
            ->map(fn (Collection $group) => $group->count());

        $logCounts = $logs
            ->groupBy(fn ($log) => $log->status->value)
            ->map(fn (Collection $group) => $group->count());

        $criteriaAverages = [];

        foreach (array_keys(Evaluation::$criteria) as $criterion) {
            $criteriaAverages[$criterion] = $evaluations->isEmpty()
                ? null
                : round($evaluations->avg($criterion), 2);
        }

        $evaluationAverage = $evaluations->isEmpty()
            ? null
            : round($evaluations->avg(fn ($evaluation) => $evaluation->averageScore()), 2);

        $monthlyAttendance = $attendances
            ->groupBy(fn ($attendance) => $attendance->date->format('Y-m'))
            ->map(function (Collection $group, string $month) {
                $present = $group->filter(fn ($attendance) => $attendance->status === AttendanceStatus::Present)->count();
                $late = $group->filter(fn ($attendance) => $attendance->status === AttendanceStatus::Late)->count();
                $flagged = $group->filter(fn ($attendance) => $attendance->status->requiresReview())->count();
                $rejected = $group->filter(fn ($attendance) => $attendance->status === AttendanceStatus::Rejected)->count();

                return [
                    'month' => Carbon::createFromFormat('Y-m', $month)->format('F Y'),
                    'present' => $present,
                    'late' => $late,
                    'flagged' => $flagged,
                    'rejected' => $rejected,
                    'attended' => $present + $late,
                ];
            })
            ->values();

        $expectedDays = $this->stats->expectedWorkingDays($internship);
        $attendedDays = $this->stats->attendedDays($internship);
        $attendancePercentage = $this->stats->attendancePercentage($internship);
        $missedDates = $this->stats->missedLogDates($internship);

        $assessment = $this->risk->assess($internship);

        $totalHours = $logs->sum(fn ($log) => (float) $log->hours_worked);

        return [
            'internship' => $internship,
            'student' => $internship->student,
            'company' => $internship->company,
            'supervisor' => $internship->supervisor,
            'lecturer' => $internship->lecturer,
            'period' => $internship->period,
            'attendances' => $attendances,
            'attendanceCounts' => $attendanceCounts,
            'attendancePercentage' => $attendancePercentage,
            'expectedDays' => $expectedDays,
            'attendedDays' => $attendedDays,
            'logs' => $logs,
            'logCounts' => $logCounts,
            'totalHours' => round($totalHours, 1),
            'evaluations' => $evaluations,
            'evaluationAverage' => $evaluationAverage,
            'criteriaAverages' => $criteriaAverages,
            'visits' => $visits,
            'monthlyAttendance' => $monthlyAttendance,
            'missedDates' => $missedDates,
            'assessment' => $includeRisk ? $assessment : null,
            'includeRisk' => $includeRisk,
            'generatedAt' => now(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function universityReport(CarbonInterface $from, CarbonInterface $to, array $filters = []): array
    {
        $internships = Internship::query()
            ->with([
                'student.user',
                'student.faculty',
                'student.department',
                'student.course',
                'company',
                'supervisor.user',
                'lecturer.user',
                'period',
            ])
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->when($filters['faculty_id'] ?? null, fn ($query, $facultyId) => $query->whereHas('student', fn ($studentQuery) => $studentQuery->where('faculty_id', $facultyId)))
            ->when($filters['company_id'] ?? null, fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->when($filters['period_id'] ?? null, fn ($query, $periodId) => $query->where('internship_period_id', $periodId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('start_date')
            ->get();

        $rows = $internships->map(function (Internship $internship) use ($from, $to) {
            $attendanceCutoff = $to->lt(now()) ? $to : now();
            $attendancePercentage = $this->stats->attendancePercentage($internship, $attendanceCutoff);
            $assessment = $this->risk->assess($internship);

            $logCounts = $internship->dailyLogs()
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $evaluationAverage = $internship->evaluations()
                ->whereBetween('week_start', [$from->toDateString(), $to->toDateString()])
                ->get()
                ->avg(fn ($evaluation) => $evaluation->averageScore());

            return [
                'internship' => $internship,
                'student' => $internship->student,
                'company' => $internship->company,
                'supervisor' => $internship->supervisor,
                'lecturer' => $internship->lecturer,
                'attendancePercentage' => $attendancePercentage,
                'attendedDays' => $this->stats->attendedDays($internship, $attendanceCutoff),
                'expectedDays' => $this->stats->expectedWorkingDays($internship, $attendanceCutoff),
                'logsTotal' => $internship->dailyLogs()->whereBetween('date', [$from->toDateString(), $to->toDateString()])->count(),
                'logsApproved' => (int) ($logCounts[DailyLogStatus::Approved->value] ?? 0),
                'logsPending' => (int) ($logCounts[DailyLogStatus::Submitted->value] ?? 0) + (int) ($logCounts[DailyLogStatus::UnderReview->value] ?? 0),
                'evaluationAverage' => $evaluationAverage !== null ? round($evaluationAverage, 2) : null,
                'visits' => $internship->visits()->whereBetween('visit_date', [$from->toDateString(), $to->toDateString()])->count(),
                'risk' => $assessment,
            ];
        })->values();

        $attendanceRows = $rows->filter(fn (array $row) => $row['expectedDays'] > 0);

        $summary = [
            'internships' => $rows->count(),
            'students' => $rows->pluck('student.id')->unique()->count(),
            'companies' => $rows->pluck('company.id')->filter()->unique()->count(),
            'averageAttendance' => $attendanceRows->isEmpty() ? 0 : round($attendanceRows->avg('attendancePercentage'), 1),
            'atRisk' => $rows->filter(fn (array $row) => $row['risk']->level->value !== 'normal')->count(),
            'logsApproved' => $rows->sum('logsApproved'),
            'logsPending' => $rows->sum('logsPending'),
            'visits' => $rows->sum('visits'),
        ];

        return [
            'rows' => $rows,
            'summary' => $summary,
            'from' => Carbon::parse($from),
            'to' => Carbon::parse($to),
            'filters' => $filters,
            'generatedAt' => now(),
        ];
    }
}
