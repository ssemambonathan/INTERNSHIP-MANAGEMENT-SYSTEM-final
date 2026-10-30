@php
    $internship = $report['internship'];
    $student = $report['student'];
    $scale = (int) settings('evaluation_scale', 5);
    $logApproved = $report['logCounts'][\App\Enums\DailyLogStatus::Approved->value] ?? 0;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Internship Report — {{ $student->user->name }}</title>
    <style>
        @page { margin: 28px 30px 46px; }
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #1f2937; line-height: 1.45; }
        h1, h2, h3, p { margin: 0; }
        .header { border-bottom: 3px solid #102a43; padding-bottom: 12px; margin-bottom: 16px; }
        .university { font-size: 16px; font-weight: bold; color: #102a43; letter-spacing: .4px; }
        .muted { color: #6b7280; }
        .small { font-size: 9px; }
        .title { font-size: 18px; color: #102a43; margin-top: 14px; font-weight: bold; }
        .badge { display: inline-block; padding: 2px 7px; border-radius: 8px; font-size: 8px; font-weight: bold; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-secondary { background: #e5e7eb; color: #374151; }
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .grid td { vertical-align: top; padding: 0; width: 50%; }
        .panel { border: 1px solid #d1d5db; border-radius: 6px; padding: 10px; margin-right: 8px; }
        .panel-last { margin-right: 0; margin-left: 8px; }
        .label { font-size: 8px; text-transform: uppercase; letter-spacing: .8px; color: #64748b; font-weight: bold; margin-bottom: 3px; }
        .value { font-size: 11px; font-weight: bold; color: #102a43; }
        .metrics { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px 14px; }
        .metrics td { width: 25%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px; text-align: center; }
        .metric-value { font-size: 16px; font-weight: bold; color: #102a43; }
        h2.section { font-size: 12px; color: #102a43; border-bottom: 1px solid #d1d5db; padding-bottom: 4px; margin: 16px 0 8px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data th { background: #f1f5f9; color: #102a43; font-size: 8px; text-transform: uppercase; letter-spacing: .4px; text-align: left; padding: 5px 6px; border: 1px solid #d1d5db; }
        table.data td { padding: 5px 6px; border: 1px solid #e5e7eb; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f9fafb; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .risk { border: 1px solid #f59e0b; background: #fffbeb; border-radius: 6px; padding: 9px; margin-bottom: 14px; }
        .feedback { border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px; margin-bottom: 6px; }
        .feedback .week { font-weight: bold; color: #102a43; }
        .footer { margin-top: 22px; border-top: 1px solid #d1d5db; padding-top: 8px; font-size: 8px; color: #6b7280; }
        .signature { width: 100%; margin-top: 30px; border-collapse: collapse; }
        .signature td { width: 33%; padding-top: 26px; border-top: 1px solid #9ca3af; font-size: 8px; color: #4b5563; text-align: center; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="vertical-align:top;">
                    <div class="university">{{ settings('university_name', 'University') }}</div>
                    <div class="muted small">{{ settings('university_address', '') }}</div>
                    @if(settings('university_email'))
                        <div class="muted small">{{ settings('university_email') }} @if(settings('university_phone')) · {{ settings('university_phone') }} @endif</div>
                    @endif
                </td>
                <td style="vertical-align:top; text-align:right;" class="small muted">
                    InternTrack v1.0<br>
                    Generated {{ $report['generatedAt']->format('d M Y, H:i') }}
                </td>
            </tr>
        </table>
        <div class="title">Internship Performance Report</div>
    </div>

    <table class="grid">
        <tr>
            <td>
                <div class="panel">
                    <div class="label">Student</div>
                    <div class="value">{{ $student->user->name }}</div>
                    <div class="small">{{ $student->registration_number }} · {{ $student->student_number }}</div>
                    <div class="small muted">{{ $student->course?->name }}</div>
                    <div class="small muted">{{ $student->department?->name }}</div>
                    <div class="small muted">{{ $student->faculty?->name }}</div>
                    <div class="small muted">{{ $student->academicYear?->name }} · Year {{ $student->year_of_study }}</div>
                    <div class="small muted">{{ $student->user->email }}</div>
                </div>
            </td>
            <td>
                <div class="panel panel-last">
                    <div class="label">Placement</div>
                    <div class="value">{{ $report['company']?->name }}</div>
                    <div class="small muted">{{ $report['company']?->address }}{{ $report['company']?->city ? ', '.$report['company']->city : '' }}</div>
                    <div class="small">{{ $internship->start_date->format('d M Y') }} – {{ $internship->end_date->format('d M Y') }}</div>
                    <div class="small muted">Period: {{ $report['period']?->name }}</div>
                    <div class="small muted">Supervisor: {{ $report['supervisor']?->name ?? '—' }}</div>
                    <div class="small muted">Lecturer: {{ $report['lecturer']?->name ?? '—' }}</div>
                    <div class="small muted">Status: {{ $internship->status->label() }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="metrics">
        <tr>
            <td><div class="label">Attendance</div><div class="metric-value">{{ number_format($report['attendancePercentage'], 1) }}%</div><div class="small muted">{{ $report['attendedDays'] }} of {{ $report['expectedDays'] }} days</div></td>
            <td><div class="label">Daily logs</div><div class="metric-value">{{ $report['logs']->count() }}</div><div class="small muted">{{ $logApproved }} approved</div></td>
            <td><div class="label">Hours logged</div><div class="metric-value">{{ $report['totalHours'] }}</div><div class="small muted">Across all entries</div></td>
            <td><div class="label">Evaluation</div><div class="metric-value">{{ $report['evaluationAverage'] !== null ? $report['evaluationAverage'] : '—' }}</div><div class="small muted">out of {{ $scale }}</div></td>
        </tr>
    </table>

    @if($report['includeRisk'] && $report['assessment'] && $report['assessment']->hasReasons())
        <div class="risk">
            <strong>Risk indicators — {{ $report['assessment']->level->label() }}</strong>
            <ul style="margin:4px 0 0 14px; padding:0;">
                @foreach($report['assessment']->reasons as $reason)
                    <li>{{ $reason }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <h2 class="section">1. Attendance summary</h2>
    <table class="data">
        <thead><tr><th>Status</th><th class="text-right">Records</th></tr></thead>
        <tbody>
            @foreach(\App\Enums\AttendanceStatus::cases() as $status)
                <tr><td>{{ $status->label() }}</td><td class="text-right">{{ $report['attendanceCounts'][$status->value] ?? 0 }}</td></tr>
            @endforeach
        </tbody>
    </table>

    @if($report['monthlyAttendance']->count() > 0)
        <table class="data">
            <thead><tr><th>Month</th><th class="text-right">Present</th><th class="text-right">Late</th><th class="text-right">Flagged</th><th class="text-right">Rejected</th><th class="text-right">Attended</th></tr></thead>
            <tbody>
                @foreach($report['monthlyAttendance'] as $month)
                    <tr>
                        <td>{{ $month['month'] }}</td>
                        <td class="text-right">{{ $month['present'] }}</td>
                        <td class="text-right">{{ $month['late'] }}</td>
                        <td class="text-right">{{ $month['flagged'] }}</td>
                        <td class="text-right">{{ $month['rejected'] }}</td>
                        <td class="text-right"><strong>{{ $month['attended'] }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2 class="section">2. Daily logbook</h2>
    @if($report['logs']->count() > 0)
        <table class="data">
            <thead><tr><th>Date</th><th>Activities performed</th><th class="text-right">Hours</th><th class="text-right">Files</th><th>Status</th></tr></thead>
            <tbody>
                @foreach($report['logs'] as $log)
                    <tr>
                        <td style="white-space:nowrap;">{{ $log->date->format('d M Y') }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($log->activities_performed, 220) }}</td>
                        <td class="text-right">{{ $log->hours_worked }}</td>
                        <td class="text-right">{{ $log->evidence_files_count }}</td>
                        <td>{{ $log->status->label() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">No daily logs were submitted.</p>
    @endif

    <h2 class="section">3. Weekly evaluations</h2>
    @if($report['evaluations']->count() > 0)
        <table class="data">
            <thead>
                <tr>
                    <th>Week</th>
                    @foreach(\App\Models\Evaluation::$criteria as $label)
                        <th class="text-center">{{ \Illuminate\Support\Str::limit($label, 12) }}</th>
                    @endforeach
                    <th class="text-center">Avg</th>
                    <th>Recommendation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['evaluations'] as $evaluation)
                    <tr>
                        <td style="white-space:nowrap;">{{ $evaluation->week_start->format('d M') }} – {{ $evaluation->week_end->format('d M') }}</td>
                        @foreach(array_keys(\App\Models\Evaluation::$criteria) as $criterion)
                            <td class="text-center">{{ $evaluation->{$criterion} }}</td>
                        @endforeach
                        <td class="text-center"><strong>{{ $evaluation->averageScore() }}</strong></td>
                        <td>{{ $evaluation->recommendation ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @foreach($report['evaluations'] as $evaluation)
            <div class="feedback">
                <span class="week">Week of {{ $evaluation->week_start->format('d M Y') }} — average {{ $evaluation->averageScore() }} / {{ $scale }}</span>
                @if($evaluation->strengths)<div><strong>Strengths:</strong> {{ \Illuminate\Support\Str::limit($evaluation->strengths, 400) }}</div>@endif
                @if($evaluation->areas_for_improvement)<div><strong>Areas for improvement:</strong> {{ \Illuminate\Support\Str::limit($evaluation->areas_for_improvement, 400) }}</div>@endif
                @if($evaluation->remarks)<div><strong>Remarks:</strong> {{ \Illuminate\Support\Str::limit($evaluation->remarks, 300) }}</div>@endif
            </div>
        @endforeach
    @else
        <p class="muted">No weekly evaluations were recorded.</p>
    @endif

    <h2 class="section">4. Supervision visits</h2>
    @if($report['visits']->count() > 0)
        <table class="data">
            <thead><tr><th>Date</th><th>Purpose</th><th>Status</th><th>Observations</th><th class="text-right">Files</th></tr></thead>
            <tbody>
                @foreach($report['visits'] as $visit)
                    <tr>
                        <td style="white-space:nowrap;">{{ $visit->visit_date->format('d M Y') }}</td>
                        <td>{{ $visit->purpose }}</td>
                        <td>{{ $visit->status->label() }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($visit->observations, 220) ?: '—' }}</td>
                        <td class="text-right">{{ $visit->evidence_files_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">No supervision visits were recorded.</p>
    @endif

    <table class="signature">
        <tr>
            <td>Student</td>
            <td>Company Supervisor</td>
            <td>University Lecturer</td>
        </tr>
    </table>

    <div class="footer">
        This report is generated from GPS-verified attendance, supervisor-reviewed daily logs and submitted weekly evaluations recorded in InternTrack.
        It is intended for academic supervision and quality assurance purposes.
        <br>{{ settings('university_name', 'University') }} · InternTrack v1.0 · {{ $report['generatedAt']->format('d M Y H:i') }}
    </div>
</body>
</html>
