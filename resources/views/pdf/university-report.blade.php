@php
    $summary = $report['summary'];
    $rows = $report['rows'];
    $riskLabels = [
        'normal' => 0,
        'warning' => 0,
        'high_risk' => 0,
    ];
    foreach ($rows as $row) {
        $riskLabels[$row['risk']->level->value]++;
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>University Internship Report</title>
    <style>
        @page { margin: 24px 26px 40px; }
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 8px; color: #1f2937; line-height: 1.4; }
        h1, h2, p { margin: 0; }
        .header { border-bottom: 3px solid #102a43; padding-bottom: 10px; margin-bottom: 12px; }
        .university { font-size: 15px; font-weight: bold; color: #102a43; letter-spacing: .4px; }
        .muted { color: #64748b; }
        .small { font-size: 7.5px; }
        .title { font-size: 16px; color: #102a43; margin-top: 10px; font-weight: bold; }
        .filters { margin: 8px 0 12px; }
        .tag { display: inline-block; padding: 2px 7px; border-radius: 8px; background: #e8eef6; color: #102a43; font-size: 7px; font-weight: bold; margin-right: 4px; }
        .metrics { width: 100%; border-collapse: separate; border-spacing: 5px 0; margin: 0 -5px 12px; }
        .metrics td { width: 12.5%; border: 1px solid #d1d5db; border-radius: 5px; padding: 7px 6px; text-align: center; vertical-align: top; }
        .metric-value { font-size: 14px; font-weight: bold; color: #102a43; }
        .label { font-size: 6.8px; text-transform: uppercase; letter-spacing: .6px; color: #64748b; font-weight: bold; margin-bottom: 2px; }
        h2.section { font-size: 10px; color: #102a43; border-bottom: 1px solid #d1d5db; padding-bottom: 3px; margin: 14px 0 7px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #102a43; color: #fff; font-size: 6.8px; text-transform: uppercase; letter-spacing: .3px; text-align: left; padding: 5px 4px; border: 1px solid #102a43; }
        table.data td { padding: 4px; border: 1px solid #e2e8f0; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        .nowrap { white-space: nowrap; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .risk-normal { color: #166534; font-weight: bold; }
        .risk-warning { color: #92400e; font-weight: bold; }
        .risk-high_risk { color: #991b1b; font-weight: bold; }
        .risk-box { border: 1px solid #e2e8f0; border-radius: 5px; padding: 7px 9px; margin-top: 8px; }
        .risk-box ul { margin: 3px 0 0 12px; padding: 0; }
        .footer { margin-top: 16px; border-top: 1px solid #d1d5db; padding-top: 7px; font-size: 7px; color: #64748b; }
        .signature { width: 100%; margin-top: 26px; border-collapse: collapse; }
        .signature td { width: 33%; padding-top: 22px; border-top: 1px solid #9ca3af; font-size: 7.5px; color: #4b5563; text-align: center; }
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
                        <div class="muted small">{{ settings('university_email') }}@if(settings('university_phone')) · {{ settings('university_phone') }}@endif</div>
                    @endif
                </td>
                <td style="vertical-align:top; text-align:right;" class="small muted">
                    InternTrack v1.0<br>
                    Generated {{ $report['generatedAt']->format('d M Y, H:i') }}
                </td>
            </tr>
        </table>
        <div class="title">University Internship Monitoring Report</div>
    </div>

    <div class="small">
        <strong>Reporting period:</strong> {{ $report['from']->format('d M Y') }} – {{ $report['to']->format('d M Y') }}
    </div>
    <div class="filters">
        @if($filterLabels === [])
            <span class="tag">All placements overlapping the period</span>
        @else
            @foreach($filterLabels as $label => $value)
                <span class="tag">{{ $label }}: {{ $value }}</span>
            @endforeach
        @endif
    </div>

    <table class="metrics">
        <tr>
            <td><div class="label">Placements</div><div class="metric-value">{{ $summary['internships'] }}</div></td>
            <td><div class="label">Students</div><div class="metric-value">{{ $summary['students'] }}</div></td>
            <td><div class="label">Companies</div><div class="metric-value">{{ $summary['companies'] }}</div></td>
            <td><div class="label">Avg attendance</div><div class="metric-value">{{ number_format($summary['averageAttendance'], 1) }}%</div></td>
            <td><div class="label">At risk</div><div class="metric-value">{{ $summary['atRisk'] }}</div></td>
            <td><div class="label">Logs approved</div><div class="metric-value">{{ $summary['logsApproved'] }}</div></td>
            <td><div class="label">Logs pending</div><div class="metric-value">{{ $summary['logsPending'] }}</div></td>
            <td><div class="label">Visits</div><div class="metric-value">{{ $summary['visits'] }}</div></td>
        </tr>
    </table>

    <h2 class="section">Placement outcomes</h2>

    @if($rows->count() > 0)
        <table class="data">
            <thead>
                <tr>
                    <th style="width:3%;">#</th>
                    <th style="width:15%;">Student</th>
                    <th style="width:14%;">Placement</th>
                    <th style="width:12%;">Supervision</th>
                    <th style="width:8%;">Attendance</th>
                    <th style="width:9%;">Logbook</th>
                    <th style="width:8%;">Evaluation</th>
                    <th style="width:5%;">Visits</th>
                    <th style="width:9%;">Risk</th>
                    <th style="width:17%;">Risk reasons</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $index => $row)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $row['student']?->user?->name ?? 'Removed' }}</strong><br>
                            <span class="muted">{{ $row['student']?->registration_number }}</span><br>
                            <span class="muted">{{ $row['student']?->faculty?->code }}@if($row['student']?->course) · {{ $row['student']->course->code }}@endif</span>
                        </td>
                        <td>
                            <strong>{{ $row['company']?->name ?? '—' }}</strong><br>
                            <span class="muted">{{ $row['internship']->period?->name }}</span><br>
                            <span class="muted">{{ $row['internship']->start_date->format('d M Y') }} – {{ $row['internship']->end_date->format('d M Y') }}</span>
                        </td>
                        <td>
                            {{ $row['supervisor']?->name ?? '—' }}<br>
                            <span class="muted">{{ $row['lecturer']?->name ?? '—' }}</span>
                        </td>
                        <td>
                            <strong>{{ number_format($row['attendancePercentage'], 1) }}%</strong><br>
                            <span class="muted">{{ $row['attendedDays'] }} / {{ $row['expectedDays'] }} days</span>
                        </td>
                        <td>
                            {{ $row['logsApproved'] }} approved<br>
                            <span class="muted">{{ $row['logsPending'] }} pending</span><br>
                            <span class="muted">{{ $row['logsTotal'] }} total</span>
                        </td>
                        <td>
                            @if($row['evaluationAverage'] !== null)
                                {{ number_format($row['evaluationAverage'], 2) }} / {{ settings('evaluation_scale', 5) }}
                            @else
                                <span class="muted">Not evaluated</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $row['visits'] }}</td>
                        <td><span class="risk-{{ $row['risk']->level->value }}">{{ $row['risk']->level->label() }}</span></td>
                        <td>
                            @if($row['risk']->hasReasons())
                                <ul style="margin:0 0 0 10px; padding:0;">
                                    @foreach($row['risk']->reasons as $reason)
                                        <li>{{ $reason }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="muted">No indicators</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="risk-box">
            <strong>Risk summary:</strong>
            Normal {{ $riskLabels['normal'] }} ·
            Warning {{ $riskLabels['warning'] }} ·
            High risk {{ $riskLabels['high_risk'] }}.
            Risk indicators are derived from consecutive absences, missed daily logs, rejected logs, supervisor inactivity, low attendance and incomplete check-outs.
        </div>
    @else
        <p class="muted">No placements matched the selected reporting period and filters.</p>
    @endif

    <table class="signature">
        <tr>
            <td>Director of Academic Affairs</td>
            <td>Internship Coordinator</td>
            <td>Date</td>
        </tr>
    </table>

    <div class="footer">
        This report is generated from GPS-verified attendance, supervisor-reviewed daily logs, weekly evaluations and lecturer supervision visits recorded in InternTrack.
        Figures cover placements that overlap the reporting period; attendance is calculated against expected working days up to the report end date.
        <br>{{ settings('university_name', 'University') }} · InternTrack v1.0 · {{ $report['generatedAt']->format('d M Y H:i') }}
    </div>
</body>
</html>
