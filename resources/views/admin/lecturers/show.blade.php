@extends('layouts.app')

@section('title', 'Lecturer Profile')
@section('page-title', 'Lecturer Profile')
@section('page-subtitle', $lecturer->user->name . ' · ' . $lecturer->staff_number)

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('admin.lecturers.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to lecturers</a>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.lecturers.edit', $lecturer) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
            <form method="POST" action="{{ route('admin.lecturers.toggle-status', $lecturer) }}" data-confirm="Change this account's status?">
                @csrf
                <button class="btn btn-sm {{ $lecturer->user->status === \App\Enums\UserStatus::Active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                    <i class="bi {{ $lecturer->user->status === \App\Enums\UserStatus::Active ? 'bi-person-slash' : 'bi-person-check' }} me-1"></i>
                    {{ $lecturer->user->status === \App\Enums\UserStatus::Active ? 'Suspend account' : 'Activate account' }}
                </button>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="Placements" :value="$internships->count()" icon="bi-briefcase" tone="primary" :hint="$internships->where('status', \App\Enums\InternshipStatus::Active)->count() . ' active'" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Supervision visits" :value="$visits->count()" icon="bi-pin-map" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Evaluations received" :value="$evaluationsCount" icon="bi-clipboard-data" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Account" :value="$lecturer->user->status->label()" icon="bi-person-workspace" :tone="$lecturer->user->status === \App\Enums\UserStatus::Active ? 'success' : 'danger'" /></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">Lecturer record</div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="avatar" style="width:56px;height:56px;font-size:1.3rem;">{{ strtoupper(Str::substr($lecturer->user->name, 0, 1)) }}</span>
                        <div>
                            <div class="fw-semibold">{{ $lecturer->user->name }}</div>
                            <div class="text-muted-2" style="font-size:.78rem;">{{ $lecturer->user->email }}</div>
                            <x-status-badge :status="$lecturer->user->status" class="mt-1" />
                        </div>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">Staff number</span><strong>{{ $lecturer->staff_number }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">Position</span><span>{{ $lecturer->position ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">Department</span><span class="text-end">{{ $lecturer->department?->name ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">Faculty</span><span class="text-end">{{ $lecturer->department?->faculty?->name ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between py-2" style="font-size:.83rem;"><span class="text-muted-2">Phone</span><span>{{ $lecturer->user->phone ?? '—' }}</span></div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">Assigned placements</div>
                @if($internships->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Student</th><th>Company</th><th>Period</th><th>Logs</th><th>Attendance</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                                @foreach($internships as $internship)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold" style="font-size:.82rem;">{{ $internship->student->user->name }}</div>
                                            <div class="text-muted-2" style="font-size:.7rem;">{{ $internship->student->registration_number }}</div>
                                        </td>
                                        <td style="font-size:.78rem;">{{ $internship->company?->name }}</td>
                                        <td style="font-size:.75rem;">{{ $internship->period?->name }}</td>
                                        <td>{{ $internship->daily_logs_count }}</td>
                                        <td>{{ $internship->attendances_count }}</td>
                                        <td><x-status-badge :status="$internship->status" /></td>
                                        <td class="text-end"><a href="{{ route('admin.internships.show', $internship) }}" class="btn btn-sm btn-outline-secondary py-0">Open</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-empty-state icon="bi-briefcase" title="No placements" text="No internship placement is assigned to this lecturer yet." />
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Recent supervision visits</div>
        @if($visits->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Date</th><th>Student</th><th>Company</th><th>Purpose</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach($visits as $visit)
                            <tr>
                                <td class="text-nowrap">{{ $visit->visit_date->format('d M Y') }}</td>
                                <td>{{ $visit->internship->student->user->name }}</td>
                                <td>{{ $visit->internship->company?->name }}</td>
                                <td style="max-width:320px;"><div class="text-truncate" style="max-width:310px;">{{ $visit->purpose }}</div></td>
                                <td><x-status-badge :status="$visit->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-empty-state icon="bi-pin-map" title="No visits recorded" text="This lecturer has not scheduled or completed any supervision visits." />
        @endif
    </div>
@endsection
