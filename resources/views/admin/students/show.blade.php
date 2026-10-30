@extends('layouts.app')

@section('title', 'Student Profile')
@section('page-title', 'Student Profile')
@section('page-subtitle', $student->user->name . ' · ' . $student->registration_number)

@section('content')
    @php
        $totalLogs = (int) $logStatusCounts->sum();
        $approvedLogs = (int) ($logStatusCounts[\App\Enums\DailyLogStatus::Approved->value] ?? 0);
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('admin.students.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to students</a>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit student</a>
            <form method="POST" action="{{ route('admin.students.toggle-status', $student) }}" data-confirm="Change this account's status?">
                @csrf
                <button class="btn btn-sm {{ $student->user->status === \App\Enums\UserStatus::Active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                    <i class="bi {{ $student->user->status === \App\Enums\UserStatus::Active ? 'bi-person-slash' : 'bi-person-check' }} me-1"></i>
                    {{ $student->user->status === \App\Enums\UserStatus::Active ? 'Suspend account' : 'Activate account' }}
                </button>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="Attendance" :value="number_format($percentage, 1).'%'" icon="bi-geo-alt" :tone="$percentage >= 80 ? 'success' : ($percentage >= 60 ? 'warning' : 'danger')" :hint="$internship?->status->label() ?? 'No placement'" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Daily logs" :value="$totalLogs" icon="bi-journal-text" tone="primary" :hint="$approvedLogs . ' approved'" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Placements" :value="$internships->count()" icon="bi-briefcase" tone="primary" :hint="$internships->where('status', \App\Enums\InternshipStatus::Active)->count() . ' active'" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Account status" :value="$student->user->status->label()" icon="bi-person-badge" :tone="$student->user->status === \App\Enums\UserStatus::Active ? 'success' : 'danger'" /></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">Student record</div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="avatar" style="width:56px;height:56px;font-size:1.3rem;">{{ strtoupper(Str::substr($student->user->name, 0, 1)) }}</span>
                        <div>
                            <div class="fw-semibold">{{ $student->user->name }}</div>
                            <div class="text-muted-2" style="font-size:.78rem;">{{ $student->user->email }}</div>
                            <x-status-badge :status="$student->user->status" class="mt-1" />
                        </div>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.82rem;"><span class="text-muted-2">Registration number</span><strong>{{ $student->registration_number }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.82rem;"><span class="text-muted-2">Student number</span><span>{{ $student->student_number ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.82rem;"><span class="text-muted-2">Faculty</span><span class="text-end">{{ $student->faculty?->name ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.82rem;"><span class="text-muted-2">Department</span><span class="text-end">{{ $student->department?->name ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.82rem;"><span class="text-muted-2">Course</span><span class="text-end">{{ $student->course?->name ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.82rem;"><span class="text-muted-2">Year of study</span><span>Year {{ $student->year_of_study }}</span></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.82rem;"><span class="text-muted-2">Academic year</span><span>{{ $student->academicYear?->name ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between py-2" style="font-size:.82rem;"><span class="text-muted-2">Phone</span><span>{{ $student->user->phone ?? '—' }}</span></div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            @if($internship)
                <div class="card mb-3">
                    <div class="card-header">
                        <span>Current placement</span>
                        <x-status-badge :status="$internship->status" />
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="section-label">Company</div>
                                <div class="fw-semibold">{{ $internship->company?->name ?? '—' }}</div>
                                <div class="text-muted-2" style="font-size:.75rem;">{{ $internship->company?->city }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="section-label">Internship period</div>
                                <div class="fw-semibold">{{ $internship->period?->name ?? '—' }}</div>
                                <div class="text-muted-2" style="font-size:.75rem;">{{ $internship->start_date->format('d M Y') }} – {{ $internship->end_date->format('d M Y') }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="section-label">Company supervisor</div>
                                <div>{{ $internship->supervisor?->name ?? '—' }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="section-label">University lecturer</div>
                                <div>{{ $internship->lecturer?->name ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('admin.internships.show', $internship) }}" class="btn btn-sm btn-outline-primary">Open placement</a>
                        </div>
                    </div>
                </div>

                @if($assessment && $assessment->hasReasons())
                    <div class="card">
                        <div class="card-header">
                            <span>Risk indicators</span>
                            <x-status-badge :status="$assessment->level" />
                        </div>
                        <div class="card-body">
                            <ul class="mb-0 ps-3" style="font-size:.82rem;">
                                @foreach($assessment->reasons as $reason)
                                    <li>{{ $reason }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @else
                    <div class="card">
                        <div class="card-header">Risk indicators</div>
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 text-success" style="font-size:.85rem;"><i class="bi bi-shield-check"></i>No risk indicators based on attendance, logs and supervisor activity.</div>
                        </div>
                    </div>
                @endif
            @else
                <div class="card h-100">
                    <x-empty-state icon="bi-briefcase" title="No placement recorded" text="Create an internship placement to start attendance and logbook tracking for this student." :action="route('admin.internships.create')" action-label="Create placement" />
                </div>
            @endif
        </div>
    </div>

    @if($internship)
        <div class="row g-3 mb-3">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header">
                        <span>Recent attendance</span>
                        <a href="{{ route('admin.attendance.index', ['q' => $student->registration_number]) }}" class="btn btn-sm btn-link text-decoration-none">All records</a>
                    </div>
                    @if($attendances->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead><tr><th>Date</th><th>In / out</th><th>Worked</th><th>Distance</th><th>Status</th></tr></thead>
                                <tbody>
                                    @foreach($attendances as $attendance)
                                        <tr>
                                            <td class="text-nowrap">{{ $attendance->date->format('d M Y') }}</td>
                                            <td>{{ $attendance->check_in_time?->format('H:i') ?? '—' }} / {{ $attendance->check_out_time?->format('H:i') ?? '—' }}</td>
                                            <td>{{ $attendance->worked_minutes !== null ? intdiv($attendance->worked_minutes, 60).'h '.($attendance->worked_minutes % 60).'m' : '—' }}</td>
                                            <td>{{ $attendance->distance_from_company !== null ? number_format($attendance->distance_from_company, 0).' m' : '—' }}</td>
                                            <td><x-status-badge :status="$attendance->status" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-empty-state icon="bi-geo-alt" title="No attendance records" text="The student has not checked in yet." />
                    @endif
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">Logbook status</div>
                    <div class="card-body">
                        @foreach(\App\Enums\DailyLogStatus::cases() as $status)
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="font-size:.82rem;">
                                <span><span class="badge badge-soft-{{ $status->badgeClass() }} me-2">{{ $status->label() }}</span></span>
                                <strong>{{ (int) ($logStatusCounts[$status->value] ?? 0) }}</strong>
                            </div>
                        @endforeach
                        @if($approvedLogs > 0)
                            <div class="mt-3" style="font-size:.78rem;">
                                <div class="d-flex justify-content-between mb-1"><span class="text-muted-2">Approved share</span><span>{{ $totalLogs > 0 ? number_format(($approvedLogs / $totalLogs) * 100, 0) : 0 }}%</span></div>
                                <div class="progress" style="height:6px;"><div class="progress-bar bg-success" style="width:{{ $totalLogs > 0 ? ($approvedLogs / $totalLogs) * 100 : 0 }}%"></div></div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header">Recent daily logs</div>
                    @if($logs->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead><tr><th>Date</th><th>Activities</th><th>Hours</th><th>Evidence</th><th>Status</th></tr></thead>
                                <tbody>
                                    @foreach($logs as $log)
                                        <tr>
                                            <td class="text-nowrap">{{ $log->date->format('d M Y') }}</td>
                                            <td style="max-width:280px;"><div class="text-truncate" style="max-width:270px;">{{ $log->activities_performed ?: '—' }}</div></td>
                                            <td>{{ $log->hours_worked }}</td>
                                            <td><span class="badge badge-soft-secondary"><i class="bi bi-paperclip me-1"></i>{{ $log->evidence_files_count }}</span></td>
                                            <td><x-status-badge :status="$log->status" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-empty-state icon="bi-journal" title="No daily logs" text="The student has not created any logbook entries for this placement." />
                    @endif
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">Weekly evaluations</div>
                    @if($evaluations->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($evaluations as $evaluation)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-semibold" style="font-size:.82rem;">Week of {{ $evaluation->week_start->format('d M Y') }}</span>
                                        <span class="badge badge-soft-primary">{{ $evaluation->averageScore() }} / {{ settings('evaluation_scale', 5) }}</span>
                                    </div>
                                    @if($evaluation->recommendation)
                                        <div class="text-muted-2 mt-1" style="font-size:.73rem;">{{ Str::limit($evaluation->recommendation, 110) }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-empty-state icon="bi-clipboard-data" title="No evaluations" text="The company supervisor has not submitted weekly evaluations yet." />
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span>Logbook date reopenings</span>
                <span class="text-muted-2 fw-normal" style="font-size:.74rem;">Historical logging is locked unless authorised</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.students.reopen', $student) }}" class="row g-2 align-items-end mb-3">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label" for="reopen_date">Date to reopen</label>
                        <input id="reopen_date" type="date" name="date" class="form-control" max="{{ now()->subDay()->toDateString() }}" value="{{ old('date') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="reason">Reason</label>
                        <input id="reason" type="text" name="reason" class="form-control" value="{{ old('reason') }}" minlength="10" maxlength="1000" placeholder="Why does the student need to submit this date?" required>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100"><i class="bi bi-unlock me-1"></i>Reopen date</button>
                    </div>
                </form>

                @if($reopenings->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Date</th><th>Reason</th><th>Reopened by</th><th>When</th></tr></thead>
                            <tbody>
                                @foreach($reopenings as $reopening)
                                    <tr>
                                        <td class="text-nowrap fw-semibold">{{ $reopening->date->format('d M Y') }}</td>
                                        <td style="max-width:360px;"><div class="text-truncate" style="max-width:350px;" title="{{ $reopening->reason }}">{{ $reopening->reason }}</div></td>
                                        <td>{{ $reopening->reopenedBy?->name ?? 'System' }}</td>
                                        <td class="text-nowrap text-muted-2" style="font-size:.75rem;">{{ $reopening->created_at?->format('d M Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted-2" style="font-size:.8rem;"><i class="bi bi-lock me-1"></i>No dates have been reopened for this placement.</div>
                @endif
            </div>
        </div>
    @endif

    @if($internships->count() > 1)
        <div class="card mt-3">
            <div class="card-header">Placement history</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Company</th><th>Period</th><th>Dates</th><th>Supervisor</th><th>Logs</th><th>Attendance</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach($internships as $placement)
                            <tr>
                                <td>{{ $placement->company?->name ?? '—' }}</td>
                                <td>{{ $placement->period?->name }}</td>
                                <td class="text-nowrap">{{ $placement->start_date->format('d M Y') }} – {{ $placement->end_date->format('d M Y') }}</td>
                                <td>{{ $placement->supervisor?->name ?? '—' }}</td>
                                <td>{{ $placement->daily_logs_count }}</td>
                                <td>{{ $placement->attendances_count }}</td>
                                <td><x-status-badge :status="$placement->status" /></td>
                                <td class="text-end"><a href="{{ route('admin.internships.show', $placement) }}" class="btn btn-sm btn-outline-secondary py-0">Open</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
