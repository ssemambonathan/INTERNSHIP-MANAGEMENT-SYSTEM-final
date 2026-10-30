@extends('layouts.app')

@section('title', 'Supervision Visits')
@section('page-title', 'Supervision Visits')
@section('page-subtitle', 'Plan, record and evidence site visits to your students')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="section-label">Visit policy</div>
            <div class="text-muted-2" style="font-size:.82rem;">Complete a visit to record observations and attach evidence. Scheduled visits can be rescheduled or cancelled.</div>
        </div>
        <a href="{{ route('lecturer.visits.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Schedule visit</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="Upcoming" :value="$upcomingCount" icon="bi-calendar-check" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Completed" :value="$counts[\App\Enums\VisitStatus::Completed->value] ?? 0" icon="bi-check2-circle" tone="success" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Scheduled total" :value="$counts[\App\Enums\VisitStatus::Scheduled->value] ?? 0" icon="bi-calendar-week" tone="warning" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Cancelled" :value="$counts[\App\Enums\VisitStatus::Cancelled->value] ?? 0" icon="bi-x-circle" tone="primary" /></div>
    </div>

    <div class="card">
        <div class="card-header">
            <span>Visit schedule</span>
            <form method="GET" class="d-flex flex-wrap gap-2">
                <input type="search" name="q" value="{{ $search }}" class="form-control form-control-sm" style="width:190px;" placeholder="Search student">
                <select name="status" class="form-select form-select-sm" style="width:150px;" onchange="this.form.submit()">
                    <option value="all" @selected($status === 'all')>All statuses</option>
                    @foreach(\App\Enums\VisitStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-outline-secondary">Filter</button>
            </form>
        </div>

        @if($visits->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Student</th><th>Company</th><th>Visit date</th><th>Purpose</th><th>Evidence</th><th>Status</th><th class="text-end"></th></tr></thead>
                    <tbody>
                        @foreach($visits as $visit)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $visit->internship->student->user->name }}</div>
                                    <div class="text-muted-2" style="font-size:.72rem;">{{ $visit->internship->student->registration_number }}</div>
                                </td>
                                <td>{{ $visit->internship->company?->name }}</td>
                                <td class="text-nowrap">
                                    {{ $visit->visit_date->format('d M Y') }}
                                    <div class="text-muted-2" style="font-size:.7rem;">{{ $visit->visit_date->diffForHumans() }}</div>
                                </td>
                                <td style="max-width:260px;"><div class="text-truncate" style="max-width:250px;">{{ $visit->purpose }}</div></td>
                                <td><span class="badge badge-soft-secondary"><i class="bi bi-paperclip me-1"></i>{{ $visit->evidence_files_count }}</span></td>
                                <td><x-status-badge :status="$visit->status" /></td>
                                <td class="text-end"><a href="{{ route('lecturer.visits.show', $visit) }}" class="btn btn-sm btn-outline-primary py-0">Open</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body border-top">{{ $visits->links() }}</div>
        @else
            <x-empty-state icon="bi-pin-map" title="No visits found" text="Schedule a supervision visit to verify a student's placement and record evidence." :action="route('lecturer.visits.create')" action-label="Schedule first visit" />
        @endif
    </div>

    @if($activeInternships->count() > 0)
        <div class="card mt-3">
            <div class="card-header">Quick schedule</div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach($activeInternships as $internship)
                        <div class="col-md-6 col-xl-4">
                            <div class="border rounded-3 p-3 d-flex justify-content-between align-items-center h-100">
                                <div>
                                    <div class="fw-semibold" style="font-size:.84rem;">{{ $internship->student->user->name }}</div>
                                    <div class="text-muted-2" style="font-size:.72rem;">{{ $internship->company?->name }}</div>
                                </div>
                                <a href="{{ route('lecturer.visits.create', ['internship_id' => $internship->id]) }}" class="btn btn-sm btn-outline-primary">Schedule</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
@endsection
