@extends('layouts.app')

@section('title', $student->user->name)
@section('page-title', $student->user->name)
@section('page-subtitle', $student->registration_number . ' · ' . ($internship->company?->name ?? 'No company'))

@section('content')
    @php
        $approvedLogs = (int) ($logCounts[\App\Enums\DailyLogStatus::Approved->value] ?? 0);
        $pendingLogs = (int) ($logCounts[\App\Enums\DailyLogStatus::Submitted->value] ?? 0) + (int) ($logCounts[\App\Enums\DailyLogStatus::UnderReview->value] ?? 0);
        $evaluationAverage = $evaluations->isEmpty() ? null : round($evaluations->avg(fn ($evaluation) => $evaluation->averageScore()), 2);
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('lecturer.students.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to students</a>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('lecturer.visits.create', ['internship_id' => $internship->id]) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pin-map me-1"></i>Schedule visit</a>
            <a href="{{ route('lecturer.reports.student', $student) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-text me-1"></i>Report preview</a>
            <a href="{{ route('lecturer.reports.student.download', $student) }}" class="btn btn-sm btn-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Download PDF</a>
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
                            <div class="text-muted-2" style="font-size:.82rem;">{{ $student->registration_number }} · {{ $student->course?->name }} · {{ $student->department?->name }}</div>
                            <div class="text-muted-2 mt-1" style="font-size:.78rem;"><i class="bi bi-envelope me-1"></i>{{ $student->user->email }} @if($student->user->phone)<span class="mx-2">·</span><i class="bi bi-telephone me-1"></i>{{ $student->user->phone }}@endif</div>
                        </div>
                        <x-status-badge :status="$assessment->level" />
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body" style="font-size:.82rem;">
                    <div class="section-label mb-2">Placement</div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Period</span><span class="text-end">{{ $internship->period?->name }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Dates</span><span class="text-end">{{ $internship->start_date->format('d M Y') }} – {{ $internship->end_date->format('d M Y') }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Supervisor</span><span class="text-end">{{ $internship->supervisor?->name ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Status</span><x-status-badge :status="$internship->status" /></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="Attendance" :value="$percentage . '%'" icon="bi-calendar-check" :tone="$percentage >= 70 ? 'success' : 'danger'" :hint="$attendedDays . ' / ' . $expectedDays . ' expected days'" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Approved logs" :value="$approvedLogs" icon="bi-journal-check" tone="success" :hint="$pendingLogs . ' awaiting supervisor'" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Evaluation average" :value="$evaluationAverage !== null ? $evaluationAverage . ' / ' . settings('evaluation_scale', 5) : '—'" icon="bi-clipboard-data" tone="primary" :hint="$evaluations->count() . ' weekly records'" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Visits" :value="$visits->count()" icon="bi-pin-map" tone="primary" :hint="$visits->where('status', \App\Enums\VisitStatus::Completed)->count() . ' completed'" /></div>
    </div>

    @if($assessment->hasReasons())
        <div class="alert alert-warning">
            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Risk assessment — {{ $assessment->level->label() }}</div>
            <ul class="mb-0 ps-3" style="font-size:.83rem;">
                @foreach($assessment->reasons as $reason)
                    <li>{{ $reason }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($missedDates->count() > 0)
        <div class="alert alert-info">
            <div class="fw-semibold mb-1"><i class="bi bi-journal-x me-1"></i>{{ $missedDates->count() }} attended day{{ $missedDates->count() === 1 ? '' : 's' }} without a daily log</div>
            <div class="d-flex flex-wrap gap-1 mt-1">
                @foreach($missedDates->take(14) as $date)
                    <span class="badge badge-soft-warning">{{ $date->format('d M Y') }}</span>
                @endforeach
                @if($missedDates->count() > 14)
                    <span class="badge badge-soft-secondary">+{{ $missedDates->count() - 14 }} more</span>
                @endif
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">
                    <span>Recent attendance</span>
                    <span class="text-muted-2 fw-normal" style="font-size:.74rem;">Latest 20 records · last attendance {{ $assessment->lastAttendanceDate?->format('d M Y') ?? 'none' }}</span>
                </div>
                @if($attendances->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead><tr><th>Date</th><th>In / Out</th><th>Worked</th><th>Distance</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach($attendances as $attendance)
                                    <tr>
                                        <td>{{ $attendance->date->format('d M Y') }}</td>
                                        <td>{{ $attendance->check_in_time?->format('H:i') ?? '—' }} – {{ $attendance->check_out_time?->format('H:i') ?? '—' }}</td>
                                        <td>{{ $attendance->worked_minutes !== null ? intdiv($attendance->worked_minutes, 60).'h '.($attendance->worked_minutes % 60).'m' : '—' }}</td>
                                        <td>{{ $attendance->distance_from_company !== null ? number_format($attendance->distance_from_company).' m' : '—' }}</td>
                                        <td>
                                            <x-status-badge :status="$attendance->status" />
                                            @if($attendance->reviewer)
                                                <div class="text-muted-2" style="font-size:.68rem;">{{ $attendance->reviewed_at?->format('d M') }} · {{ $attendance->reviewer->name }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-empty-state icon="bi-calendar-x" title="No attendance" text="This student has no attendance records for the placement." />
                @endif
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <span>Recent daily logs</span>
                    <span class="text-muted-2 fw-normal" style="font-size:.74rem;">Latest 20 entries</span>
                </div>
                @if($logs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead><tr><th>Date</th><th>Activities</th><th>Hours</th><th>Evidence</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach($logs as $log)
                                    <tr>
                                        <td>{{ $log->date->format('d M') }}</td>
                                        <td style="max-width:260px;"><div class="text-truncate" style="max-width:250px;" title="{{ $log->activities_performed }}">{{ $log->activities_performed }}</div></td>
                                        <td>{{ $log->hours_worked }}</td>
                                        <td>{{ $log->evidence_files_count }}</td>
                                        <td><x-status-badge :status="$log->status" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-empty-state icon="bi-journal" title="No daily logs" text="The student has not submitted any daily logs." />
                @endif
            </div>

            <div class="card mb-3">
                <div class="card-header">Weekly evaluations</div>
                @if($evaluations->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead><tr><th>Week</th><th>Average</th><th>Recommendation</th><th>Strengths / improvement</th></tr></thead>
                            <tbody>
                                @foreach($evaluations as $evaluation)
                                    <tr>
                                        <td class="text-nowrap">{{ $evaluation->week_start->format('d M') }} – {{ $evaluation->week_end->format('d M Y') }}</td>
                                        <td><span class="fw-semibold">{{ $evaluation->averageScore() }}</span><span class="text-muted-2"> / {{ settings('evaluation_scale', 5) }}</span></td>
                                        <td>{{ $evaluation->recommendation ?: '—' }}</td>
                                        <td style="max-width:320px;">
                                            @if($evaluation->strengths)<div style="font-size:.76rem;"><span class="text-muted-2">Strengths:</span> {{ Str::limit($evaluation->strengths, 90) }}</div>@endif
                                            @if($evaluation->areas_for_improvement)<div style="font-size:.76rem;"><span class="text-muted-2">Improve:</span> {{ Str::limit($evaluation->areas_for_improvement, 90) }}</div>@endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-empty-state icon="bi-clipboard-data" title="No evaluations" text="The company supervisor has not recorded a weekly evaluation yet." />
                @endif
            </div>

            <div class="card">
                <div class="card-header">Supervision visits</div>
                @if($visits->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($visits as $visit)
                            <a href="{{ route('lecturer.visits.show', $visit) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold" style="font-size:.84rem;">{{ $visit->visit_date->format('d M Y') }} · {{ $visit->purpose }}</div>
                                    <div class="text-muted-2" style="font-size:.73rem;">{{ $visit->evidence_files_count }} evidence file{{ $visit->evidence_files_count === 1 ? '' : 's' }}{{ $visit->completed_at ? ' · completed '.$visit->completed_at->format('d M Y') : '' }}</div>
                                </div>
                                <x-status-badge :status="$visit->status" />
                            </a>
                        @endforeach
                    </div>
                @else
                    <x-empty-state icon="bi-pin-map" title="No visits" text="No supervision visits have been scheduled for this student." :action="route('lecturer.visits.create', ['internship_id' => $internship->id])" action-label="Schedule visit" />
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Reopen a missed log date</div>
                <div class="card-body">
                    <p class="text-muted-2" style="font-size:.8rem;">Reopening lets the student submit a log for a past attended day. Every reopening is audited and the student is notified.</p>
                    @if($eligibleDates->isEmpty())
                        <div class="alert alert-light border mb-0" style="font-size:.8rem;"><i class="bi bi-info-circle me-1"></i>No past attended dates are currently eligible for reopening.</div>
                    @else
                        <form method="POST" action="{{ route('lecturer.students.reopen', $internship) }}">
                            @csrf
                            <label for="reopen_date" class="form-label">Date</label>
                            <select id="reopen_date" name="date" class="form-select mb-2" required>
                                <option value="">Select a date</option>
                                @foreach($eligibleDates as $date)
                                    <option value="{{ $date->format('Y-m-d') }}" @selected(old('date') === $date->format('Y-m-d'))>{{ $date->format('D, d M Y') }}</option>
                                @endforeach
                            </select>
                            <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                            <textarea id="reason" name="reason" rows="3" class="form-control mb-2" minlength="10" maxlength="1000" required placeholder="Why should this date be reopened? (at least 10 characters)">{{ old('reason') }}</textarea>
                            <button class="btn btn-primary w-100" data-confirm="Reopen this date for log submission? This action is audited."><i class="bi bi-unlock me-1"></i>Reopen date</button>
                        </form>
                    @endif
                </div>
            </div>

            @if($reopenings->count() > 0)
                <div class="card mb-3">
                    <div class="card-header">Reopening history</div>
                    <div class="list-group list-group-flush">
                        @foreach($reopenings as $reopening)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-semibold" style="font-size:.82rem;">{{ $reopening->date->format('d M Y') }}</span>
                                    <span class="text-muted-2" style="font-size:.7rem;">{{ $reopening->created_at->format('d M Y') }}</span>
                                </div>
                                <div class="text-muted-2" style="font-size:.74rem;">{{ $reopening->reason }}</div>
                                <div class="text-muted-2" style="font-size:.68rem;">By {{ $reopening->reopenedBy?->name ?? 'Staff' }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="card mb-3">
                <div class="card-header">Logbook summary</div>
                <div class="card-body" style="font-size:.82rem;">
                    @foreach(\App\Enums\DailyLogStatus::cases() as $case)
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">{{ $case->label() }}</span><strong>{{ $logCounts[$case->value] ?? 0 }}</strong></div>
                    @endforeach
                    <hr class="my-2">
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Total logs</span><strong>{{ $logCounts->sum() }}</strong></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Company supervisor</div>
                <div class="card-body" style="font-size:.82rem;">
                    @if($internship->supervisor)
                        <div class="fw-semibold">{{ $internship->supervisor->name }}</div>
                        <div class="text-muted-2">{{ $internship->supervisor->position ?? 'Supervisor' }}</div>
                        <div class="text-muted-2 mt-1"><i class="bi bi-envelope me-1"></i>{{ $internship->supervisor->user?->email }}</div>
                        <div class="text-muted-2"><i class="bi bi-telephone me-1"></i>{{ $internship->supervisor->user?->phone ?? 'No phone' }}</div>
                    @else
                        <p class="text-muted-2 mb-0">No company supervisor assigned.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
