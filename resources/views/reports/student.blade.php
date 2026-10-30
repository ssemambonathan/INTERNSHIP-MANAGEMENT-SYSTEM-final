@extends('layouts.app')

@section('title', 'Internship Report')
@section('page-title', 'Internship Report')
@section('page-subtitle', $report['student']->user->name . ' · ' . $report['internship']->period?->name)

@section('content')
    @php
        $internship = $report['internship'];
        $student = $report['student'];
        $scale = (int) settings('evaluation_scale', 5);
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
        <a href="{{ $backRoute }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ $downloadRoute }}" class="btn btn-sm btn-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Download PDF</a>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
        </div>
    </div>

    <div class="report-document">
        <div class="report-header">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <div class="report-university">{{ settings('university_name', 'University') }}</div>
                    <div class="text-muted-2" style="font-size:.78rem;">{{ settings('university_address', '') }}</div>
                    <h1 class="h4 mt-3 mb-1">Internship Performance Report</h1>
                    <div class="text-muted-2" style="font-size:.8rem;">Generated {{ $report['generatedAt']->format('d F Y, H:i') }}</div>
                </div>
                <span class="brand-mark">IT</span>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="report-block h-100">
                    <div class="section-label mb-2">Student</div>
                    <div class="fw-semibold fs-5">{{ $student->user->name }}</div>
                    <div style="font-size:.82rem;">{{ $student->registration_number }} · {{ $student->student_number }}</div>
                    <div class="text-muted-2" style="font-size:.8rem;">{{ $student->course?->name }}</div>
                    <div class="text-muted-2" style="font-size:.8rem;">{{ $student->department?->name }} · {{ $student->faculty?->name }}</div>
                    <div class="text-muted-2" style="font-size:.8rem;">{{ $student->academicYear?->name }} · Year {{ $student->year_of_study }}</div>
                    <div class="text-muted-2 mt-1" style="font-size:.78rem;">{{ $student->user->email }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="report-block h-100">
                    <div class="section-label mb-2">Placement</div>
                    <div class="fw-semibold fs-5">{{ $report['company']?->name }}</div>
                    <div class="text-muted-2" style="font-size:.8rem;">{{ $report['company']?->address }}, {{ $report['company']?->city }}</div>
                    <div style="font-size:.82rem;" class="mt-1">{{ $internship->start_date->format('d M Y') }} – {{ $internship->end_date->format('d M Y') }}</div>
                    <div class="text-muted-2" style="font-size:.8rem;">Period: {{ $report['period']?->name }}</div>
                    <div class="text-muted-2" style="font-size:.8rem;">Supervisor: {{ $report['supervisor']?->name ?? '—' }}</div>
                    <div class="text-muted-2" style="font-size:.8rem;">Lecturer: {{ $report['lecturer']?->name ?? '—' }}</div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3"><div class="report-metric"><div class="stat-label">Attendance</div><div class="report-metric-value">{{ number_format($report['attendancePercentage'], 1) }}%</div><div class="text-muted-2" style="font-size:.7rem;">{{ $report['attendedDays'] }} of {{ $report['expectedDays'] }} days</div></div></div>
            <div class="col-6 col-md-3"><div class="report-metric"><div class="stat-label">Daily logs</div><div class="report-metric-value">{{ $report['logs']->count() }}</div><div class="text-muted-2" style="font-size:.7rem;">{{ $report['logCounts'][\App\Enums\DailyLogStatus::Approved->value] ?? 0 }} approved</div></div></div>
            <div class="col-6 col-md-3"><div class="report-metric"><div class="stat-label">Hours logged</div><div class="report-metric-value">{{ $report['totalHours'] }}</div><div class="text-muted-2" style="font-size:.7rem;">Across all entries</div></div></div>
            <div class="col-6 col-md-3"><div class="report-metric"><div class="stat-label">Evaluation</div><div class="report-metric-value">{{ $report['evaluationAverage'] !== null ? $report['evaluationAverage'] : '—' }}</div><div class="text-muted-2" style="font-size:.7rem;">out of {{ $scale }} average</div></div></div>
        </div>

        @if($report['includeRisk'] && $report['assessment'] && $report['assessment']->hasReasons())
            <div class="report-block mb-4 border-warning">
                <div class="fw-semibold mb-2"><i class="bi bi-exclamation-triangle me-1 text-warning"></i>Risk indicators — {{ $report['assessment']->level->label() }}</div>
                <ul class="mb-0 ps-3" style="font-size:.82rem;">
                    @foreach($report['assessment']->reasons as $reason)
                        <li>{{ $reason }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="report-block mb-4">
            <h2 class="h6 mb-3">1. Attendance summary</h2>
            <div class="row g-2 mb-3" style="font-size:.8rem;">
                @foreach(\App\Enums\AttendanceStatus::cases() as $status)
                    <div class="col-6 col-md-2">
                        <div class="border rounded p-2 text-center">
                            <div class="text-muted-2" style="font-size:.7rem;">{{ $status->label() }}</div>
                            <div class="fw-semibold">{{ $report['attendanceCounts'][$status->value] ?? 0 }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($report['monthlyAttendance']->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm mb-0 report-table">
                        <thead><tr><th>Month</th><th class="text-end">Present</th><th class="text-end">Late</th><th class="text-end">Flagged</th><th class="text-end">Rejected</th><th class="text-end">Attended</th></tr></thead>
                        <tbody>
                            @foreach($report['monthlyAttendance'] as $month)
                                <tr>
                                    <td>{{ $month['month'] }}</td>
                                    <td class="text-end">{{ $month['present'] }}</td>
                                    <td class="text-end">{{ $month['late'] }}</td>
                                    <td class="text-end">{{ $month['flagged'] }}</td>
                                    <td class="text-end">{{ $month['rejected'] }}</td>
                                    <td class="text-end fw-semibold">{{ $month['attended'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="report-block mb-4">
            <h2 class="h6 mb-3">2. Daily logbook</h2>
            @if($report['logs']->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm report-table mb-0">
                        <thead><tr><th>Date</th><th>Activities</th><th class="text-end">Hours</th><th class="text-end">Evidence</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach($report['logs'] as $log)
                                <tr>
                                    <td class="text-nowrap">{{ $log->date->format('d M Y') }}</td>
                                    <td>{{ Str::limit($log->activities_performed, 140) }}</td>
                                    <td class="text-end">{{ $log->hours_worked }}</td>
                                    <td class="text-end">{{ $log->evidence_files_count }}</td>
                                    <td>{{ $log->status->label() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted-2 mb-0" style="font-size:.83rem;">No daily logs were submitted.</p>
            @endif
        </div>

        <div class="report-block mb-4">
            <h2 class="h6 mb-3">3. Weekly evaluations</h2>
            @if($report['evaluations']->count() > 0)
                <div class="table-responsive mb-3">
                    <table class="table table-sm report-table mb-0">
                        <thead>
                            <tr>
                                <th>Week</th>
                                @foreach(\App\Models\Evaluation::$criteria as $label)
                                    <th class="text-center">{{ Str::limit($label, 12) }}</th>
                                @endforeach
                                <th class="text-center">Average</th>
                                <th>Recommendation</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['evaluations'] as $evaluation)
                                <tr>
                                    <td class="text-nowrap">{{ $evaluation->week_start->format('d M') }} – {{ $evaluation->week_end->format('d M') }}</td>
                                    @foreach(array_keys(\App\Models\Evaluation::$criteria) as $criterion)
                                        <td class="text-center">{{ $evaluation->{$criterion} }}</td>
                                    @endforeach
                                    <td class="text-center fw-semibold">{{ $evaluation->averageScore() }}</td>
                                    <td>{{ $evaluation->recommendation ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @foreach($report['evaluations'] as $evaluation)
                    <div class="border rounded-3 p-3 mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold" style="font-size:.82rem;">Week of {{ $evaluation->week_start->format('d M Y') }}</span>
                            <span class="text-muted-2" style="font-size:.74rem;">Average {{ $evaluation->averageScore() }} / {{ $scale }}</span>
                        </div>
                        @if($evaluation->strengths)<div style="font-size:.78rem;"><span class="text-muted-2">Strengths:</span> {{ $evaluation->strengths }}</div>@endif
                        @if($evaluation->areas_for_improvement)<div style="font-size:.78rem;"><span class="text-muted-2">Improvement:</span> {{ $evaluation->areas_for_improvement }}</div>@endif
                        @if($evaluation->remarks)<div style="font-size:.78rem;"><span class="text-muted-2">Remarks:</span> {{ $evaluation->remarks }}</div>@endif
                    </div>
                @endforeach
            @else
                <p class="text-muted-2 mb-0" style="font-size:.83rem;">No weekly evaluations were recorded.</p>
            @endif
        </div>

        <div class="report-block">
            <h2 class="h6 mb-3">4. Supervision visits</h2>
            @if($report['visits']->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm report-table mb-0">
                        <thead><tr><th>Date</th><th>Purpose</th><th>Status</th><th>Observations</th><th class="text-end">Evidence</th></tr></thead>
                        <tbody>
                            @foreach($report['visits'] as $visit)
                                <tr>
                                    <td class="text-nowrap">{{ $visit->visit_date->format('d M Y') }}</td>
                                    <td>{{ $visit->purpose }}</td>
                                    <td>{{ $visit->status->label() }}</td>
                                    <td>{{ Str::limit($visit->observations, 110) ?: '—' }}</td>
                                    <td class="text-end">{{ $visit->evidence_files_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted-2 mb-0" style="font-size:.83rem;">No supervision visits were recorded.</p>
            @endif
        </div>

        <div class="report-footer">
            <div>This report is generated from verified attendance records, supervisor-approved logbook entries and submitted evaluations in InternTrack.</div>
            <div class="mt-1">{{ settings('university_name', 'University') }} · {{ $report['generatedAt']->format('d M Y H:i') }}</div>
        </div>
    </div>
@endsection
