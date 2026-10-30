@extends('layouts.app')

@section('title', 'University Reports')
@section('page-title', 'University Internship Report')
@section('page-subtitle', 'Placement performance, attendance and risk across the university')

@section('content')
    @php
        $exportQuery = array_filter([
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'faculty_id' => $filters['faculty_id'],
            'company_id' => $filters['company_id'],
            'period_id' => $filters['period_id'],
            'status' => $filters['status'],
        ], fn ($value) => filled($value));
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="section-label">Reporting period</div>
            <div class="fw-semibold">{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</div>
            @if($filterLabels !== [])
                <div class="d-flex flex-wrap gap-1 mt-1">
                    @foreach($filterLabels as $label => $value)
                        <span class="badge badge-soft-primary">{{ $label }}: {{ $value }}</span>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.attendance.index') }}" class="btn btn-outline-primary"><i class="bi bi-geo-alt me-1"></i>Attendance register</a>
            <a href="{{ route('admin.reports.export', $exportQuery) }}" class="btn btn-primary"><i class="bi bi-filetype-pdf me-1"></i>Export PDF</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label" for="from">From</label>
                    <input id="from" type="date" name="from" value="{{ $from->toDateString() }}" class="form-control">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="to">To</label>
                    <input id="to" type="date" name="to" value="{{ $to->toDateString() }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="faculty_id">Faculty</label>
                    <select id="faculty_id" name="faculty_id" class="form-select">
                        <option value="">All faculties</option>
                        @foreach($faculties as $faculty)
                            <option value="{{ $faculty->id }}" @selected((int) $filters['faculty_id'] === $faculty->id)>{{ $faculty->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="company_id">Company</label>
                    <select id="company_id" name="company_id" class="form-select">
                        <option value="">All companies</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected((int) $filters['company_id'] === $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="period_id">Period</label>
                    <select id="period_id" name="period_id" class="form-select">
                        <option value="">All periods</option>
                        @foreach($periods as $period)
                            <option value="{{ $period->id }}" @selected((int) $filters['period_id'] === $period->id)>{{ $period->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="status">Placement status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach(\App\Enums\InternshipStatus::cases() as $case)
                            <option value="{{ $case->value }}" @selected($filters['status'] === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary">Generate report</button>
                    <a href="{{ route('admin.reports.index') }}" class="btn btn-link text-decoration-none">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="Placements" :value="$report['summary']['internships']" icon="bi-briefcase" tone="primary" :hint="$report['summary']['students'] . ' students'" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Host companies" :value="$report['summary']['companies']" icon="bi-building" tone="primary" :hint="$report['summary']['visits'] . ' supervision visits'" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Average attendance" :value="number_format($report['summary']['averageAttendance'], 1).'%'" icon="bi-geo-alt" :tone="$report['summary']['averageAttendance'] >= 80 ? 'success' : ($report['summary']['averageAttendance'] >= 60 ? 'warning' : 'danger')" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Students at risk" :value="$report['summary']['atRisk']" icon="bi-shield-exclamation" :tone="$report['summary']['atRisk'] > 0 ? 'danger' : 'success'" :hint="$report['summary']['logsPending'] . ' logs awaiting review'" /></div>
    </div>

    @if($report['rows']->count() > 0)
        <div class="row g-3 mb-3">
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">Risk distribution</div>
                    <div class="card-body">
                        <canvas data-chart height="190" data-chart='@json($riskChart)' aria-label="Risk distribution chart" role="img"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">Average attendance by company</div>
                    <div class="card-body">
                        <canvas data-chart height="190" data-chart='@json($attendanceChart)' aria-label="Average attendance by company chart" role="img"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card h-100">
                    <div class="card-header">Logbook review</div>
                    <div class="card-body">
                        <canvas data-chart height="150" data-chart='@json($logChart)' aria-label="Logbook review chart" role="img"></canvas>
                        <div class="text-center text-muted-2 mt-2" style="font-size:.74rem;">{{ $report['summary']['logsApproved'] }} approved · {{ $report['summary']['logsPending'] }} pending</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <span>Placement outcomes ({{ $report['rows']->count() }})</span>
            <span class="text-muted-2 fw-normal" style="font-size:.72rem;">Generated {{ $report['generatedAt']->format('d M Y H:i') }}</span>
        </div>
        @if($report['rows']->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Student</th><th>Placement</th><th>Supervision</th><th>Attendance</th><th>Logbook</th><th>Evaluation</th><th>Visits</th><th>Risk</th></tr>
                    </thead>
                    <tbody>
                        @foreach($report['rows'] as $row)
                            <tr>
                                <td>
                                    <div class="fw-semibold" style="font-size:.82rem;">{{ $row['student']?->user?->name ?? 'Removed' }}</div>
                                    <div class="text-muted-2" style="font-size:.7rem;">{{ $row['student']?->registration_number }}</div>
                                    <div class="text-muted-2" style="font-size:.68rem;">{{ $row['student']?->faculty?->code }} · {{ $row['student']?->course?->code }}</div>
                                </td>
                                <td style="font-size:.78rem;">
                                    <div>{{ $row['company']?->name ?? '—' }}</div>
                                    <div class="text-muted-2" style="font-size:.68rem;">{{ Str::limit($row['internship']->period?->name, 30) }}</div>
                                    <div class="text-muted-2" style="font-size:.68rem;">{{ $row['internship']->start_date->format('d M') }} – {{ $row['internship']->end_date->format('d M Y') }}</div>
                                </td>
                                <td style="font-size:.75rem;">
                                    <div><i class="bi bi-person-badge text-muted-2 me-1"></i>{{ $row['supervisor']?->name ?? '—' }}</div>
                                    <div class="text-muted-2"><i class="bi bi-mortarboard me-1"></i>{{ $row['lecturer']?->name ?? '—' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold" style="font-size:.82rem;">{{ number_format($row['attendancePercentage'], 1) }}%</div>
                                    <div class="text-muted-2" style="font-size:.68rem;">{{ $row['attendedDays'] }} / {{ $row['expectedDays'] }} days</div>
                                    <div class="progress mt-1" style="height:4px;"><div class="progress-bar {{ $row['attendancePercentage'] >= 80 ? 'bg-success' : ($row['attendancePercentage'] >= 60 ? 'bg-warning' : 'bg-danger') }}" style="width:{{ min(100, $row['attendancePercentage']) }}%"></div></div>
                                </td>
                                <td>
                                    <div style="font-size:.8rem;">{{ $row['logsApproved'] }} approved</div>
                                    <div class="text-muted-2" style="font-size:.68rem;">{{ $row['logsPending'] }} pending · {{ $row['logsTotal'] }} total</div>
                                </td>
                                <td style="font-size:.8rem;">
                                    @if($row['evaluationAverage'] !== null)
                                        <span class="badge badge-soft-primary">{{ number_format($row['evaluationAverage'], 2) }}</span>
                                        <span class="text-muted-2" style="font-size:.68rem;">/ {{ settings('evaluation_scale', 5) }}</span>
                                    @else
                                        <span class="text-muted-2">Not evaluated</span>
                                    @endif
                                </td>
                                <td style="font-size:.8rem;">{{ $row['visits'] }}</td>
                                <td>
                                    <x-status-badge :status="$row['risk']->level" />
                                    @if($row['risk']->hasReasons())
                                        <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-muted-2" data-bs-toggle="popover" data-bs-trigger="focus" data-bs-content="{{ implode(' • ', $row['risk']->reasons) }}" title="Risk reasons" aria-label="Show risk reasons">
                                            <i class="bi bi-info-circle"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-empty-state icon="bi-graph-up" title="No placements in this range" text="No placements overlap the selected period or filters. Adjust the dates or clear filters to generate a report." />
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
            if (window.bootstrap) {
                new window.bootstrap.Popover(el);
            }
        });
    </script>
@endpush
