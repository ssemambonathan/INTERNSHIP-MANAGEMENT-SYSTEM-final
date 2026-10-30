@extends('layouts.app')

@section('title', 'Review Daily Log')
@section('page-title', 'Review Daily Log')
@section('page-subtitle', $log->student->user->name . ' · ' . $log->date->format('l, d F Y'))

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('supervisor.approvals.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to approvals</a>
        <x-status-badge :status="$log->status" />
    </div>

    @if($log->status === \App\Enums\DailyLogStatus::Submitted)
        <div class="attendance-banner info mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="bi bi-inbox me-1"></i>This log was submitted {{ $log->submitted_at?->diffForHumans() }} and is waiting for you to begin the review.</span>
                <form method="POST" action="{{ route('supervisor.approvals.review', $log) }}">
                    @csrf
                    <button class="btn btn-sm btn-primary"><i class="bi bi-play-circle me-1"></i>Start review</button>
                </form>
            </div>
        </div>
    @elseif($log->status === \App\Enums\DailyLogStatus::UnderReview)
        <div class="attendance-banner warning mb-3"><i class="bi bi-hourglass-split me-1"></i>This log is in review. Approve it or return it with a clear reason.</div>
    @elseif($log->status === \App\Enums\DailyLogStatus::Approved)
        <div class="attendance-banner success mb-3"><i class="bi bi-check-circle me-1"></i>Approved{{ $log->reviewer ? ' by '.$log->reviewer->name : '' }} on {{ $log->reviewed_at?->format('d M Y, H:i') }}. Approved logs cannot be edited.</div>
    @elseif($log->status === \App\Enums\DailyLogStatus::Rejected)
        <div class="attendance-banner danger mb-3"><i class="bi bi-arrow-repeat me-1"></i>This log was returned to the student{{ $log->reviewer ? ' by '.$log->reviewer->name : '' }}. They can revise and resubmit.</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">
                    <span>Student entry</span>
                    <span class="text-muted-2 fw-normal" style="font-size:.75rem;">{{ $log->hours_worked }} hours recorded</span>
                </div>
                <div class="card-body">
                    @foreach([
                        'activities_performed' => ['Activities performed', 'bi-list-task'],
                        'skills_learned' => ['Skills learned', 'bi-lightbulb'],
                        'challenges' => ['Challenges', 'bi-exclamation-diamond'],
                        'solutions' => ['Solutions', 'bi-tools'],
                        'daily_reflection' => ['Daily reflection', 'bi-journal-richtext'],
                    ] as $field => [$label, $icon])
                        <div class="mb-3">
                            <div class="section-label mb-1"><i class="bi {{ $icon }} me-1"></i>{{ $label }}</div>
                            <div style="font-size:.87rem;white-space:pre-line;">{{ $log->{$field} ?: '—' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <span><i class="bi bi-paperclip me-1"></i>Evidence</span>
                    <span class="badge badge-soft-secondary">{{ $log->evidenceFiles->count() }}</span>
                </div>
                <div class="card-body">
                    @if($log->evidenceFiles->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($log->evidenceFiles as $evidence)
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar avatar-sm"><i class="bi {{ str_contains($evidence->mime_type, 'pdf') ? 'bi-file-earmark-pdf' : (str_contains($evidence->mime_type, 'image') ? 'bi-file-earmark-image' : 'bi-file-earmark-word') }}"></i></span>
                                        <div>
                                            <a href="{{ route('evidence.download', $evidence) }}" class="fw-semibold text-navy">{{ $evidence->original_name }}</a>
                                            <div class="text-muted-2" style="font-size:.72rem;">{{ $evidence->humanSize() }} · {{ $evidence->created_at->format('d M Y H:i') }}</div>
                                        </div>
                                    </div>
                                    <a href="{{ route('evidence.download', $evidence) }}" class="btn btn-sm btn-outline-secondary py-0"><i class="bi bi-download"></i></a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted-2 mb-0" style="font-size:.83rem;">No evidence files were attached to this log.</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">Review history</div>
                <div class="card-body">
                    @if($log->approvals->count() > 0)
                        <ul class="timeline mb-0">
                            @foreach($log->approvals->sortByDesc('created_at') as $approval)
                                <li class="{{ $approval->action === \App\Enums\ApprovalAction::Approved ? 'timeline-success' : ($approval->action === \App\Enums\ApprovalAction::Rejected || $approval->action === \App\Enums\ApprovalAction::CorrectionRequested ? 'timeline-danger' : '') }}">
                                    <div class="d-flex justify-content-between gap-2">
                                        <strong>{{ $approval->reviewer?->name ?? 'Supervisor' }} · {{ $approval->action->label() }}</strong>
                                        <small class="text-muted-2">{{ $approval->created_at->format('d M Y H:i') }}</small>
                                    </div>
                                    @if($approval->comments)<div class="text-muted-2 mt-1">{{ $approval->comments }}</div>@endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted-2 mb-0" style="font-size:.83rem;">No decisions or comments recorded yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Student</div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="avatar">{{ strtoupper(Str::substr($log->student->user->name, 0, 1)) }}</span>
                        <div>
                            <div class="fw-semibold">{{ $log->student->user->name }}</div>
                            <div class="text-muted-2" style="font-size:.73rem;">{{ $log->student->registration_number }}</div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between py-1" style="font-size:.82rem;"><span class="text-muted-2">Course</span><span class="text-end">{{ $log->student->course?->name ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between py-1" style="font-size:.82rem;"><span class="text-muted-2">Department</span><span class="text-end">{{ $log->student->department?->name ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between py-1" style="font-size:.82rem;"><span class="text-muted-2">Company</span><span class="text-end">{{ $log->internship->company?->name }}</span></div>
                    <a href="{{ route('supervisor.students.show', $log->student) }}" class="btn btn-sm btn-outline-secondary w-100 mt-2">Open student profile</a>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Attendance on {{ $log->date->format('d M') }}</div>
                <div class="card-body" style="font-size:.82rem;">
                    @if($log->attendance)
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Check-in</span><strong>{{ $log->attendance->check_in_time?->format('H:i') ?? '—' }}</strong></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Check-out</span><strong>{{ $log->attendance->check_out_time?->format('H:i') ?? 'Not recorded' }}</strong></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Distance</span><span>{{ $log->attendance->distance_from_company !== null ? number_format($log->attendance->distance_from_company).' m' : '—' }}</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Status</span><x-status-badge :status="$log->attendance->status" /></div>
                        @if($log->attendance->notes)
                            <div class="text-muted-2 mt-2" style="font-size:.74rem;">{{ $log->attendance->notes }}</div>
                        @endif
                    @else
                        <p class="text-muted-2 mb-0">No attendance record linked to this log.</p>
                    @endif
                </div>
            </div>

            @if($log->status->isPendingReview())
                <div class="card">
                    <div class="card-header">Decision</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('supervisor.approvals.approve', $log) }}" class="mb-3">
                            @csrf
                            <label for="comment" class="form-label">Approval comment <span class="text-muted-2 fw-normal">(optional)</span></label>
                            <textarea id="comment" name="comment" rows="2" maxlength="2000" class="form-control mb-2" placeholder="Feedback for the student"></textarea>
                            <button class="btn btn-success w-100" data-confirm="Approve this daily log? The student will be notified and the log will be locked."><i class="bi bi-check2-circle me-1"></i>Approve log</button>
                        </form>

                        <hr>

                        <form method="POST" action="{{ route('supervisor.approvals.reject', $log) }}" class="mb-3" id="returnForm">
                            @csrf
                            <label for="reason" class="form-label">Return or reject with reason <span class="text-danger">*</span></label>
                            <textarea id="reason" name="reason" rows="3" minlength="10" maxlength="2000" class="form-control mb-2" required placeholder="Explain exactly what needs to change (at least 10 characters)"></textarea>
                            <div class="d-grid gap-2">
                                <button type="submit" name="decision" value="correction" class="btn btn-warning"><i class="bi bi-arrow-repeat me-1"></i>Request correction</button>
                                <button type="submit" name="decision" value="reject" class="btn btn-outline-danger" data-confirm="Reject this log? The student will be notified and can revise it."><i class="bi bi-x-circle me-1"></i>Reject log</button>
                            </div>
                        </form>

                        <hr>

                        <form method="POST" action="{{ route('supervisor.approvals.comment', $log) }}">
                            @csrf
                            <label for="commentOnly" class="form-label">Add a comment</label>
                            <div class="input-group">
                                <input id="commentOnly" name="comment" maxlength="2000" class="form-control" placeholder="Note for the review history" required>
                                <button class="btn btn-outline-primary">Add</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
