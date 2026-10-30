@extends('layouts.app')

@section('title', $student->name)
@section('page-title', $student->name)
@section('page-subtitle', $student->registration_number . ' · ' . ($internship->company?->name ?? ''))

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('supervisor.students.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to students</a>
        <div class="d-flex gap-2">
            <a href="{{ route('supervisor.evaluations.create', ['internship_id' => $internship->id]) }}" class="btn btn-sm btn-primary"><i class="bi bi-clipboard-data me-1"></i>Evaluate this week</a>
            <a href="{{ route('supervisor.approvals.index') }}" class="btn btn-sm btn-outline-primary">Log approvals</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="avatar avatar-lg">{{ strtoupper(Str::substr($student->user->name, 0, 1)) }}</span>
                        <div class="flex-grow-1">
                            <h4 class="h5 mb-1">{{ $student->user->name }}</h4>
                            <div class="text-muted-2" style="font-size:.82rem;">{{ $student->course?->name }} · {{ $student->department?->name }} · Year {{ $student->year_of_study }}</div>
                            <div class="text-muted-2 mt-1" style="font-size:.78rem;"><i class="bi bi-envelope me-1"></i>{{ $student->user->email }} @if($student->user->phone)<span class="mx-2">·</span><i class="bi bi-telephone me-1"></i>{{ $student->user->phone }}@endif</div>
                        </div>
                        <x-status-badge :status="$assessment->level" />
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body" style="font-size:.83rem;">
                    <div class="section-label mb-2">Placement</div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Period</span><span class="text-end">{{ $internship->period?->name }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Dates</span><span class="text-end">{{ $internship->start_date->format('d M') }} – {{ $internship->end_date->format('d M Y') }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Lecturer</span><span class="text-end">{{ $internship->lecturer?->user?->name ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Status</span><x-status-badge :status="$internship->status" /></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="Attendance" :value="$percentage . '%'" icon="bi-calendar-check" tone="success" :hint="$attendedDays . '/' . $expectedDays . ' days'" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Logs" :value="$logs->count()" icon="bi-journal-text" tone="primary" hint="Most recent 12" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Missed logs" :value="$missedDates->count()" icon="bi-journal-x" :tone="$missedDates->count() ? 'danger' : 'primary'" hint="Last 45 days" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Evaluations" :value="$evaluations->count()" icon="bi-clipboard-data" tone="primary" hint="Weekly records" /></div>
    </div>

    @if($assessment->hasReasons())
        <div class="alert alert-warning">
            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Risk indicators ({{ $assessment->level->label() }})</div>
            <ul class="mb-0 ps-3" style="font-size:.83rem;">
                @foreach($assessment->reasons as $reason)
                    <li>{{ $reason }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header">Attendance history</div>
                @if($attendances->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Date</th><th>In</th><th>Out</th><th>Distance</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach($attendances as $attendance)
                                    <tr>
                                        <td>{{ $attendance->date->format('d M Y') }}</td>
                                        <td>{{ $attendance->check_in_time?->format('H:i') ?? '—' }}</td>
                                        <td>{{ $attendance->check_out_time?->format('H:i') ?? '—' }}</td>
                                        <td>{{ $attendance->distance_from_company !== null ? number_format($attendance->distance_from_company) . ' m' : '—' }}</td>
                                        <td><x-status-badge :status="$attendance->status" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-empty-state icon="bi-calendar-x" title="No attendance" text="This student has no attendance records yet." />
                @endif
            </div>

            <div class="card">
                <div class="card-header">
                    <span>Recent daily logs</span>
                    <a href="{{ route('supervisor.approvals.index') }}" class="btn btn-sm btn-link text-decoration-none">Approvals queue</a>
                </div>
                @if($logs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Date</th><th>Activities</th><th>Evidence</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                                @foreach($logs as $log)
                                    <tr>
                                        <td>{{ $log->date->format('d M') }}</td>
                                        <td style="max-width:280px;"><div class="text-truncate" style="max-width:270px;">{{ $log->activities_performed }}</div></td>
                                        <td>{{ $log->evidence_files_count }}</td>
                                        <td><x-status-badge :status="$log->status" /></td>
                                        <td class="text-end"><a href="{{ route('supervisor.approvals.show', $log) }}" class="btn btn-sm btn-outline-secondary py-0">Open</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-empty-state icon="bi-journal" title="No logs submitted" text="Daily logs appear here after the student submits them." />
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header">Missed log days</div>
                <div class="card-body">
                    @if($missedDates->count() > 0)
                        <p class="text-muted-2" style="font-size:.8rem;">Attended days without a submitted log. Only the lecturer can reopen these dates.</p>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($missedDates->take(20) as $date)
                                <span class="badge badge-soft-warning">{{ $date->format('d M Y') }}</span>
                            @endforeach
                        </div>
                    @else
                        <div class="text-muted-2" style="font-size:.82rem;"><i class="bi bi-check-circle text-success me-1"></i>No missing logs in the last 45 days.</div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span>Weekly evaluations</span>
                    <a href="{{ route('supervisor.evaluations.create', ['internship_id' => $internship->id]) }}" class="btn btn-sm btn-outline-primary py-0">New</a>
                </div>
                @if($evaluations->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($evaluations as $evaluation)
                            <a href="{{ route('supervisor.evaluations.show', $evaluation) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold" style="font-size:.83rem;">Week of {{ $evaluation->week_start->format('d M Y') }}</div>
                                    <div class="text-muted-2" style="font-size:.73rem;">{{ $evaluation->week_end->format('d M') }} · {{ $evaluation->averageScore() }} / {{ settings('evaluation_scale', 5) }} average</div>
                                </div>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </a>
                        @endforeach
                    </div>
                @else
                    <x-empty-state icon="bi-clipboard-data" title="No evaluations" text="Record a weekly evaluation to document this student's progress." />
                @endif
            </div>
        </div>
    </div>
@endsection
