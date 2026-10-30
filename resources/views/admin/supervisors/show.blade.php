@extends('layouts.app')

@section('title', 'Supervisor Profile')
@section('page-title', 'Supervisor Profile')
@section('page-subtitle', $supervisor->user->name . ' · ' . ($supervisor->company?->name ?? 'No company'))

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('admin.supervisors.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to supervisors</a>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.supervisors.edit', $supervisor) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
            <form method="POST" action="{{ route('admin.supervisors.toggle-status', $supervisor) }}" data-confirm="Change this account's status?">
                @csrf
                <button class="btn btn-sm {{ $supervisor->user->status === \App\Enums\UserStatus::Active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                    <i class="bi {{ $supervisor->user->status === \App\Enums\UserStatus::Active ? 'bi-person-slash' : 'bi-person-check' }} me-1"></i>
                    {{ $supervisor->user->status === \App\Enums\UserStatus::Active ? 'Suspend account' : 'Activate account' }}
                </button>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="Placements" :value="$internships->count()" icon="bi-briefcase" tone="primary" :hint="$internships->where('status', \App\Enums\InternshipStatus::Active)->count() . ' active'" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Logs awaiting review" :value="$pendingLogs" icon="bi-hourglass-split" :tone="$pendingLogs ? 'warning' : 'success'" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Evaluations" :value="$evaluationsCount" icon="bi-clipboard-data" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Account" :value="$supervisor->user->status->label()" icon="bi-person-badge" :tone="$supervisor->user->status === \App\Enums\UserStatus::Active ? 'success' : 'danger'" /></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">Supervisor record</div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="avatar" style="width:56px;height:56px;font-size:1.3rem;">{{ strtoupper(Str::substr($supervisor->user->name, 0, 1)) }}</span>
                        <div>
                            <div class="fw-semibold">{{ $supervisor->user->name }}</div>
                            <div class="text-muted-2" style="font-size:.78rem;">{{ $supervisor->user->email }}</div>
                            <x-status-badge :status="$supervisor->user->status" class="mt-1" />
                        </div>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">Company</span><span>{{ $supervisor->company?->name ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">Position</span><span>{{ $supervisor->position ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem;"><span class="text-muted-2">Phone</span><span>{{ $supervisor->user->phone ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between py-2" style="font-size:.83rem;"><span class="text-muted-2">Account created</span><span>{{ $supervisor->created_at?->format('d M Y') }}</span></div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">
                    <span>Assigned placements</span>
                    <a href="{{ route('admin.internships.index', ['q' => $supervisor->user->name]) }}" class="btn btn-sm btn-link text-decoration-none">Manage placements</a>
                </div>
                @if($internships->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Student</th><th>Period</th><th>Logs</th><th>Attendance</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                                @foreach($internships as $internship)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold" style="font-size:.83rem;">{{ $internship->student->user->name }}</div>
                                            <div class="text-muted-2" style="font-size:.7rem;">{{ $internship->student->registration_number }} · {{ $internship->student->department?->code }}</div>
                                        </td>
                                        <td style="font-size:.77rem;">{{ $internship->period?->name }}</td>
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
                    <x-empty-state icon="bi-briefcase" title="No placements" text="This supervisor is not yet attached to a student placement." />
                @endif
            </div>
        </div>
    </div>
@endsection
