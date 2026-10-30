@extends('layouts.app')

@section('title', 'Edit Daily Log')
@section('page-title', 'Edit Daily Log')
@section('page-subtitle', $log->date->format('l, d F Y'))

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <span>Daily learning record</span>
                    <x-status-badge :status="$log->status" />
                </div>
                <div class="card-body">
                    @if($log->status === \App\Enums\DailyLogStatus::Rejected && $log->rejection_reason)
                        <div class="alert alert-warning">
                            <div class="fw-semibold"><i class="bi bi-arrow-repeat me-1"></i>Correction requested</div>
                            <div style="font-size:.83rem;">{{ $log->rejection_reason }}</div>
                        </div>
                    @endif

                    @if($log->attendance)
                        <div class="d-flex flex-wrap gap-3 mb-3 p-2 bg-light rounded-3" style="font-size:.78rem;">
                            <span><i class="bi bi-clock me-1"></i>In: {{ $log->attendance->check_in_time?->format('H:i') ?? '—' }}</span>
                            <span><i class="bi bi-clock-history me-1"></i>Out: {{ $log->attendance->check_out_time?->format('H:i') ?? '—' }}</span>
                            <x-status-badge :status="$log->attendance->status" />
                        </div>
                    @endif

                    @include('student.logs._form', [
                        'action' => route('student.logs.update', $log),
                        'method' => 'PATCH',
                        'log' => $log,
                        'openDates' => collect(),
                        'dateValue' => $log->date->toDateString(),
                    ])
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Editing rules</div>
                <div class="card-body">
                    <ul class="timeline mb-0">
                        <li class="timeline-success"><strong>Drafts and corrections are editable</strong><div class="text-muted-2">You may refine and resubmit returned logs.</div></li>
                        <li class="timeline-warning"><strong>Under review is locked</strong><div class="text-muted-2">Once submitted, only your supervisor can approve, reject or request changes.</div></li>
                        <li class="timeline-danger"><strong>Approved logs are final</strong><div class="text-muted-2">Approved entries cannot be altered by students.</div></li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Evidence</div>
                <div class="card-body" style="font-size:.82rem;">
                    <p class="text-muted-2">Attach supporting files from the log's detail page after saving.</p>
                    <a href="{{ route('student.logs.show', $log) }}" class="btn btn-sm btn-outline-secondary">Open log detail</a>
                </div>
            </div>
        </div>
    </div>
@endsection
