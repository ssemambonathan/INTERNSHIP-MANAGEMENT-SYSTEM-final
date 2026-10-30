@extends('layouts.app')

@section('title', 'New Daily Log')
@section('page-title', 'New Daily Log')
@section('page-subtitle', 'Record today\'s learning while it is fresh')

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">Daily learning record</div>
                <div class="card-body">
                    @if($blockReason)
                        <div class="attendance-banner warning mb-0">
                            <i class="bi bi-lock me-1"></i>{{ $blockReason }}
                            @if($internship)
                                <div class="mt-2">
                                    <a href="{{ route('student.logs.index') }}" class="btn btn-sm btn-outline-secondary">Back to logbook</a>
                                    <a href="{{ route('student.attendance.index') }}" class="btn btn-sm btn-outline-primary">Open attendance</a>
                                </div>
                            @endif
                        </div>
                    @elseif($openDates->isEmpty())
                        <div class="attendance-banner info mb-0">
                            <i class="bi bi-lock me-1"></i>There are no open dates available for a new log right now.
                        </div>
                    @else
                        @include('student.logs._form', [
                            'action' => route('student.logs.store'),
                            'method' => 'POST',
                            'log' => null,
                        ])
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Submission rules</div>
                <div class="card-body">
                    <ul class="timeline mb-0">
                        <li class="timeline-success"><strong>Attendance required</strong><div class="text-muted-2">You can only log a day where your attendance was recorded.</div></li>
                        <li class="timeline-warning"><strong>Today only</strong><div class="text-muted-2">Historical dates are locked. Your lecturer may reopen a genuinely missed date.</div></li>
                        <li><strong>One log per day</strong><div class="text-muted-2">Duplicate logs for the same date are rejected by the system.</div></li>
                        <li><strong>Evidence can be attached</strong><div class="text-muted-2">After saving, attach JPG, PNG, PDF or DOCX evidence on the log page.</div></li>
                    </ul>
                </div>
            </div>

            @if($internship)
                <div class="card">
                    <div class="card-header">Placement</div>
                    <div class="card-body" style="font-size:.82rem;">
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Company</span><strong>{{ $internship->company?->name }}</strong></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Period</span><span class="text-end">{{ $internship->period?->name }}</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Supervisor</span><span class="text-end">{{ $internship->supervisor?->name ?? '—' }}</span></div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
