@extends('layouts.app')

@section('title', 'Companies')
@section('page-title', 'Companies')
@section('page-subtitle', 'Host organisations, supervisors and GPS geofences')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="section-label">Host organisations</div>
            <div class="text-muted-2" style="font-size:.82rem;">A company's coordinates and radius determine whether student check-ins are inside the approved location.</div>
        </div>
        <a href="{{ route('admin.companies.create') }}" class="btn btn-primary"><i class="bi bi-building-add me-1"></i>Register company</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="Companies" :value="$counts['total']" icon="bi-building" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Active" :value="$counts['active']" icon="bi-check2-circle" tone="success" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Supervisors" :value="$counts['supervisors']" icon="bi-person-badge" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Active placements" :value="$counts['placements']" icon="bi-briefcase" tone="warning" /></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label" for="q">Search</label>
                    <input id="q" type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Company, industry or city">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach(\App\Enums\CompanyStatus::cases() as $case)
                            <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
                <div class="col-md-1 text-end"><a href="{{ route('admin.companies.index') }}" class="btn btn-link text-decoration-none px-0">Reset</a></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span>{{ $companies->total() }} compan{{ $companies->total() === 1 ? 'y' : 'ies' }}</span>
        </div>
        @if($companies->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Company</th><th>Location</th><th>Geofence</th><th>Supervisors</th><th>Placements</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        @foreach($companies as $company)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $company->name }}</div>
                                    <div class="text-muted-2" style="font-size:.72rem;">{{ $company->industry }}</div>
                                </td>
                                <td style="font-size:.8rem;">{{ $company->city }}<div class="text-muted-2" style="font-size:.7rem;">{{ $company->address }}</div></td>
                                <td style="font-size:.76rem;">
                                    <div>{{ number_format($company->allowed_radius) }} m</div>
                                    <div class="text-muted-2" style="font-size:.68rem;">{{ number_format($company->latitude, 5) }}, {{ number_format($company->longitude, 5) }}</div>
                                </td>
                                <td><a href="{{ route('admin.supervisors.index', ['company_id' => $company->id]) }}" class="text-decoration-none">{{ $company->supervisors_count }}</a></td>
                                <td>
                                    <span class="badge badge-soft-primary">{{ $company->active_internships_count }} active</span>
                                    <span class="text-muted-2" style="font-size:.7rem;">of {{ $company->internships_count }} total</span>
                                </td>
                                <td><x-status-badge :status="$company->status" /></td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('admin.companies.show', $company) }}" class="btn btn-sm btn-outline-primary py-0">View</a>
                                    <a href="{{ route('admin.companies.edit', $company) }}" class="btn btn-sm btn-outline-secondary py-0">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body border-top">{{ $companies->links() }}</div>
        @else
            <x-empty-state icon="bi-building" title="No companies found" text="Register a host organisation to assign supervisors and create placements." :action="route('admin.companies.create')" action-label="Register company" />
        @endif
    </div>
@endsection
