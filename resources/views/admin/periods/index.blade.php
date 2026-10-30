@extends('layouts.app')

@section('title', 'Internship Periods')
@section('page-title', 'Internship Periods')
@section('page-subtitle', 'Academic windows that placements and attendance operate inside')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="section-label">Period calendar</div>
            <div class="text-muted-2" style="font-size:.82rem;">Draft → Active → Closed. A closed period can be reopened; overlapping active periods are not allowed.</div>
        </div>
        <a href="{{ route('admin.periods.create') }}" class="btn btn-primary"><i class="bi bi-calendar-plus me-1"></i>New period</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="All periods" :value="(int) $counts->sum()" icon="bi-calendar3" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Active" :value="(int) ($counts['active'] ?? 0)" icon="bi-play-circle" tone="success" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Draft" :value="(int) ($counts['draft'] ?? 0)" icon="bi-pencil-square" tone="warning" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Closed" :value="(int) ($counts['closed'] ?? 0)" icon="bi-lock" tone="secondary" /></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="q">Search</label>
                    <input id="q" type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Period name">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="academic_year_id">Academic year</label>
                    <select id="academic_year_id" name="academic_year_id" class="form-select">
                        <option value="">All academic years</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" @selected($academicYearId === $year->id)>{{ $year->name }}{{ $year->is_current ? ' (current)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach(\App\Enums\PeriodStatus::cases() as $case)
                            <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
            </form>
            @if($search !== '' || $status !== '' || $academicYearId)
                <div class="mt-2"><a href="{{ route('admin.periods.index') }}" class="btn btn-sm btn-link text-decoration-none px-0">Clear filters</a></div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span>{{ $periods->total() }} period{{ $periods->total() === 1 ? '' : 's' }}</span>
        </div>
        @if($periods->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Period</th><th>Academic year</th><th>Dates</th><th>Placements</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        @foreach($periods as $period)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $period->name }}</div>
                                    <div class="text-muted-2" style="font-size:.7rem;">Created {{ $period->created_at?->format('d M Y') }}</div>
                                </td>
                                <td style="font-size:.8rem;">
                                    {{ $period->academicYear?->name }}
                                    @if($period->academicYear?->is_current)
                                        <span class="badge badge-soft-primary ms-1">Current</span>
                                    @endif
                                </td>
                                <td class="text-nowrap" style="font-size:.78rem;">
                                    {{ $period->start_date->format('d M Y') }} – {{ $period->end_date->format('d M Y') }}
                                    <div class="text-muted-2" style="font-size:.7rem;">{{ $period->start_date->diffInDays($period->end_date) + 1 }} days</div>
                                </td>
                                <td>
                                    <a href="{{ route('admin.internships.index', ['period_id' => $period->id]) }}" class="text-decoration-none" style="font-size:.8rem;">
                                        {{ $period->internships_count }} placement{{ $period->internships_count === 1 ? '' : 's' }}
                                    </a>
                                </td>
                                <td><x-status-badge :status="$period->status" /></td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('admin.periods.edit', $period) }}" class="btn btn-sm btn-outline-secondary py-0">Edit</a>
                                    <form method="POST" action="{{ route('admin.periods.toggle-status', $period) }}" class="d-inline"
                                          data-confirm="{{ $period->status === \App\Enums\PeriodStatus::Active ? 'Close this period? Students will no longer be able to check in.' : 'Set this period to active? Existing records are preserved.' }}">
                                        @csrf
                                        <button class="btn btn-sm {{ $period->status === \App\Enums\PeriodStatus::Active ? 'btn-outline-danger' : 'btn-outline-success' }} py-0">
                                            {{ $period->status === \App\Enums\PeriodStatus::Draft ? 'Activate' : ($period->status === \App\Enums\PeriodStatus::Active ? 'Close' : 'Reopen') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body border-top">{{ $periods->links() }}</div>
        @else
            <x-empty-state icon="bi-calendar3" title="No internship periods" text="Create an internship period before registering placements." :action="route('admin.periods.create')" action-label="New period" />
        @endif
    </div>
@endsection
