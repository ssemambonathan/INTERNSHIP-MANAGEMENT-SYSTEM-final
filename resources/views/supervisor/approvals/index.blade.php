@extends('layouts.app')

@section('title', 'Log Approvals')
@section('page-title', 'Log Approvals')
@section('page-subtitle', 'Review daily logs submitted by students at your company')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="Awaiting decision" :value="$pendingCount" icon="bi-hourglass-split" :tone="$pendingCount ? 'warning' : 'primary'" hint="Submitted or in review" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Approved" :value="$counts[\App\Enums\DailyLogStatus::Approved->value] ?? 0" icon="bi-journal-check" tone="success" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Returned" :value="$counts[\App\Enums\DailyLogStatus::Rejected->value] ?? 0" icon="bi-arrow-repeat" tone="danger" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Total reviewed" :value="$logs->total()" icon="bi-journals" tone="primary" hint="Current filter" /></div>
    </div>

    <div class="card">
        <div class="card-header">
            <span>Approval queue</span>
            <form method="GET" class="d-flex gap-2">
                <select name="status" class="form-select form-select-sm" style="width:180px;" onchange="this.form.submit()">
                    <option value="pending" @selected($filter === 'pending')>Pending review</option>
                    <option value="approved" @selected($filter === 'approved')>Approved</option>
                    <option value="rejected" @selected($filter === 'rejected')>Returned / rejected</option>
                    <option value="under_review" @selected($filter === 'under_review')>In review</option>
                    <option value="submitted" @selected($filter === 'submitted')>Submitted</option>
                </select>
                <noscript><button class="btn btn-sm btn-outline-secondary">Filter</button></noscript>
            </form>
        </div>

        @if($logs->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Student</th><th>Date</th><th>Hours</th><th>Evidence</th><th>Submitted</th><th>Status</th><th class="text-end"></th></tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $log->student->user->name }}</div>
                                    <div class="text-muted-2" style="font-size:.72rem;">{{ $log->student->registration_number }} · {{ $log->internship->company?->name }}</div>
                                </td>
                                <td>{{ $log->date->format('d M Y') }}<div class="text-muted-2" style="font-size:.72rem;">{{ $log->date->format('l') }}</div></td>
                                <td>{{ $log->hours_worked }} h</td>
                                <td><span class="badge badge-soft-secondary"><i class="bi bi-paperclip me-1"></i>{{ $log->evidence_files_count }}</span></td>
                                <td class="text-nowrap">{{ $log->submitted_at?->format('d M, H:i') ?? '—' }}</td>
                                <td><x-status-badge :status="$log->status" /></td>
                                <td class="text-end"><a href="{{ route('supervisor.approvals.show', $log) }}" class="btn btn-sm {{ $log->status->isPendingReview() ? 'btn-primary' : 'btn-outline-secondary' }} py-0">{{ $log->status->isPendingReview() ? 'Review' : 'View' }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body border-top">{{ $logs->links() }}</div>
        @else
            <x-empty-state icon="bi-journal-check" title="Nothing to review" :text="$filter === 'pending' ? 'All submitted logs have been reviewed. New submissions will appear here.' : 'No logs match this filter.'" />
        @endif
    </div>
@endsection
