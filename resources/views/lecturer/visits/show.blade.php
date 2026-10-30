@extends('layouts.app')

@section('title', 'Supervision Visit')
@section('page-title', 'Supervision Visit')
@section('page-subtitle', $visit->internship->student->user->name . ' · ' . $visit->visit_date->format('l, d F Y'))

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('lecturer.visits.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to visits</a>
        <div class="d-flex flex-wrap gap-2">
            @can('update', $visit)
                <a href="{{ route('lecturer.visits.edit', $visit) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
            @endcan
            @can('delete', $visit)
                <form method="POST" action="{{ route('lecturer.visits.destroy', $visit) }}" data-confirm="Cancel this visit? The student will keep the visit record for audit.">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Cancel visit</button>
                </form>
            @endcan
            <x-status-badge :status="$visit->status" />
        </div>
    </div>

    @if($visit->status === \App\Enums\VisitStatus::Completed)
        <div class="attendance-banner success mb-3"><i class="bi bi-check-circle me-1"></i>Visit completed on {{ $visit->completed_at?->format('d M Y, H:i') }}. Outcomes and evidence are kept on record.</div>
    @elseif($visit->status === \App\Enums\VisitStatus::Cancelled)
        <div class="attendance-banner danger mb-3"><i class="bi bi-x-circle me-1"></i>This visit was cancelled. No further action can be taken.</div>
    @else
        <div class="attendance-banner info mb-3"><i class="bi bi-calendar-check me-1"></i>Visit is scheduled for {{ $visit->visit_date->format('l, d F Y') }} ({{ $visit->visit_date->diffForHumans() }}).</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">
                    <span>Visit overview</span>
                    <span class="text-muted-2 fw-normal" style="font-size:.74rem;">Scheduled {{ $visit->created_at->format('d M Y') }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="section-label">Student</div>
                            <div class="fw-semibold">{{ $visit->internship->student->user->name }}</div>
                            <div class="text-muted-2" style="font-size:.75rem;">{{ $visit->internship->student->registration_number }} · {{ $visit->internship->student->department?->name }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="section-label">Company</div>
                            <div class="fw-semibold">{{ $visit->internship->company?->name }}</div>
                            <div class="text-muted-2" style="font-size:.75rem;">{{ $visit->internship->company?->city }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="section-label">Company supervisor</div>
                            <div class="fw-semibold">{{ $visit->internship->supervisor?->name ?? '—' }}</div>
                            <div class="text-muted-2" style="font-size:.75rem;">{{ $visit->internship->supervisor?->user?->email }}</div>
                        </div>
                        <div class="col-12">
                            <div class="section-label">Purpose</div>
                            <div style="font-size:.86rem;">{{ $visit->purpose }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if($visit->status === \App\Enums\VisitStatus::Completed)
                <div class="card mb-3">
                    <div class="card-header">Visit outcomes</div>
                    <div class="card-body">
                        @foreach([
                            'observations' => ['Observations', 'bi-clipboard-check'],
                            'student_performance' => ['Student performance', 'bi-graph-up-arrow'],
                            'company_feedback' => ['Company feedback', 'bi-building'],
                            'recommendations' => ['Recommendations', 'bi-lightbulb'],
                            'action_items' => ['Action items', 'bi-check2-square'],
                        ] as $field => [$label, $icon])
                            <div class="mb-3">
                                <div class="section-label mb-1"><i class="bi {{ $icon }} me-1"></i>{{ $label }}</div>
                                <div style="font-size:.85rem;white-space:pre-line;">{{ $visit->{$field} ?: '—' }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="card mb-3">
                <div class="card-header">
                    <span><i class="bi bi-paperclip me-1"></i>Visit evidence</span>
                    <span class="badge badge-soft-secondary">{{ $visit->evidenceFiles->count() }} / 5</span>
                </div>
                <div class="card-body">
                    @if($visit->evidenceFiles->count() > 0)
                        <div class="list-group list-group-flush mb-3">
                            @foreach($visit->evidenceFiles as $evidence)
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar avatar-sm"><i class="bi {{ str_contains($evidence->mime_type, 'pdf') ? 'bi-file-earmark-pdf' : (str_contains($evidence->mime_type, 'image') ? 'bi-file-earmark-image' : 'bi-file-earmark-word') }}"></i></span>
                                        <div>
                                            <a href="{{ route('evidence.download', $evidence) }}" class="fw-semibold text-navy">{{ $evidence->original_name }}</a>
                                            <div class="text-muted-2" style="font-size:.72rem;">{{ $evidence->humanSize() }} · uploaded by {{ $evidence->uploader?->name ?? '—' }} · {{ $evidence->created_at->format('d M Y H:i') }}</div>
                                        </div>
                                    </div>
                                    @can('delete', $evidence)
                                        <form method="POST" action="{{ route('lecturer.visits.evidence.destroy', $evidence) }}" data-confirm="Remove this evidence file?">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted-2 mb-3" style="font-size:.83rem;">No evidence uploaded yet. Attach photos of the visit or signed attendance sheets.</p>
                    @endif

                    @if($visit->status !== \App\Enums\VisitStatus::Cancelled && $visit->evidenceFiles->count() < 5)
                        <form method="POST" action="{{ route('lecturer.visits.evidence.store', $visit) }}" enctype="multipart/form-data">
                            @csrf
                            <label for="evidence" class="form-label">Attach evidence</label>
                            <div class="input-group">
                                <input id="evidence" type="file" name="evidence" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.docx" data-max-size-kb="{{ settings('max_upload_size_kb', 10240) }}" required>
                                <button class="btn btn-outline-primary">Upload</button>
                            </div>
                            <div class="form-text" data-file-hint="evidence">JPG, PNG, PDF or DOCX · maximum {{ round(settings('max_upload_size_kb', 10240) / 1024) }} MB.</div>
                        </form>
                    @endif
                </div>
            </div>

            @if($visit->status === \App\Enums\VisitStatus::Scheduled)
                <div class="card">
                    <div class="card-header">Complete visit</div>
                    <div class="card-body">
                        <p class="text-muted-2" style="font-size:.82rem;">Record the outcomes after the visit. Completing the visit locks its purpose and date; evidence can still be attached.</p>
                        <form method="POST" action="{{ route('lecturer.visits.complete', $visit) }}">
                            @csrf
                            <div class="mb-3">
                                <label for="observations" class="form-label">Observations <span class="text-danger">*</span></label>
                                <textarea id="observations" name="observations" rows="4" minlength="10" maxlength="5000" class="form-control" required placeholder="What did you observe during the visit?">{{ old('observations') }}</textarea>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="student_performance" class="form-label">Student performance</label>
                                    <textarea id="student_performance" name="student_performance" rows="3" maxlength="3000" class="form-control">{{ old('student_performance') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="company_feedback" class="form-label">Company feedback</label>
                                    <textarea id="company_feedback" name="company_feedback" rows="3" maxlength="3000" class="form-control">{{ old('company_feedback') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="recommendations" class="form-label">Recommendations</label>
                                    <textarea id="recommendations" name="recommendations" rows="3" maxlength="3000" class="form-control">{{ old('recommendations') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="action_items" class="form-label">Action items</label>
                                    <textarea id="action_items" name="action_items" rows="3" maxlength="3000" class="form-control">{{ old('action_items') }}</textarea>
                                </div>
                            </div>
                            <button class="btn btn-success mt-3" data-confirm="Mark this visit as completed? This cannot be undone."><i class="bi bi-check2-circle me-1"></i>Complete visit</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Placement</div>
                <div class="card-body" style="font-size:.82rem;">
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Lecturer</span><span class="text-end">{{ $visit->lecturer?->name }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Dates</span><span class="text-end">{{ $visit->internship->start_date->format('d M Y') }} – {{ $visit->internship->end_date->format('d M Y') }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Status</span><x-status-badge :status="$visit->internship->status" /></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Evidence</span><span>{{ $visit->evidenceFiles->count() }} file{{ $visit->evidenceFiles->count() === 1 ? '' : 's' }}</span></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Related</div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ route('lecturer.students.show', $visit->internship->student) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-person me-1"></i>Student monitoring</a>
                    <a href="{{ route('lecturer.reports.student', $visit->internship->student) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-text me-1"></i>Student report</a>
                    <a href="{{ route('lecturer.visits.create', ['internship_id' => $visit->internship_id]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-plus-lg me-1"></i>Schedule another visit</a>
                </div>
            </div>
        </div>
    </div>
@endsection
