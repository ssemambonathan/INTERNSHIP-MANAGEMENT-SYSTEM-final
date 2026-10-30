@extends('layouts.app')

@section('title', 'Lecturers')
@section('page-title', 'Lecturers')
@section('page-subtitle', 'Academic supervisors responsible for monitoring and site visits')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="section-label">Academic supervision</div>
            <div class="text-muted-2" style="font-size:.82rem;">Lecturers monitor only the students whose placements are assigned to them.</div>
        </div>
        <a href="{{ route('admin.lecturers.create') }}" class="btn btn-primary"><i class="bi bi-person-workspace me-1"></i>Add lecturer</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="Lecturers" :value="$counts['total']" icon="bi-person-workspace" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Active accounts" :value="$counts['active']" icon="bi-person-check" tone="success" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Departments covered" :value="$counts['departments']" icon="bi-diagram-3" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Supervision visits" :value="$counts['visits']" icon="bi-pin-map" tone="warning" /></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="q">Search</label>
                    <input id="q" type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Name, email or staff number">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="department_id">Department</label>
                    <select id="department_id" name="department_id" class="form-select">
                        <option value="">All departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected((int) $departmentId === $department->id)>{{ $department->name }}</option>
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
                <div class="col-md-1 text-end"><a href="{{ route('admin.lecturers.index') }}" class="btn btn-link text-decoration-none px-0">Reset</a></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">{{ $lecturers->total() }} lecturer{{ $lecturers->total() === 1 ? '' : 's' }}</div>
        @if($lecturers->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Lecturer</th><th>Department</th><th>Placements</th><th>Visits</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        @foreach($lecturers as $lecturer)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar">{{ strtoupper(Str::substr($lecturer->user?->name ?? '?', 0, 1)) }}</span>
                                        <div>
                                            <div class="fw-semibold">{{ $lecturer->user?->name }}</div>
                                            <div class="text-muted-2" style="font-size:.72rem;">{{ $lecturer->staff_number }} · {{ $lecturer->user?->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-size:.8rem;">{{ $lecturer->department?->name }}<div class="text-muted-2" style="font-size:.7rem;">{{ $lecturer->department?->faculty?->name }}</div></td>
                                <td>
                                    <span class="badge badge-soft-primary">{{ $lecturer->active_internships_count }} active</span>
                                    <div class="text-muted-2" style="font-size:.7rem;">{{ $lecturer->internships_count }} total</div>
                                </td>
                                <td>{{ $lecturer->visits_count }}</td>
                                <td><x-status-badge :status="$lecturer->user?->status ?? \App\Enums\UserStatus::Inactive" /></td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('admin.lecturers.show', $lecturer) }}" class="btn btn-sm btn-outline-primary py-0">View</a>
                                    <a href="{{ route('admin.lecturers.edit', $lecturer) }}" class="btn btn-sm btn-outline-secondary py-0">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body border-top">{{ $lecturers->links() }}</div>
        @else
            <x-empty-state icon="bi-person-workspace" title="No lecturers found" text="Add lecturers so placements can receive academic monitoring and supervision visits." :action="route('admin.lecturers.create')" action-label="Add lecturer" />
        @endif
    </div>
@endsection
