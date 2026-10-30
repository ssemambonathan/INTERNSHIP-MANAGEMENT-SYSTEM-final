<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\DailyLogStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\DailyLog;
use App\Models\Evaluation;
use App\Models\Internship;
use App\Services\AttendanceStatsService;
use App\Services\RiskService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, AttendanceStatsService $stats, RiskService $risk): View
    {
        $supervisor = $request->user()->companySupervisor()->with('company')->first();

        if (! $supervisor) {
            return view('supervisor.dashboard', ['supervisor' => null]);
        }

        $internships = Internship::query()
            ->where('company_supervisor_id', $supervisor->id)
            ->where('status', 'active')
            ->with(['student.user', 'student.department', 'period'])
            ->get();

        $ids = $internships->pluck('id');

        $pendingLogsQuery = DailyLog::query()
            ->whereIn('internship_id', $ids)
            ->pendingReview()
            ->with(['student.user', 'internship.company'])
            ->orderByDesc('submitted_at');

        $pendingLogs = (clone $pendingLogsQuery)->limit(5)->get();
        $pendingCount = (clone $pendingLogsQuery)->count();

        $todayAttendance = Attendance::query()
            ->whereIn('internship_id', $ids)
            ->whereDate('date', today())
            ->with(['student.user'])
            ->get();

        $presentToday = $todayAttendance->filter(fn (Attendance $attendance) => $attendance->status->countsAsAttended())->count();
        $flaggedCount = Attendance::query()
            ->whereIn('internship_id', $ids)
            ->flagged()
            ->count();

        $missedLogs = $internships->sum(fn (Internship $internship) => $stats->missedLogDates($internship, now()->subDays(7))->count());

        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $evaluatedThisWeek = Evaluation::query()
            ->where('company_supervisor_id', $supervisor->id)
            ->whereDate('week_start', $weekStart->toDateString())
            ->count();

        $riskCollection = $risk->assessMany($internships);
        $riskDistribution = $risk->distribution($internships);

        $trendRaw = $stats->attendanceTrend($internships, 14);
        $trendChart = [
            'type' => 'line',
            'data' => [
                'labels' => $trendRaw['labels'],
                'datasets' => [
                    [
                        'label' => 'Present',
                        'data' => $trendRaw['present'],
                        'borderColor' => '#16a34a',
                        'backgroundColor' => 'rgba(22,163,74,.12)',
                        'fill' => true,
                        'tension' => .35,
                        'borderWidth' => 2,
                        'pointRadius' => 2,
                    ],
                    [
                        'label' => 'Late',
                        'data' => $trendRaw['late'],
                        'borderColor' => '#d97706',
                        'backgroundColor' => 'rgba(217,119,6,.08)',
                        'fill' => false,
                        'tension' => .35,
                        'borderWidth' => 2,
                        'pointRadius' => 2,
                    ],
                    [
                        'label' => 'Flagged',
                        'data' => $trendRaw['flagged'],
                        'borderColor' => '#dc2626',
                        'backgroundColor' => 'rgba(220,38,38,.08)',
                        'fill' => false,
                        'tension' => .35,
                        'borderWidth' => 2,
                        'pointRadius' => 2,
                    ],
                ],
            ],
            'options' => [
                'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 10, 'padding' => 14]]],
            ],
        ];

        $statusCounts = DailyLog::query()
            ->whereIn('internship_id', $ids)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $logChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => ['Draft', 'Submitted', 'Under review', 'Approved', 'Rejected'],
                'datasets' => [[
                    'data' => [
                        (int) ($statusCounts[DailyLogStatus::Draft->value] ?? 0),
                        (int) ($statusCounts[DailyLogStatus::Submitted->value] ?? 0),
                        (int) ($statusCounts[DailyLogStatus::UnderReview->value] ?? 0),
                        (int) ($statusCounts[DailyLogStatus::Approved->value] ?? 0),
                        (int) ($statusCounts[DailyLogStatus::Rejected->value] ?? 0),
                    ],
                    'backgroundColor' => ['#94a3b8', '#2563a8', '#d97706', '#16a34a', '#dc2626'],
                    'borderWidth' => 2,
                    'borderColor' => '#fff',
                ]],
            ],
            'options' => ['cutout' => '68%', 'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 10, 'padding' => 12]]]],
        ];

        $studentsAtRisk = $internships
            ->map(fn (Internship $internship) => ['internship' => $internship, 'assessment' => $riskCollection->get($internship->id)])
            ->filter(fn (array $row) => $row['assessment']?->hasReasons())
            ->sortByDesc(fn (array $row) => $row['assessment']->level->weight())
            ->take(4);

        $recentActivity = DailyLog::query()
            ->whereIn('internship_id', $ids)
            ->with(['student.user'])
            ->latest('updated_at')
            ->limit(6)
            ->get();

        return view('supervisor.dashboard', compact(
            'supervisor',
            'internships',
            'pendingLogs',
            'pendingCount',
            'presentToday',
            'flaggedCount',
            'missedLogs',
            'weekStart',
            'evaluatedThisWeek',
            'riskDistribution',
            'trendChart',
            'logChart',
            'studentsAtRisk',
            'recentActivity',
            'todayAttendance',
        ));
    }
}
