@extends('layouts.app')

@section('title', 'Daily Log · ' . $log->date->format('d M Y'))
@section('page-title', 'Daily Log Detail')
@section('page-subtitle', $log->date->format('l, d F Y') . ' · ' . $log->internship->company->name)

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('student.logs.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to logbook</a>
        <div class="d-flex gap-2">
            @if($log->isEditableByStudent())
                <a href="{{ route('student.logs.edit', $log) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit log</a>
            @endif
            @if($log->status === \App\Enums\DailyLogStatus::Draft || $log->status === \App\Enums\DailyLogStatus::Rejected)
                <form method="POST" action="{{ route('student.logs.submit', $log) }}" data-confirm="Submit this log for supervisor review? You will not be able to edit it while it is under review.">
                    @csrf
                    <button class="btn btn-sm btn-primary" @disabled(! $log->isComplete())><i class="bi bi-send me-1"></i>Submit for review</button>
                </form>
            @endif
        </div>
    </div>

    @if($log->status === \App\Enums\DailyLogStatus::Rejected && $log->rejection_reason)
        <div class="alert alert-warning">
            <div class="fw-semibold mb-1"><i class="bi bi-arrow-repeat me-1"></i>This log was returned for correction</div>
            <div style="font-size:.84rem;">{{ $log->rejection_reason }}</div>
            <div class="mt-2"><a href="{{ route('student.logs.edit', $log) }}" class="btn btn-sm btn-warning">Correct and resubmit</a></div>
        </div>
    @elseif($log->status === \App\Enums\DailyLogStatus::Approved)
        <div class="attendance-banner success mb-3">
            <i class="bi bi-check-circle me-1"></i>Approved{{ $log->reviewer ? ' by '.$log->reviewer->name : '' }} on {{ $log->reviewed_at?->format('d M Y, H:i') }}. This log is now locked.
        </div>
    @elseif($log->status->isPendingReview())
        <div class="attendance-banner info mb-3">
            <i class="bi bi-hourglass-split me-1"></i>This log is with your supervisor for review. It cannot be edited until a decision is made.
        </div>
    @elseif($log->status === \App\Enums\DailyLogStatus::Draft)
        <div class="attendance-banner warning mb-3">
            <i class="bi bi-pencil me-1"></i>This is a draft and has not been sent to your supervisor. Complete all fields and submit it.
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">
                    <span>Daily learning record</span>
                    <x-status-badge :status="$log->status" />
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-4">
                            <div class="section-label">Date</div>
                            <div class="fw-semibold">{{ $log->date->format('d M Y') }}</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="section-label">Hours worked</div>
                            <div class="fw-semibold">{{ $log->hours_worked }} hours</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="section-label">Submitted</div>
                            <div class="fw-semibold">{{ $log->submitted_at?->format('d M Y, H:i') ?? 'Not submitted' }}</div>
                        </div>
                    </div>

                    <hr>

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
                    <span><i class="bi bi-paperclip me-1"></i>Evidence files</span>
                    <span class="badge badge-soft-secondary">{{ $log->evidenceFiles->count() }} / 5</span>
                </div>
                <div class="card-body">
                    @if($log->evidenceFiles->count() > 0)
                        <div class="list-group list-group-flush mb-3">
                            @foreach($log->evidenceFiles as $evidence)
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar avatar-sm"><i class="bi {{ str_contains($evidence->mime_type, 'pdf') ? 'bi-file-earmark-pdf' : (str_contains($evidence->mime_type, 'image') ? 'bi-file-earmark-image' : 'bi-file-earmark-word') }}"></i></span>
                                        <div>
                                            <a href="{{ route('evidence.download', $evidence) }}" class="fw-semibold text-navy">{{ $evidence->original_name }}</a>
                                            <div class="text-muted-2" style="font-size:.72rem;">{{ $evidence->humanSize() }} · uploaded {{ $evidence->created_at->format('d M Y H:i') }}</div>
                                        </div>
                                    </div>
                                    @can('delete', $evidence)
                                        <form method="POST" action="{{ route('student.logs.evidence.destroy', $evidence) }}" data-confirm="Remove this evidence file?">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted-2 mb-3" style="font-size:.83rem;">No evidence attached yet. Evidence helps your supervisor verify the work described.</p>
                    @endif

                    @if($log->isEditableByStudent())
                        <form method="POST" action="{{ route('student.logs.evidence.store', $log) }}" enctype="multipart/form-data">
                            @csrf
                            <label for="evidence" class="form-label">Attach evidence</label>
                            <div class="input-group">
                                <input id="evidence" type="file" name="evidence" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.docx" data-max-size-kb="{{ settings('max_upload_size_kb', 10240) }}" required>
                                <button class="btn btn-outline-primary">Upload</button>
                            </div>
                            <div class="form-text" data-file-hint="evidence">JPG, PNG, PDF or DOCX · maximum {{ round(settings('max_upload_size_kb', 10240) / 1024) }} MB.</div>
                        </form>
                    @else
                        <div class="text-muted-2" style="font-size:.78rem;"><i class="bi bi-lock me-1"></i>Evidence is locked while the log is under review or approved.</div>
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
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ $approval->reviewer?->name ?? 'Supervisor' }} · {{ $approval->action->label() }}</strong>
                                        <small class="text-muted-2">{{ $approval->created_at->format('d M Y H:i') }}</small>
                                    </div>
                                    @if($approval->comments)<div class="text-muted-2 mt-1">{{ $approval->comments }}</div>@endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted-2 mb-0" style="font-size:.83rem;">No supervisor decisions recorded yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Attendance for this date</div>
                <div class="card-body" style="font-size:.83rem;">
                    @if($log->attendance)
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Check-in</span><strong>{{ $log->attendance->check_in_time?->format('H:i') ?? '—' }}</strong></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Check-out</span><strong>{{ $log->attendance->check_out_time?->format('H:i') ?? '—' }}</strong></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Distance</span><span>{{ $log->attendance->distance_from_company !== null ? number_format($log->attendance->distance_from_company) . ' m' : '—' }}</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Status</span><x-status-badge :status="$log->attendance->status" /></div>
                    @else
                        <p class="text-muted-2 mb-0">No linked attendance record.</p>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Internship</div>
                <div class="card-body" style="font-size:.83rem;">
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Company</span><span class="text-end fw-semibold">{{ $log->internship->company?->name }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Supervisor</span><span class="text-end">{{ $log->internship->supervisor?->name ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Period</span><span class="text-end">{{ $log->internship->period?->name }}</span></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Related records</div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ route('student.attendance.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-geo-alt me-1"></i>My attendance</a>
                    <a href="{{ route('student.report.show') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-pdf me-1"></i>Internship report</a>
                </div>
            </div>
        </div>
    </div>
@endsection
