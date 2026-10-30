@extends('layouts.app')

@section('title', 'Company Supervisors')
@section('page-title', 'Company Supervisors')
@section('page-subtitle', 'Workplace supervisors who review attendance, logbooks and evaluations')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="section-label">Workplace supervision</div>
            <div class="text-muted-2" style="font-size:.82rem;">Each supervisor is attached to one host company and can only access students placed at that company.</div>
        </div>
        <a href="{{ route('admin.supervisors.create') }}" class="btn btn-primary"><i class="bi bi-person-badge me-1"></i>Add supervisor</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="Supervisors" :value="$counts['total']" icon="bi-person-badge" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Active accounts" :value="$counts['active']" icon="bi-person-check" tone="success" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Companies covered" :value="$counts['companies']" icon="bi-building" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Active placements" :value="$counts['placements']" icon="bi-briefcase" tone="warning" /></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="q">Search</label>
                    <input id="q" type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Name or email">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="company_id">Company</label>
                    <select id="company_id" name="company_id" class="form-select">
                        <option value="">All companies</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected((int) $companyId === $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="status">Account</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Any status</option>
                        @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'] as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
                <div class="col-md-1 text-end"><a href="{{ route('admin.supervisors.index') }}" class="btn btn-link text-decoration-none px-0">Reset</a></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">{{ $supervisors->total() }} supervisor{{ $supervisors->total() === 1 ? '' : 's' }}</div>
        @if($supervisors->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Supervisor</th><th>Company</th><th>Position</th><th>Placements</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        @foreach($supervisors as $supervisor)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar">{{ strtoupper(Str::substr($supervisor->user?->name ?? '?', 0, 1)) }}</span>
                                        <div>
                                            <div class="fw-semibold">{{ $supervisor->user?->name }}</div>
                                            <div class="text-muted-2" style="font-size:.72rem;">{{ $supervisor->user?->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $supervisor->company?->name ?? '—' }}</td>
                                <td>{{ $supervisor->position ?? '—' }}</td>
                                <td>
                                    <span class="badge badge-soft-primary">{{ $supervisor->active_internships_count }} active</span>
                                    <div class="text-muted-2" style="font-size:.7rem;">{{ $supervisor->internships_count }} total</div>
                                </td>
                                <td><x-status-badge :status="$supervisor->user?->status ?? \App\Enums\UserStatus::Inactive" /></td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('admin.supervisors.show', $supervisor) }}" class="btn btn-sm btn-outline-primary py-0">View</a>
                                    <a href="{{ route('admin.supervisors.edit', $supervisor) }}" class="btn btn-sm btn-outline-secondary py-0">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body border-top">{{ $supervisors->links() }}</div>
        @else
            <x-empty-state icon="bi-person-badge" title="No supervisors found" text="Add company supervisors so workplace approvals and evaluations can be completed." :action="route('admin.supervisors.create')" action-label="Add supervisor" />
        @endif
    </div>
@endsection
