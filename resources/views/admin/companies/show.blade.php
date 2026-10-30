@extends('layouts.app')

@section('title', 'Company Profile')
@section('page-title', 'Company Profile')
@section('page-subtitle', $company->name)

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('admin.companies.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to companies</a>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.companies.edit', $company) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
            <form method="POST" action="{{ route('admin.companies.toggle-status', $company) }}" data-confirm="{{ $company->isActive() ? 'Deactivating this company blocks new attendance check-ins. Continue?' : 'Activate this company?' }}">
                @csrf
                <button class="btn btn-sm {{ $company->isActive() ? 'btn-outline-danger' : 'btn-outline-success' }}">
                    <i class="bi {{ $company->isActive() ? 'bi-pause-circle' : 'bi-play-circle' }} me-1"></i>{{ $company->isActive() ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="Active placements" :value="$activePlacements" icon="bi-briefcase" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Completed" :value="$completedPlacements" icon="bi-check2-circle" tone="success" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Supervisors" :value="$company->supervisors->count()" icon="bi-person-badge" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Status" :value="$company->status->label()" icon="bi-building" :tone="$company->isActive() ? 'success' : 'secondary'" /></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">Organisation details</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">Industry</span><span>{{ $company->industry }}</span></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">Address</span><span class="text-end">{{ $company->address }}, {{ $company->city }}</span></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">Phone</span><span>{{ $company->phone ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">Email</span><span>{{ $company->email ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between py-2" style="font-size:.83rem;"><span class="text-muted-2">Registered</span><span>{{ $company->created_at?->format('d M Y') }}</span></div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">Attendance geofence</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">Latitude</span><span>{{ number_format($company->latitude, 6) }}</span></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">Longitude</span><span>{{ number_format($company->longitude, 6) }}</span></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">Allowed radius</span><strong>{{ number_format($company->allowed_radius) }} m</strong></div>
                    <div class="alert alert-info mt-3 mb-0" style="font-size:.78rem;">
                        <i class="bi bi-info-circle me-1"></i>Check-ins beyond this radius are recorded as outside location and require supervisor review. Changing these values does not alter historical records.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">
                    <span>Company supervisors</span>
                    <a href="{{ route('admin.supervisors.create') }}" class="btn btn-sm btn-outline-primary py-0">Add</a>
                </div>
                @if($company->supervisors->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($company->supervisors as $supervisor)
                            <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
                                <div>
                                    <div class="fw-semibold" style="font-size:.84rem;">{{ $supervisor->user?->name }}</div>
                                    <div class="text-muted-2" style="font-size:.72rem;">{{ $supervisor->position ?? 'Supervisor' }} · {{ $supervisor->user?->email }}</div>
                                </div>
                                <a href="{{ route('admin.supervisors.show', $supervisor) }}" class="btn btn-sm btn-outline-secondary py-0">Open</a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-empty-state icon="bi-person-badge" title="No supervisors" text="Add a company supervisor so this placement can be monitored at the workplace." :action="route('admin.supervisors.create')" action-label="Add supervisor" />
                @endif
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">
                    <span>Placements</span>
                    <a href="{{ route('admin.internships.index', ['company_id' => $company->id]) }}" class="btn btn-sm btn-link text-decoration-none">View all</a>
                </div>
                @if($company->internships->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Student</th><th>Period</th><th>Supervisor</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                                @foreach($company->internships->take(8) as $internship)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold" style="font-size:.82rem;">{{ $internship->student->user->name }}</div>
                                            <div class="text-muted-2" style="font-size:.7rem;">{{ $internship->student->registration_number }} · {{ $internship->start_date->format('d M Y') }}</div>
                                        </td>
                                        <td style="font-size:.78rem;">{{ $internship->period?->name }}</td>
                                        <td style="font-size:.78rem;">{{ $internship->supervisor?->name ?? '—' }}</td>
                                        <td><x-status-badge :status="$internship->status" /></td>
                                        <td class="text-end"><a href="{{ route('admin.internships.show', $internship) }}" class="btn btn-sm btn-outline-secondary py-0">Open</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($company->internships->count() > 8)
                        <div class="card-body border-top py-2 text-muted-2" style="font-size:.74rem;">Showing 8 of {{ $company->internships->count() }} placements.</div>
                    @endif
                @else
                    <x-empty-state icon="bi-briefcase" title="No placements" text="Create a placement to assign a student to this company." :action="route('admin.internships.create')" action-label="Create placement" />
                @endif
            </div>
        </div>
    </div>
@endsection
