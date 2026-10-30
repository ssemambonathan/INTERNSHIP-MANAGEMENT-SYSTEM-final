@extends('layouts.app')

@section('title', 'Placement Detail')
@section('page-title', 'Internship Placement')
@section('page-subtitle', $internship->student->user->name . ' · ' . $internship->company->name)

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('admin.internships.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to placements</a>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <x-status-badge :status="$internship->status" />
            <a href="{{ route('admin.internships.edit', $internship) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit placement</a>
            <form method="POST" action="{{ route('admin.internships.status', $internship) }}" class="d-flex gap-2">
                @csrf
                <select name="status" class="form-select form-select-sm" style="width:auto;">
                    @foreach(\App\Enums\InternshipStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected($internship->status === $case)>{{ $case->label() }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-outline-secondary">Update status</button>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="Attendance" :value="number_format($percentage, 1).'%'" icon="bi-geo-alt" :tone="$percentage >= 80 ? 'success' : ($percentage >= 60 ? 'warning' : 'danger')" :hint="$attendedDays . ' of ' . $expectedDays . ' working days'" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Daily logs" :value="(int) $logCounts->sum()" icon="bi-journal-text" tone="primary" :hint="(int) ($logCounts[\App\Enums\DailyLogStatus::Approved->value] ?? 0) . ' approved'" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Evaluations" :value="$evaluations->count()" icon="bi-clipboard-data" tone="primary" :hint="'Six-criteria weekly reviews'" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Risk level" :value="$assessment->level->label()" icon="bi-shield-exclamation" :tone="$assessment->level->value === 'high_risk' ? 'danger' : ($assessment->level->value === 'warning' ? 'warning' : 'success')" :hint="$assessment->hasReasons() ? count($assessment->reasons) . ' indicator(s)' : 'No indicators'" /></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">Placement details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="section-label">Student</div>
                            <div class="fw-semibold"><a href="{{ route('admin.students.show', $internship->student) }}" class="text-decoration-none">{{ $internship->student->user->name }}</a></div>
                            <div class="text-muted-2" style="font-size:.75rem;">{{ $internship->student->registration_number }} · {{ $internship->student->course?->name }}</div>
                            <div class="text-muted-2" style="font-size:.72rem;">{{ $internship->student->faculty?->name }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="section-label">Company</div>
                            <div class="fw-semibold"><a href="{{ route('admin.companies.show', $internship->company) }}" class="text-decoration-none">{{ $internship->company->name }}</a></div>
                            <div class="text-muted-2" style="font-size:.75rem;">{{ $internship->company->industry }} · {{ $internship->company->city }}</div>
                            <div class="text-muted-2" style="font-size:.72rem;">Geofence {{ number_format($internship->company->allowed_radius) }} m</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="section-label">Company supervisor</div>
                            <div>{{ $internship->supervisor?->name ?? 'Not assigned' }}</div>
                            <div class="text-muted-2" style="font-size:.72rem;">{{ $internship->supervisor?->user?->email }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="section-label">University lecturer</div>
                            <div>{{ $internship->lecturer?->name ?? 'Not assigned' }}</div>
                            <div class="text-muted-2" style="font-size:.72rem;">{{ $internship->lecturer?->user?->email }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="section-label">Internship period</div>
                            <div>{{ $internship->period?->name ?? '—' }}</div>
                            <div class="text-muted-2" style="font-size:.72rem;">{{ $internship->period?->academicYear?->name }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="section-label">Placement dates</div>
                            <div>{{ $internship->start_date->format('d M Y') }} – {{ $internship->end_date->format('d M Y') }}</div>
                            <div class="text-muted-2" style="font-size:.72rem;">{{ $internship->start_date->diffInDays($internship->end_date) + 1 }} calendar days</div>
                        </div>
                        @if($internship->notes)
                            <div class="col-12">
                                <div class="section-label">Notes</div>
                                <div class="text-muted-2" style="font-size:.8rem;">{{ $internship->notes }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">
                    <span>Risk indicators</span>
                    <x-status-badge :status="$assessment->level" />
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-1" style="font-size:.79rem;"><span class="text-muted-2">Last attendance</span><span>{{ $assessment->lastAttendanceDate?->format('d M Y') ?? 'None' }}</span></div>
                    <div class="d-flex justify-content-between py-1" style="font-size:.79rem;"><span class="text-muted-2">Consecutive absences</span><span>{{ $assessment->consecutiveAbsences }} working day(s)</span></div>
                    <div class="d-flex justify-content-between py-1" style="font-size:.79rem;"><span class="text-muted-2">Missed logs</span><span>{{ $assessment->missedLogs }}</span></div>
                    <div class="d-flex justify-content-between py-1" style="font-size:.79rem;"><span class="text-muted-2">Incomplete check-outs (30d)</span><span>{{ $assessment->incompleteCheckouts }}</span></div>
                    <div class="d-flex justify-content-between py-1" style="font-size:.79rem;"><span class="text-muted-2">Supervisor activity (14d)</span><span>{{ $assessment->supervisorActive ? 'Active' : 'None' }}</span></div>
                    <hr>
                    @if($assessment->hasReasons())
                        <ul class="mb-0 ps-3" style="font-size:.8rem;">
                            @foreach($assessment->reasons as $reason)
                                <li>{{ $reason }}</li>
                            @endforeach
                        </ul>
                    @else
                        <div class="d-flex align-items-center gap-2 text-success" style="font-size:.82rem;"><i class="bi bi-shield-check"></i>No risk indicators detected for this placement.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">
                    <span>Recent attendance</span>
                    <a href="{{ route('admin.attendance.index', ['q' => $internship->student->registration_number]) }}" class="btn btn-sm btn-link text-decoration-none">All records</a>
                </div>
                @if($lastAttendance->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Date</th><th>In / out</th><th>Worked</th><th>Late</th><th>Distance</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach($lastAttendance as $attendance)
                                    <tr>
                                        <td class="text-nowrap">{{ $attendance->date->format('d M Y') }}</td>
                                        <td>{{ $attendance->check_in_time?->format('H:i') ?? '—' }} / {{ $attendance->check_out_time?->format('H:i') ?? '—' }}</td>
                                        <td>{{ $attendance->worked_minutes !== null ? intdiv($attendance->worked_minutes, 60).'h '.($attendance->worked_minutes % 60).'m' : '—' }}</td>
                                        <td>{{ $attendance->late_minutes > 0 ? $attendance->late_minutes.' min' : '—' }}</td>
                                        <td>{{ $attendance->distance_from_company !== null ? number_format($attendance->distance_from_company, 0).' m' : '—' }}</td>
                                        <td><x-status-badge :status="$attendance->status" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-empty-state icon="bi-geo-alt" title="No attendance yet" text="The student has not checked in for this placement." />
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">Attendance breakdown</div>
                <div class="card-body">
                    @foreach(\App\Enums\AttendanceStatus::cases() as $status)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="font-size:.82rem;">
                            <span class="badge badge-soft-{{ $status->badgeClass() }}">{{ $status->label() }}</span>
                            <strong>{{ (int) ($attendanceCounts[$status->value] ?? 0) }}</strong>
                        </div>
                    @endforeach
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-1" style="font-size:.77rem;"><span class="text-muted-2">Attendance rate</span><span>{{ number_format($percentage, 1) }}%</span></div>
                        <div class="progress" style="height:7px;"><div class="progress-bar {{ $percentage >= 80 ? 'bg-success' : ($percentage >= 60 ? 'bg-warning' : 'bg-danger') }}" style="width:{{ min(100, $percentage) }}%"></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">Recent daily logs</div>
                @if($recentLogs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Date</th><th>Activities</th><th>Hours</th><th>Evidence</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach($recentLogs as $log)
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
                    <x-empty-state icon="bi-journal" title="No daily logs" text="The student has not created a logbook entry for this placement." />
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">Logbook status</div>
                <div class="card-body">
                    @php($totalLogs = (int) $logCounts->sum())
                    @foreach(\App\Enums\DailyLogStatus::cases() as $status)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="font-size:.82rem;">
                            <span class="badge badge-soft-{{ $status->badgeClass() }}">{{ $status->label() }}</span>
                            <strong>{{ (int) ($logCounts[$status->value] ?? 0) }}</strong>
                        </div>
                    @endforeach
                    <div class="mt-3" style="font-size:.78rem;">
                        <div class="d-flex justify-content-between mb-1"><span class="text-muted-2">Approved share</span><span>{{ $totalLogs > 0 ? number_format(((int) ($logCounts[\App\Enums\DailyLogStatus::Approved->value] ?? 0) / $totalLogs) * 100, 0) : 0 }}%</span></div>
                        <div class="progress" style="height:6px;"><div class="progress-bar bg-success" style="width:{{ $totalLogs > 0 ? ((int) ($logCounts[\App\Enums\DailyLogStatus::Approved->value] ?? 0) / $totalLogs) * 100 : 0 }}%"></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">Latest weekly evaluations</div>
                @if($evaluations->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($evaluations as $evaluation)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold" style="font-size:.82rem;">Week of {{ $evaluation->week_start->format('d M Y') }}</div>
                                        <div class="text-muted-2" style="font-size:.7rem;">{{ $evaluation->supervisor?->name }} · {{ $evaluation->supervisor?->user?->name }}</div>
                                    </div>
                                    <span class="badge badge-soft-primary">{{ number_format($evaluation->averageScore(), 2) }} / {{ settings('evaluation_scale', 5) }}</span>
                                </div>
                                @if($evaluation->strengths)
                                    <div class="text-muted-2 mt-1" style="font-size:.73rem;"><strong>Strengths:</strong> {{ Str::limit($evaluation->strengths, 100) }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-empty-state icon="bi-clipboard-data" title="No evaluations" text="The company supervisor has not submitted a weekly evaluation yet." />
                @endif
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">Supervision visits</div>
                @if($visits->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($visits as $visit)
                            <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
                                <div>
                                    <div class="fw-semibold" style="font-size:.82rem;">{{ $visit->visit_date->format('d M Y') }} · {{ Str::limit($visit->purpose, 45) }}</div>
                                    <div class="text-muted-2" style="font-size:.71rem;">{{ $visit->lecturer?->name }} · {{ $visit->status->label() }}</div>
                                </div>
                                <x-status-badge :status="$visit->status" />
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-empty-state icon="bi-car-front" title="No supervision visits" text="The assigned lecturer has not recorded a visit for this placement." />
                @endif
            </div>
        </div>
    </div>
@endsection
