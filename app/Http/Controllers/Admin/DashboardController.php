<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Enums\UserStatus;
use App\Enums\VisitStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\DailyLog;
use App\Models\Internship;
use App\Models\LecturerVisit;
use App\Models\Student;
use App\Models\User;
use App\Services\AttendanceStatsService;
use App\Services\RiskService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(AttendanceStatsService $stats, RiskService $risk): View
    {
        $activeInternships = Internship::query()
            ->where('status', 'active')
            ->with(['student.user', 'company'])
            ->get();

        $todayCounts = Attendance::query()
            ->whereDate('date', today())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $logStatusCounts = DailyLog::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $riskDistribution = $risk->distribution($activeInternships);

        $trend = $stats->attendanceTrend($activeInternships, 14);

        $trendChart = [
            'type' => 'bar',
            'data' => [
                'labels' => $trend['labels'],
                'datasets' => [
                    [
                        'label' => 'Present',
                        'data' => $trend['present'],
                        'backgroundColor' => '#16a34a',
                        'borderRadius' => 4,
                    ],
                    [
                        'label' => 'Late',
                        'data' => $trend['late'],
                        'backgroundColor' => '#d97706',
                        'borderRadius' => 4,
                    ],
                    [
                        'label' => 'Flagged',
                        'data' => $trend['flagged'],
                        'backgroundColor' => '#dc2626',
                        'borderRadius' => 4,
                    ],
                ],
            ],
            'options' => [
                'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 10, 'padding' => 12]]],
                'scales' => [
                    'x' => ['stacked' => true, 'grid' => ['display' => false]],
                    'y' => ['stacked' => true, 'beginAtZero' => true, 'ticks' => ['precision' => 0]],
                ],
            ],
        ];

        $logChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => ['Draft', 'Submitted', 'Under review', 'Approved', 'Returned'],
                'datasets' => [[
                    'data' => [
                        (int) ($logStatusCounts[DailyLogStatus::Draft->value] ?? 0),
                        (int) ($logStatusCounts[DailyLogStatus::Submitted->value] ?? 0),
                        (int) ($logStatusCounts[DailyLogStatus::UnderReview->value] ?? 0),
                        (int) ($logStatusCounts[DailyLogStatus::Approved->value] ?? 0),
                        (int) ($logStatusCounts[DailyLogStatus::Rejected->value] ?? 0),
                    ],
                    'backgroundColor' => ['#64748b', '#0891b2', '#d97706', '#16a34a', '#dc2626'],
                    'borderWidth' => 2,
                    'borderColor' => '#fff',
                ]],
            ],
            'options' => [
                'cutout' => '68%',
                'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 10, 'padding' => 12]]],
            ],
        ];

        $todayTotal = (int) $todayCounts->sum();
        $todayAttended = (int) ($todayCounts[AttendanceStatus::Present->value] ?? 0) + (int) ($todayCounts[AttendanceStatus::Late->value] ?? 0);
        $todayRate = $todayTotal > 0 ? round(($todayAttended / $todayTotal) * 100, 1) : 0.0;

        $pendingLogs = (int) ($logStatusCounts[DailyLogStatus::Submitted->value] ?? 0) + (int) ($logStatusCounts[DailyLogStatus::UnderReview->value] ?? 0);

        $flaggedAttendance = Attendance::query()->flagged()->count();

        $upcomingVisits = LecturerVisit::query()
            ->where('status', VisitStatus::Scheduled)
            ->whereDate('visit_date', '>=', today())
            ->with(['internship.student.user', 'internship.company'])
            ->orderBy('visit_date')
            ->limit(5)
            ->get();

        $recentAudit = AuditLog::query()
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $recentSubmissions = DailyLog::query()
            ->pendingReview()
            ->with(['student.user', 'internship.company'])
            ->orderByDesc('submitted_at')
            ->limit(6)
            ->get();

        $studentsCount = Student::query()->count();
        $inactiveUsers = User::query()->where('status', '!=', UserStatus::Active)->whereHas('role', fn ($query) => $query->where('name', '!=', 'admin'))->count();

        return view('admin.dashboard', compact(
            'activeInternships',
            'studentsCount',
            'inactiveUsers',
            'todayCounts',
            'todayRate',
            'todayTotal',
            'todayAttended',
            'logStatusCounts',
            'pendingLogs',
            'flaggedAttendance',
            'riskDistribution',
            'trendChart',
            'logChart',
            'upcomingVisits',
            'recentAudit',
            'recentSubmissions',
        ));
    }
}
