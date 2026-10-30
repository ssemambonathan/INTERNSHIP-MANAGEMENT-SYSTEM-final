@extends('layouts.app')

@section('title', 'My Students')
@section('page-title', 'My Students')
@section('page-subtitle', 'Monitor attendance, logbooks and risk across your placements')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="Placements" :value="$summary['total']" icon="bi-people" tone="primary" hint="Matching filters" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Average attendance" :value="$summary['average_attendance'] . '%'" icon="bi-calendar-check" tone="success" /></div>
        <div class="col-6 col-lg-3"><x-stat label="At risk" :value="$summary['at_risk']" icon="bi-exclamation-triangle" :tone="$summary['at_risk'] ? 'warning' : 'primary'" :hint="$summary['high_risk'] . ' high risk'" /></div>
        <div class="col-6 col-lg-3"><div class="stat-card h-100 d-flex align-items-center justify-content-center"><a href="{{ route('lecturer.visits.index') }}" class="text-decoration-none text-center"><i class="bi bi-pin-map fs-4 d-block text-navy mb-1"></i><span style="font-size:.8rem;" class="fw-semibold">Supervision visits</span></a></div></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="q" class="form-label">Search student</label>
                    <input id="q" type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Name or registration number">
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Placement</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All</option>
                        @foreach(['active' => 'Active', 'completed' => 'Completed', 'pending' => 'Pending', 'cancelled' => 'Cancelled'] as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="risk" class="form-label">Risk level</label>
                    <select id="risk" name="risk" class="form-select">
                        <option value="">All risk levels</option>
                        @foreach(\App\Enums\RiskLevel::cases() as $level)
                            <option value="{{ $level->value }}" @selected($riskFilter === $level->value)>{{ $level->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-primary w-100">Apply filters</button></div>
                <div class="col-md-1 text-end"><a href="{{ route('lecturer.students.index') }}" class="btn btn-link text-decoration-none px-0">Reset</a></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span>{{ $paginated->total() }} placement{{ $paginated->total() === 1 ? '' : 's' }}</span>
            <span class="text-muted-2 fw-normal" style="font-size:.74rem;">Risk uses attendance, missing logs, rejected logs, checkouts and supervisor activity</span>
        </div>

        @if($paginated->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Student</th><th>Company</th><th>Attendance</th><th>Logbook</th><th>Risk</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach($paginated as $internship)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar">{{ strtoupper(Str::substr($internship->student->user->name, 0, 1)) }}</span>
                                        <div>
                                            <div class="fw-semibold">{{ $internship->student->user->name }}</div>
                                            <div class="text-muted-2" style="font-size:.72rem;">{{ $internship->student->registration_number }} · {{ $internship->student->department?->name ?? '—' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>{{ $internship->company?->name }}</div>
                                    <div class="text-muted-2" style="font-size:.72rem;">{{ $internship->period?->name }}</div>
                                </td>
                                <td style="min-width:130px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:6px;">
                                            <div class="progress-bar {{ $internship->attendance_percentage >= 80 ? 'bg-success' : ($internship->attendance_percentage >= 60 ? 'bg-warning' : 'bg-danger') }}" style="width:{{ min(100, $internship->attendance_percentage) }}%"></div>
                                        </div>
                                        <span style="font-size:.78rem;">{{ $internship->attendance_percentage }}%</span>
                                    </div>
                                    <div class="text-muted-2" style="font-size:.7rem;">{{ $internship->attended_days }} / {{ $internship->expected_days }} days</div>
                                </td>
                                <td>
                                    @if($internship->missed_logs_count > 0)
                                        <span class="badge badge-soft-warning">{{ $internship->missed_logs_count }} missing</span>
                                    @else
                                        <span class="badge badge-soft-success">Complete</span>
                                    @endif
                                    @if($internship->pending_logs_count > 0)
                                        <span class="badge badge-soft-info">{{ $internship->pending_logs_count }} pending</span>
                                    @endif
                                </td>
                                <td>
                                    <x-status-badge :status="$internship->risk_assessment->level" />
                                    @if($internship->risk_assessment->hasReasons())
                                        <div class="text-muted-2 mt-1" style="font-size:.68rem;">{{ $internship->risk_assessment->reasons[0] }}</div>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('lecturer.students.show', $internship->student) }}" class="btn btn-sm btn-outline-primary py-0">Monitor</a>
                                    <a href="{{ route('lecturer.reports.student', $internship->student) }}" class="btn btn-sm btn-outline-secondary py-0">Report</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body border-top">{{ $paginated->links() }}</div>
        @else
            <x-empty-state icon="bi-people" title="No placements found" text="No assigned student placements match these filters." />
        @endif
    </div>
@endsection
