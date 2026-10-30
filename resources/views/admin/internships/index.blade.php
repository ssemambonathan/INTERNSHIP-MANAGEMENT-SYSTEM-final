@extends('layouts.app')

@section('title', 'Placements')
@section('page-title', 'Internship Placements')
@section('page-subtitle', 'Student placements, supervision teams and periods')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="section-label">Placement register</div>
            <div class="text-muted-2" style="font-size:.82rem;">Each placement links a student to a company supervisor, a university lecturer and an internship period.</div>
        </div>
        <a href="{{ route('admin.internships.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New placement</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="All placements" :value="(int) $counts->sum()" icon="bi-briefcase" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Active" :value="(int) ($counts['active'] ?? 0)" icon="bi-play-circle" tone="success" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Pending" :value="(int) ($counts['pending'] ?? 0)" icon="bi-hourglass-split" tone="warning" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Completed" :value="(int) ($counts['completed'] ?? 0)" icon="bi-check2-circle" tone="primary" /></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="q">Search</label>
                    <input id="q" type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Student, registration number or company">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="company_id">Company</label>
                    <select id="company_id" name="company_id" class="form-select">
                        <option value="">All companies</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected($companyId === $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="period_id">Period</label>
                    <select id="period_id" name="period_id" class="form-select">
                        <option value="">All periods</option>
                        @foreach($periods as $period)
                            <option value="{{ $period->id }}" @selected($periodId === $period->id)>{{ $period->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach(\App\Enums\InternshipStatus::cases() as $case)
                            <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary">Apply filters</button>
                    <a href="{{ route('admin.internships.index') }}" class="btn btn-link text-decoration-none">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span>{{ $internships->total() }} placement{{ $internships->total() === 1 ? '' : 's' }}</span>
            <span class="text-muted-2 fw-normal" style="font-size:.74rem;">Page {{ $internships->currentPage() }} of {{ $internships->lastPage() }}</span>
        </div>
        @if($internships->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Student</th><th>Company</th><th>Supervision</th><th>Period &amp; dates</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @foreach($internships as $internship)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar">{{ strtoupper(Str::substr($internship->student?->user?->name ?? '?', 0, 1)) }}</span>
                                        <div>
                                            <div class="fw-semibold">{{ $internship->student?->user?->name ?? 'Student removed' }}</div>
                                            <div class="text-muted-2" style="font-size:.71rem;">{{ $internship->student?->registration_number }}</div>
                                            <div class="text-muted-2" style="font-size:.7rem;">{{ $internship->student?->department?->code }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size:.82rem;">{{ $internship->company?->name ?? 'Company removed' }}</div>
                                    <div class="text-muted-2" style="font-size:.7rem;">{{ $internship->company?->city }}</div>
                                </td>
                                <td style="font-size:.78rem;">
                                    <div><i class="bi bi-person-badge text-muted-2 me-1"></i>{{ $internship->supervisor?->name ?? 'No supervisor' }}</div>
                                    <div class="text-muted-2" style="font-size:.71rem;"><i class="bi bi-mortarboard me-1"></i>{{ $internship->lecturer?->name ?? 'No lecturer' }}</div>
                                </td>
                                <td>
                                    <div style="font-size:.78rem;">{{ $internship->period?->name ?? '—' }}</div>
                                    <div class="text-muted-2" style="font-size:.7rem;">{{ $internship->start_date->format('d M Y') }} – {{ $internship->end_date->format('d M Y') }}</div>
                                </td>
                                <td><x-status-badge :status="$internship->status" /></td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('admin.internships.show', $internship) }}" class="btn btn-sm btn-outline-primary py-0">View</a>
                                    <a href="{{ route('admin.internships.edit', $internship) }}" class="btn btn-sm btn-outline-secondary py-0">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body border-top">{{ $internships->links() }}</div>
        @else
            <x-empty-state icon="bi-briefcase" title="No placements found" text="Create a placement to link a student to a host company, its supervisor and a university lecturer." :action="route('admin.internships.create')" action-label="New placement" />
        @endif
    </div>
@endsection
