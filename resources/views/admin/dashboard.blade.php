@extends('layouts.app')

@section('title', 'Administrator Dashboard')
@section('page-title', 'Administrator Dashboard')
@section('page-subtitle', 'University-wide internship operations and oversight')

@section('content')
    @php
        $atRisk = $riskDistribution['warning'] + $riskDistribution['high_risk'];
        $presentToday = (int) ($todayCounts[\App\Enums\AttendanceStatus::Present->value] ?? 0);
        $lateToday = (int) ($todayCounts[\App\Enums\AttendanceStatus::Late->value] ?? 0);
        $flaggedToday = (int) ($todayCounts[\App\Enums\AttendanceStatus::OutsideLocation->value] ?? 0) + (int) ($todayCounts[\App\Enums\AttendanceStatus::Incomplete->value] ?? 0);
    @endphp

    <div class="hero-card mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="hero-eyebrow">System administration</div>
                <h2 class="h4 mb-1">Welcome, {{ Str::limit(auth()->user()->name, 32) }}</h2>
                <p class="mb-0" style="font-size:.85rem;opacity:.85;">
                    {{ $activeInternships->count() }} active placement{{ $activeInternships->count() === 1 ? '' : 's' }}
                    · {{ $studentsCount }} student{{ $studentsCount === 1 ? '' : 's' }}
                    · {{ number_format($todayRate, 1) }}% attendance today
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.students.create') }}" class="btn btn-light"><i class="bi bi-person-plus me-1"></i>Add student</a>
                <a href="{{ route('admin.internships.create') }}" class="btn btn-outline-light"><i class="bi bi-briefcase me-1"></i>New placement</a>
            </div>
        </div>
    </div>

    @if($flaggedAttendance > 0 || $pendingLogs > 0)
        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div style="font-size:.84rem;">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <strong>{{ $flaggedAttendance }}</strong> attendance record{{ $flaggedAttendance === 1 ? '' : 's' }} need review
                and <strong>{{ $pendingLogs }}</strong> log{{ $pendingLogs === 1 ? '' : 's' }} await supervisor decisions.
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.attendance.index', ['status' => 'outside_location']) }}" class="btn btn-sm btn-warning">Attendance issues</a>
                <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-dark">Open reports</a>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3"><x-stat label="Active placements" :value="$activeInternships->count()" icon="bi-briefcase" tone="primary" :hint="$atRisk . ' flagged at risk'" /></div>
        <div class="col-6 col-xl-3"><x-stat label="Students" :value="$studentsCount" icon="bi-mortarboard" tone="primary" :hint="$inactiveUsers . ' inactive/suspended users'" /></div>
        <div class="col-6 col-xl-3"><x-stat label="Attendance today" :value="$todayAttended.'/'.$todayTotal" icon="bi-geo-alt" :tone="$todayTotal && $todayRate >= 80 ? 'success' : 'warning'" :hint="number_format($todayRate, 1).'% checked in'" /></div>
        <div class="col-6 col-xl-3"><x-stat label="Logs awaiting review" :value="$pendingLogs" icon="bi-hourglass-split" :tone="$pendingLogs ? 'warning' : 'success'" hint="Submitted or in review" /></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <span>Attendance trend — last 14 days</span>
                    <span class="text-muted-2 fw-normal" style="font-size:.74rem;">Active placements only</span>
                </div>
                <div class="card-body">
                    <canvas data-chart height="120" data-chart='@json($trendChart)'></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">Logbook status mix</div>
                <div class="card-body">
                    <canvas data-chart height="205" data-chart='@json($logChart)'></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">
                    <span>Active placements</span>
                    <a href="{{ route('admin.internships.index', ['status' => 'active']) }}" class="btn btn-sm btn-link text-decoration-none">View all</a>
                </div>
                @if($activeInternships->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Student</th><th>Company</th><th>Period</th><th></th></tr></thead>
                            <tbody>
                                @foreach($activeInternships->take(7) as $internship)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $internship->student->user->name }}</div>
                                            <div class="text-muted-2" style="font-size:.72rem;">{{ $internship->student->registration_number }}</div>
                                        </td>
                                        <td>{{ $internship->company?->name }}</td>
                                        <td class="text-nowrap">{{ $internship->period?->name }}</td>
                                        <td class="text-end"><a href="{{ route('admin.internships.show', $internship) }}" class="btn btn-sm btn-outline-primary py-0">Open</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($activeInternships->count() > 7)
                        <div class="card-body border-top py-2 text-muted-2" style="font-size:.75rem;">Showing 7 of {{ $activeInternships->count() }} active placements.</div>
                    @endif
                @else
                    <x-empty-state icon="bi-briefcase" title="No active placements" text="Create a placement or activate a pending one to begin monitoring." :action="route('admin.internships.create')" action-label="Create placement" />
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header">Today at a glance</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">Present</span><strong class="text-success">{{ $presentToday }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">Late</span><strong class="text-warning">{{ $lateToday }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">Flagged (location / incomplete)</span><strong class="text-danger">{{ $flaggedToday }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">Rejected</span><strong>{{ (int) ($todayCounts[\App\Enums\AttendanceStatus::Rejected->value] ?? 0) }}</strong></div>
                    <div class="d-flex justify-content-between py-2" style="font-size:.83rem;"><span class="text-muted-2">Implementation risk</span><strong>{{ $atRisk }} student{{ $atRisk === 1 ? '' : 's' }}</strong></div>
                    <div class="d-flex gap-3 mt-3" style="font-size:.78rem;">
                        <span><span class="badge badge-soft-success me-1">{{ $riskDistribution['normal'] }}</span> normal</span>
                        <span><span class="badge badge-soft-warning me-1">{{ $riskDistribution['warning'] }}</span> warning</span>
                        <span><span class="badge badge-soft-danger me-1">{{ $riskDistribution['high_risk'] }}</span> high risk</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Administrative actions</div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ route('admin.students.import.form') }}" class="btn btn-outline-primary text-start"><i class="bi bi-filetype-csv me-2"></i>Import students from CSV</a>
                    <a href="{{ route('admin.companies.create') }}" class="btn btn-outline-primary text-start"><i class="bi bi-building-add me-2"></i>Register a company</a>
                    <a href="{{ route('admin.periods.create') }}" class="btn btn-outline-primary text-start"><i class="bi bi-calendar-plus me-2"></i>Create an internship period</a>
                    <a href="{{ route('admin.settings.edit') }}" class="btn btn-outline-secondary text-start"><i class="bi bi-sliders me-2"></i>Review system settings</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <span>Upcoming visits</span>
                    <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-link text-decoration-none">Reports</a>
                </div>
                @if($upcomingVisits->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($upcomingVisits as $visit)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="fw-semibold" style="font-size:.83rem;">{{ $visit->internship->student->user->name }}</div>
                                        <div class="text-muted-2" style="font-size:.72rem;">{{ $visit->internship->company?->name }}</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-semibold text-navy" style="font-size:.75rem;">{{ $visit->visit_date->format('d M') }}</div>
                                        <div class="text-muted-2" style="font-size:.68rem;">{{ $visit->visit_date->diffForHumans() }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-empty-state icon="bi-pin-map" title="No upcoming visits" text="Lecturers have not scheduled any future supervision visits." />
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <span>Latest log submissions</span>
                    <span class="badge badge-soft-warning">{{ $pendingLogs }}</span>
                </div>
                @if($recentSubmissions->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($recentSubmissions as $log)
                            <div class="list-group-item">
                                <div class="fw-semibold" style="font-size:.83rem;">{{ $log->student->user->name }}</div>
                                <div class="text-muted-2" style="font-size:.72rem;">{{ $log->date->format('d M Y') }} · {{ $log->internship->company?->name }}</div>
                                <div class="text-muted-2 mt-1" style="font-size:.68rem;">Submitted {{ $log->submitted_at?->diffForHumans() ?? '—' }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-empty-state icon="bi-journal-check" title="No submissions waiting" text="All daily logs have been reviewed by company supervisors." />
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <span>Recent audit activity</span>
                    <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-sm btn-link text-decoration-none">Audit trail</a>
                </div>
                @if($recentAudit->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($recentAudit as $entry)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between gap-2">
                                    <span class="badge badge-soft-secondary">{{ $entry->action }}</span>
                                    <span class="text-muted-2" style="font-size:.68rem;">{{ $entry->created_at?->diffForHumans() }}</span>
                                </div>
                                <div class="mt-2" style="font-size:.76rem;">{{ Str::limit($entry->description, 110) }}</div>
                                <div class="text-muted-2 mt-1" style="font-size:.68rem;">{{ $entry->user?->name ?? 'System' }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-empty-state icon="bi-shield-check" title="No audit entries" text="Administrative and workflow actions will appear here." />
                @endif
            </div>
        </div>
    </div>
@endsection
