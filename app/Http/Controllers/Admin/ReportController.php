<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InternshipStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Faculty;
use App\Models\InternshipPeriod;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports): View
    {
        [$from, $to, $filters] = $this->filters($request);
        $report = $reports->universityReport($from, $to, $filters);

        $riskCounts = ['normal' => 0, 'warning' => 0, 'high_risk' => 0];

        foreach ($report['rows'] as $row) {
            $riskCounts[$row['risk']->level->value]++;
        }

        $riskChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => ['Normal', 'Warning', 'High Risk'],
                'datasets' => [[
                    'data' => array_values($riskCounts),
                    'backgroundColor' => ['#16a34a', '#d97706', '#dc2626'],
                    'borderWidth' => 2,
                    'borderColor' => '#fff',
                ]],
            ],
            'options' => ['cutout' => '65%', 'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 10, 'padding' => 12]]]],
        ];

        $companyAttendance = $report['rows']
            ->filter(fn (array $row) => $row['company'] !== null)
            ->groupBy(fn (array $row) => $row['company']->name)
            ->map(fn (Collection $rows) => round($rows->avg('attendancePercentage'), 1))
            ->sortDesc();

        $attendanceChart = [
            'type' => 'bar',
            'data' => [
                'labels' => $companyAttendance->keys()->all(),
                'datasets' => [[
                    'label' => 'Average attendance (%)',
                    'data' => $companyAttendance->values()->all(),
                    'backgroundColor' => '#1d4b7a',
                    'borderRadius' => 6,
                    'maxBarThickness' => 42,
                ]],
            ],
            'options' => [
                'indexAxis' => 'y',
                'plugins' => ['legend' => ['display' => false]],
                'scales' => [
                    'x' => ['beginAtZero' => true, 'max' => 100, 'grid' => ['color' => '#eef2f7']],
                    'y' => ['grid' => ['display' => false]],
                ],
            ],
        ];

        $logTotal = (int) $report['summary']['logsApproved'] + (int) $report['summary']['logsPending'];
        $otherLogs = max(0, (int) $report['rows']->sum('logsTotal') - $logTotal);

        $logChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => ['Approved', 'Pending review', 'Other'],
                'datasets' => [[
                    'data' => [(int) $report['summary']['logsApproved'], (int) $report['summary']['logsPending'], $otherLogs],
                    'backgroundColor' => ['#16a34a', '#d97706', '#cbd5e1'],
                    'borderWidth' => 2,
                    'borderColor' => '#fff',
                ]],
            ],
            'options' => ['cutout' => '65%', 'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 10, 'padding' => 12]]]],
        ];

        return view('admin.reports.index', [
            'report' => $report,
            'riskChart' => $riskChart,
            'attendanceChart' => $attendanceChart,
            'logChart' => $logChart,
            'faculties' => Faculty::query()->orderBy('name')->get(),
            'companies' => Company::query()->orderBy('name')->get(),
            'periods' => InternshipPeriod::query()->orderByDesc('start_date')->get(),
            'from' => $from,
            'to' => $to,
            'filters' => $filters,
            'filterLabels' => $this->filterLabels($filters),
        ]);
    }

    public function export(Request $request, ReportService $reports): Response
    {
        [$from, $to, $filters] = $this->filters($request);
        $report = $reports->universityReport($from, $to, $filters);

        $filename = sprintf(
            'interntrack-university-report-%s-to-%s.pdf',
            $from->format('Y-m-d'),
            $to->format('Y-m-d'),
        );

        return Pdf::loadView('pdf.university-report', [
            'report' => $report,
            'filterLabels' => $this->filterLabels($filters),
        ])
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, string>
     */
    private function filterLabels(array $filters): array
    {
        return array_filter([
            'Faculty' => $filters['faculty_id'] ? Faculty::query()->whereKey($filters['faculty_id'])->value('name') : null,
            'Company' => $filters['company_id'] ? Company::query()->whereKey($filters['company_id'])->value('name') : null,
            'Internship period' => $filters['period_id'] ? InternshipPeriod::query()->whereKey($filters['period_id'])->value('name') : null,
            'Placement status' => $filters['status'] ? InternshipStatus::from($filters['status'])->label() : null,
        ], fn ($value) => filled($value));
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: array<string, mixed>}
     */
    private function filters(Request $request): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'faculty_id' => ['nullable', 'integer', Rule::exists('faculties', 'id')],
            'company_id' => ['nullable', 'integer', Rule::exists('companies', 'id')],
            'period_id' => ['nullable', 'integer', Rule::exists('internship_periods', 'id')],
            'status' => ['nullable', Rule::enum(InternshipStatus::class)],
        ]);

        $from = filled($data['from'] ?? null)
            ? Carbon::createFromFormat('Y-m-d', $data['from'])->startOfDay()
            : now()->startOfYear()->startOfDay();

        $to = filled($data['to'] ?? null)
            ? Carbon::createFromFormat('Y-m-d', $data['to'])->endOfDay()
            : now()->endOfYear()->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $filters = [
            'faculty_id' => $data['faculty_id'] ?? null,
            'company_id' => $data['company_id'] ?? null,
            'period_id' => $data['period_id'] ?? null,
            'status' => $data['status'] ?? null,
        ];

        return [$from, $to, $filters];
    }
}
