@extends('layouts.app')

@section('title', 'Daily Logbook')
@section('page-title', 'Daily Logbook')
@section('page-subtitle', 'Evidence-backed learning records, locked to the day they happened')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="section-label">Submission policy</div>
            <div class="text-muted-2" style="font-size:.82rem;">Today's log can be written now. Historical dates stay locked unless your lecturer reopens them.</div>
        </div>
        <a href="{{ route('student.logs.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New daily log</a>
    </div>

    @if($missedDates->count() > 0)
        <div class="alert alert-warning">
            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Missed log days detected</div>
            <div style="font-size:.82rem;">You attended on {{ $missedDates->count() }} day{{ $missedDates->count() === 1 ? '' : 's' }} without submitting a log. These cannot be back-filled unless a lecturer reopens the date.</div>
            <div class="mt-2 d-flex flex-wrap gap-1">
                @foreach($missedDates->take(12) as $missedDate)
                    <span class="badge badge-soft-warning">{{ $missedDate->format('d M Y') }}</span>
                @endforeach
                @if($missedDates->count() > 12)
                    <span class="badge badge-soft-secondary">+{{ $missedDates->count() - 12 }} more</span>
                @endif
            </div>
        </div>
    @endif

    @if($recentReopenings->count() > 0)
        <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div style="font-size:.83rem;"><i class="bi bi-unlock me-1"></i>Your lecturer reopened {{ $recentReopenings->count() }} date{{ $recentReopenings->count() === 1 ? '' : 's' }} for logging.</div>
            <div class="d-flex flex-wrap gap-1">
                @foreach($recentReopenings as $reopening)
                    <span class="badge badge-soft-info">{{ $reopening->date->format('d M Y') }}</span>
                @endforeach
            </div>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="Total logs" :value="$logs->total()" icon="bi-journal-text" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Approved" :value="$counts[\App\Enums\DailyLogStatus::Approved->value] ?? 0" icon="bi-journal-check" tone="success" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Awaiting review" :value="($counts[\App\Enums\DailyLogStatus::Submitted->value] ?? 0) + ($counts[\App\Enums\DailyLogStatus::UnderReview->value] ?? 0)" icon="bi-hourglass-split" tone="warning" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Needs correction" :value="$counts[\App\Enums\DailyLogStatus::Rejected->value] ?? 0" icon="bi-arrow-repeat" tone="danger" /></div>
    </div>

    <div class="card">
        <div class="card-header">
            <span>My logbook entries</span>
            <form method="GET" class="d-flex gap-2">
                <select name="status" class="form-select form-select-sm" style="width:170px;">
                    <option value="">All statuses</option>
                    @foreach(\App\Enums\DailyLogStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-outline-secondary">Filter</button>
            </form>
        </div>

        @if($logs->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Date</th><th>Activities</th><th>Hours</th><th>Evidence</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr>
                                <td>
                                    <span class="fw-semibold">{{ $log->date->format('d M Y') }}</span>
                                    <div class="text-muted-2" style="font-size:.72rem;">{{ $log->date->format('l') }}</div>
                                </td>
                                <td style="max-width:340px;">
                                    <div class="text-truncate" style="max-width:330px;">{{ $log->activities_performed ?: '—' }}</div>
                                    @if($log->status === \App\Enums\DailyLogStatus::Rejected && $log->rejection_reason)
                                        <div class="text-danger mt-1" style="font-size:.72rem;"><i class="bi bi-exclamation-circle me-1"></i>{{ Str::limit($log->rejection_reason, 80) }}</div>
                                    @endif
                                </td>
                                <td>{{ $log->hours_worked }}</td>
                                <td><span class="badge badge-soft-secondary"><i class="bi bi-paperclip me-1"></i>{{ $log->evidence_files_count }}</span></td>
                                <td><x-status-badge :status="$log->status" /></td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('student.logs.show', $log) }}" class="btn btn-sm btn-outline-secondary py-0">View</a>
                                    @if($log->isEditableByStudent())
                                        <a href="{{ route('student.logs.edit', $log) }}" class="btn btn-sm btn-outline-primary py-0">Edit</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body border-top">{{ $logs->links() }}</div>
        @else
            <x-empty-state icon="bi-journal" title="No logbook entries" text="Once you check in for the day, you can record your activities, skills and reflections here." :action="route('student.logs.create')" action-label="Write today's log" />
        @endif
    </div>
@endsection
