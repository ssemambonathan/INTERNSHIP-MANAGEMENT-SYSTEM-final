@extends('layouts.app')

@section('title', 'Lecturer Dashboard')
@section('page-title', 'Lecturer Dashboard')
@section('page-subtitle', $lecturer->department?->name ?? 'Internship monitoring')

@section('content')
    @php($atRiskTotal = $riskDistribution['warning'] + $riskDistribution['high_risk'])

    <div class="hero-card mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="hero-eyebrow">Academic supervision</div>
                <h2 class="h4 mb-1">Welcome, {{ Str::limit(auth()->user()->name, 32) }}</h2>
                <p class="mb-0" style="font-size:.85rem;opacity:.85;">Tracking {{ $active->count() }} active placement{{ $active->count() === 1 ? '' : 's' }} · average attendance {{ number_format($averageAttendance, 1) }}%</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('lecturer.students.index') }}" class="btn btn-light"><i class="bi bi-people me-1"></i>Monitor students</a>
                <a href="{{ route('lecturer.visits.create') }}" class="btn btn-outline-light"><i class="bi bi-pin-map me-1"></i>Schedule visit</a>
            </div>
        </div>
    </div>

    @if($atRiskTotal > 0)
        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div style="font-size:.84rem;"><i class="bi bi-exclamation-triangle me-1"></i><strong>{{ $atRiskTotal }}</strong> student{{ $atRiskTotal === 1 ? '' : 's' }} need attention based on attendance, logbook and supervisor activity.</div>
            <a href="{{ route('lecturer.students.index', ['risk' => 'high_risk']) }}" class="btn btn-sm btn-warning">Review high risk</a>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="Assigned students" :value="$internships->count()" icon="bi-people" tone="primary" :hint="$active->count() . ' active now'" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Students at risk" :value="$atRiskTotal" icon="bi-exclamation-triangle" :tone="$atRiskTotal ? 'danger' : 'success'" :hint="$riskDistribution['high_risk'] . ' high risk'" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Logs awaiting supervisor" :value="$pendingLogsCount" icon="bi-hourglass-split" tone="warning" hint="Across your students" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Upcoming visits" :value="$upcomingVisits->count()" icon="bi-pin-map" tone="primary" :hint="$completedVisits . ' completed all time'" /></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <span>Attendance trend — last 14 days</span>
                    <span class="text-muted-2 fw-normal" style="font-size:.74rem;">All active placements</span>
                </div>
                <div class="card-body">
                    <canvas data-chart height="120" data-chart='@json($trendChart)'></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">Risk distribution</div>
                <div class="card-body">
                    <canvas data-chart height="210" data-chart='@json($riskChart)'></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header">
                    <span>Students needing attention</span>
                    <a href="{{ route('lecturer.students.index') }}" class="btn btn-sm btn-link text-decoration-none">View all</a>
                </div>
                @if($atRiskStudents->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead><tr><th>Student</th><th>Attendance</th><th>Risk</th><th>Reasons</th><th></th></tr></thead>
                            <tbody>
                                @foreach($atRiskStudents as $row)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $row['internship']->student->user->name }}</div>
                                            <div class="text-muted-2" style="font-size:.72rem;">{{ $row['internship']->student->registration_number }} · {{ $row['internship']->company?->name }}</div>
                                        </td>
                                        <td>{{ number_format($row['assessment']->attendancePercentage, 1) }}%</td>
                                        <td><x-status-badge :status="$row['assessment']->level" /></td>
                                        <td style="max-width:260px;">
                                            <div class="text-truncate" style="max-width:250px;" title="{{ implode('; ', $row['assessment']->reasons) }}">{{ implode('; ', $row['assessment']->reasons) }}</div>
                                        </td>
                                        <td class="text-end"><a href="{{ route('lecturer.students.show', $row['internship']->student) }}" class="btn btn-sm btn-outline-primary py-0">Open</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-empty-state icon="bi-shield-check" title="No risk indicators" text="Attendance, logbooks and supervisor activity are within expected thresholds." />
                @endif
            </div>

            <div class="card">
                <div class="card-header">
                    <span>Logs awaiting supervisor review</span>
                    <span class="badge badge-soft-warning">{{ $pendingLogsCount }}</span>
                </div>
                @if($recentLogs->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($recentLogs as $log)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold" style="font-size:.84rem;">{{ $log->student->user->name }}</div>
                                    <div class="text-muted-2" style="font-size:.73rem;">{{ $log->date->format('d M Y') }} · submitted {{ $log->submitted_at?->diffForHumans() }} · {{ $log->internship->company?->name }}</div>
                                </div>
                                <span class="badge badge-soft-{{ $log->status->badgeClass() }}">{{ $log->status->label() }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-empty-state icon="bi-journal-check" title="No pending logs" text="All submitted daily logs have been reviewed by supervisors." />
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header">
                    <span>Upcoming supervision visits</span>
                    <a href="{{ route('lecturer.visits.create') }}" class="btn btn-sm btn-outline-primary py-0">Schedule</a>
                </div>
                @if($upcomingVisits->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($upcomingVisits->take(6) as $visit)
                            <a href="{{ route('lecturer.visits.show', $visit) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold" style="font-size:.84rem;">{{ $visit->internship->student->user->name }}</div>
                                    <div class="text-muted-2" style="font-size:.73rem;">{{ $visit->visit_date->format('d M Y') }} · {{ $visit->internship->company?->name }}</div>
                                </div>
                                <div class="text-end">
                                    <x-status-badge :status="$visit->status" />
                                    <div class="text-muted-2 mt-1" style="font-size:.68rem;">{{ $visit->visit_date->diffForHumans() }}</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <x-empty-state icon="bi-pin-map" title="No visits scheduled" text="Plan a supervision visit to verify the placement in person." :action="route('lecturer.visits.create')" action-label="Schedule a visit" />
                @endif
            </div>

            <div class="card">
                <div class="card-header">Placement snapshot</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">Active placements</span><strong>{{ $active->count() }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">Normal</span><strong class="text-success">{{ $riskDistribution['normal'] }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">Warning</span><strong class="text-warning">{{ $riskDistribution['warning'] }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">High risk</span><strong class="text-danger">{{ $riskDistribution['high_risk'] }}</strong></div>
                    <div class="d-flex justify-content-between py-2" style="font-size:.83rem;"><span class="text-muted-2">Average attendance</span><strong>{{ number_format($averageAttendance, 1) }}%</strong></div>
                </div>
            </div>
        </div>
    </div>
@endsection
