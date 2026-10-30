<?php

namespace App\Http\Controllers\Lecturer;

use App\Enums\DailyLogStatus;
use App\Enums\RiskLevel;
use App\Enums\VisitStatus;
use App\Http\Controllers\Controller;
use App\Models\DailyLog;
use App\Models\Internship;
use App\Models\LecturerVisit;
use App\Services\AttendanceStatsService;
use App\Services\RiskService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, AttendanceStatsService $stats, RiskService $risk): View
    {
        $lecturer = $request->user()->lecturer;

        abort_unless($lecturer, 403, 'Your lecturer profile is not linked to this account.');

        $internships = Internship::query()
            ->where('lecturer_id', $lecturer->id)
            ->with(['student.user', 'student.department', 'company', 'period', 'supervisor.user'])
            ->orderByDesc('start_date')
            ->get();

        $active = $internships->filter(fn (Internship $internship) => $internship->isActive())->values();
        $activeIds = $active->pluck('id');

        $assessments = $risk->assessMany($internships);
        $activeAssessments = $assessments->only($activeIds->all());

        $riskDistribution = ['normal' => 0, 'warning' => 0, 'high_risk' => 0];

        foreach ($activeAssessments as $assessment) {
            $riskDistribution[$assessment->level->value]++;
        }

        $atRiskStudents = $active
            ->filter(fn (Internship $internship) => $assessments->has($internship->id) && $assessments[$internship->id]->hasReasons())
            ->map(fn (Internship $internship) => ['internship' => $internship, 'assessment' => $assessments[$internship->id]])
            ->sortByDesc(fn (array $row) => ($row['assessment']->level->weight() * 100) + count($row['assessment']->reasons))
            ->take(7)
            ->values();

        $pendingLogsCount = DailyLog::query()
            ->whereIn('internship_id', $internships->pluck('id'))
            ->pendingReview()
            ->count();

        $recentLogs = DailyLog::query()
            ->whereIn('internship_id', $internships->pluck('id'))
            ->whereIn('status', [DailyLogStatus::Submitted->value, DailyLogStatus::UnderReview->value])
            ->with(['student.user', 'internship.company'])
            ->orderByDesc('submitted_at')
            ->limit(6)
            ->get();

        $upcomingVisits = LecturerVisit::query()
            ->where('lecturer_id', $lecturer->id)
            ->where('status', VisitStatus::Scheduled)
            ->whereDate('visit_date', '>=', today())
            ->with(['internship.student.user', 'internship.company'])
            ->orderBy('visit_date')
            ->get();

        $completedVisits = LecturerVisit::query()
            ->where('lecturer_id', $lecturer->id)
            ->where('status', VisitStatus::Completed)
            ->count();

        $trend = $stats->attendanceTrend($active, 14);

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

        $riskChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => ['Normal', 'Warning', 'High Risk'],
                'datasets' => [[
                    'data' => [
                        $riskDistribution[RiskLevel::Normal->value],
                        $riskDistribution[RiskLevel::Warning->value],
                        $riskDistribution[RiskLevel::HighRisk->value],
                    ],
                    'backgroundColor' => ['#16a34a', '#d97706', '#dc2626'],
                    'borderWidth' => 2,
                    'borderColor' => '#fff',
                ]],
            ],
            'options' => [
                'cutout' => '68%',
                'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 10, 'padding' => 12]]],
            ],
        ];

        $averageAttendance = $stats->averagePercentage($active);

        return view('lecturer.dashboard', compact(
            'internships',
            'active',
            'assessments',
            'riskDistribution',
            'atRiskStudents',
            'pendingLogsCount',
            'recentLogs',
            'upcomingVisits',
            'completedVisits',
            'trendChart',
            'riskChart',
            'averageAttendance',
        ));
    }
}
