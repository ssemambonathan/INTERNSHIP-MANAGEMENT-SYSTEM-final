@extends('layouts.app')

@section('title', 'My Students')
@section('page-title', 'My Students')
@section('page-subtitle', $supervisor->company?->name . ' · internship monitoring')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label" for="q">Search student</label>
                    <input id="q" type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Name, registration number or student number">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="status">Placement status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All placements</option>
                        @foreach(['active' => 'Active', 'completed' => 'Completed', 'pending' => 'Pending', 'cancelled' => 'Cancelled'] as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100">Filter</button>
                </div>
                <div class="col-md-2 text-end">
                    <a href="{{ route('supervisor.students.index') }}" class="btn btn-link text-decoration-none">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span>{{ $internships->total() }} student placement{{ $internships->total() === 1 ? '' : 's' }}</span>
            <span class="text-muted-2 fw-normal" style="font-size:.75rem;">Only students assigned to you at {{ $supervisor->company?->name }}</span>
        </div>

        @if($internships->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Student</th><th>Department</th><th>Period</th><th>Attendance</th><th>Pending logs</th><th>Risk</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach($internships as $internship)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar">{{ strtoupper(Str::substr($internship->student->user->name, 0, 1)) }}</span>
                                        <div>
                                            <div class="fw-semibold">{{ $internship->student->user->name }}</div>
                                            <div class="text-muted-2" style="font-size:.72rem;">{{ $internship->student->registration_number }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $internship->student->department?->name ?? '—' }}</td>
                                <td>{{ $internship->period?->name }}</td>
                                <td style="min-width:120px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:6px;">
                                            <div class="progress-bar {{ $internship->attendance_percentage >= 80 ? 'bg-success' : ($internship->attendance_percentage >= 60 ? 'bg-warning' : 'bg-danger') }}" style="width:{{ min(100, $internship->attendance_percentage) }}%"></div>
                                        </div>
                                        <span style="font-size:.78rem;">{{ $internship->attendance_percentage }}%</span>
                                    </div>
                                    <div class="text-muted-2" style="font-size:.7rem;">{{ $internship->attended_days }} / {{ $internship->expected_days }} days</div>
                                </td>
                                <td>
                                    @if($internship->pending_logs_count > 0)
                                        <a href="{{ route('supervisor.approvals.index') }}" class="badge badge-soft-warning text-decoration-none">{{ $internship->pending_logs_count }} awaiting</a>
                                    @else
                                        <span class="badge badge-soft-success">Clear</span>
                                    @endif
                                </td>
                                <td><x-status-badge :status="$internship->risk_assessment->level" /></td>
                                <td class="text-end"><a href="{{ route('supervisor.students.show', $internship->student) }}" class="btn btn-sm btn-outline-primary py-0">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body border-top">{{ $internships->links() }}</div>
        @else
            <x-empty-state icon="bi-people" title="No students found" text="No placements match your filters. Students assigned to your company will appear here." />
        @endif
    </div>
@endsection
