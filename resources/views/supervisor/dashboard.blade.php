@extends('layouts.app')

@section('title', 'Supervisor Dashboard')
@section('page-title', 'Supervision Dashboard')
@section('page-subtitle', $supervisor?->company?->name ?? 'Company supervision')

@section('content')
    @if(! $supervisor)
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i>Your account is not linked to a company supervisor profile. Contact the internship administrator.</div>
    @else
        <div class="hero-band mb-3">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <div class="text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.09em;color:#f0b429;">Company supervisor workspace</div>
                    <h2 class="h4 mb-1">{{ $supervisor->company?->name }}</h2>
                    <p class="mb-0 text-hero-muted" style="font-size:.84rem;">{{ $internships->count() }} active intern{{ $internships->count() === 1 ? '' : 's' }} · {{ $supervisor->position ?: 'Supervisor' }}</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="{{ route('supervisor.approvals.index') }}" class="btn btn-warning fw-semibold" style="background:#f0b429;border-color:#f0b429;color:#102a43;">
                        <i class="bi bi-check2-square me-1"></i>Review pending logs
                        @if($pendingCount > 0)<span class="badge bg-dark ms-1">{{ $pendingCount }}</span>@endif
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3"><x-stat label="Assigned students" :value="$internships->count()" icon="bi-people" tone="primary" hint="Active placements" /></div>
            <div class="col-6 col-xl-3"><x-stat label="Pending approvals" :value="$pendingCount" icon="bi-hourglass-split" :tone="$pendingCount ? 'warning' : 'success'" hint="Submitted logs" /></div>
            <div class="col-6 col-xl-3"><x-stat label="On site today" :value="$presentToday . ' / ' . $internships->count()" icon="bi-geo-alt" tone="success" hint="Present or late" /></div>
            <div class="col-6 col-xl-3"><x-stat label="Flagged attendance" :value="$flaggedCount" icon="bi-flag" :tone="$flaggedCount ? 'danger' : 'primary'" hint="Awaiting verification" /></div>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <span><i class="bi bi-inbox me-1"></i>Logs awaiting your review</span>
                        <a href="{{ route('supervisor.approvals.index') }}" class="btn btn-sm btn-link text-decoration-none">View all</a>
                    </div>
                    @if($pendingLogs->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead><tr><th>Student</th><th>Log date</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                    @foreach($pendingLogs as $log)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="avatar avatar-sm">{{ strtoupper(Str::substr($log->student->user->name, 0, 1)) }}</span>
                                                    <div>
                                                        <div class="fw-semibold">{{ $log->student->user->name }}</div>
                                                        <div class="text-muted-2" style="font-size:.72rem;">{{ $log->student->registration_number }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $log->date->format('d M Y') }}</td>
                                            <td>{{ $log->submitted_at?->diffForHumans() ?? '—' }}</td>
                                            <td><x-status-badge :status="$log->status" /></td>
                                            <td class="text-end"><a href="{{ route('supervisor.approvals.show', $log) }}" class="btn btn-sm btn-outline-primary py-0">Review</a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-empty-state icon="bi-check2-circle" title="No pending approvals" text="Submitted student logs will appear here for review." />
                    @endif
                </div>

                <div class="card mb-3">
                    <div class="card-header">Team attendance · last 14 days</div>
                    <div class="card-body">
                        <div style="height:260px;"><canvas data-chart="{{ json_encode($trendChart) }}"></canvas></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Recent student activity</div>
                    @if($recentActivity->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentActivity as $activity)
                                <a href="{{ route('supervisor.approvals.show', $activity) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                        <div class="fw-semibold text-navy" style="font-size:.83rem;">{{ $activity->student->user->name }} · {{ $activity->date->format('d M') }}</div>
                                        <div class="text-muted-2" style="font-size:.75rem;">{{ Str::limit($activity->activities_performed, 80) }}</div>
                                    </div>
                                    <x-status-badge :status="$activity->status" />
                                </a>
                            @endforeach
                        </div>
                    @else
                        <x-empty-state icon="bi-activity" title="No recent activity" text="Student logbook activity will appear here." />
                    @endif
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header">Weekly evaluation progress</div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-1" style="font-size:.8rem;">
                            <span class="text-muted-2">Week of {{ $weekStart->format('d M Y') }}</span>
                            <strong>{{ $evaluatedThisWeek }} / {{ $internships->count() }}</strong>
                        </div>
                        <div class="progress mb-2" style="height:8px;">
                            <div class="progress-bar bg-success" style="width:{{ $internships->count() ? min(100, ($evaluatedThisWeek / $internships->count()) * 100) : 0 }}%"></div>
                        </div>
                        <p class="text-muted-2 mb-2" style="font-size:.78rem;">{{ $internships->count() - $evaluatedThisWeek }} evaluation{{ ($internships->count() - $evaluatedThisWeek) === 1 ? '' : 's' }} still due for this week.</p>
                        <a href="{{ route('supervisor.evaluations.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>New evaluation</a>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">Students needing attention</div>
                    <div class="card-body">
                        @forelse($studentsAtRisk as $row)
                            <div class="border-bottom pb-2 mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('supervisor.students.show', $row['internship']->student) }}" class="fw-semibold text-navy" style="font-size:.83rem;">{{ $row['internship']->student->user->name }}</a>
                                    <x-status-badge :status="$row['assessment']->level" />
                                </div>
                                <div class="text-muted-2 mt-1" style="font-size:.74rem;">{{ Str::limit(implode(' ', array_slice($row['assessment']->reasons, 0, 2)), 120) }}</div>
                            </div>
                        @empty
                            <div class="text-muted-2" style="font-size:.82rem;"><i class="bi bi-check-circle me-1 text-success"></i>No students currently flagged as at risk on your team.</div>
                        @endforelse
                        <div class="text-muted-2 mt-2" style="font-size:.75rem;"><i class="bi bi-journal-x me-1"></i>{{ $missedLogs }} missed log day{{ $missedLogs === 1 ? '' : 's' }} in the last 7 days.</div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">Logbook status mix</div>
                    <div class="card-body">
                        <div style="height:220px;"><canvas data-chart="{{ json_encode($logChart) }}"></canvas></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Quick actions</div>
                    <div class="card-body d-grid gap-2">
                        <a href="{{ route('supervisor.attendance.index', ['status' => 'flagged']) }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-flag me-1"></i>Review flagged attendance ({{ $flaggedCount }})</a>
                        <a href="{{ route('supervisor.students.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-people me-1"></i>My students</a>
                        <a href="{{ route('supervisor.evaluations.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-clipboard-data me-1"></i>Evaluations</a>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
