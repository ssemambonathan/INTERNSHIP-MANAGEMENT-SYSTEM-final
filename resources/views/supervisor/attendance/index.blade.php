@extends('layouts.app')

@section('title', 'Attendance Monitoring')
@section('page-title', 'Attendance Monitoring')
@section('page-subtitle', 'GPS-verified attendance for students at your company')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="Present" :value="$counts[\App\Enums\AttendanceStatus::Present->value] ?? 0" icon="bi-check-circle" tone="success" :hint="$monthDate->format('F Y')" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Late" :value="$counts[\App\Enums\AttendanceStatus::Late->value] ?? 0" icon="bi-clock-history" tone="warning" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Flagged this month" :value="($counts[\App\Enums\AttendanceStatus::OutsideLocation->value] ?? 0) + ($counts[\App\Enums\AttendanceStatus::Incomplete->value] ?? 0)" icon="bi-exclamation-triangle" tone="danger" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Flagged all time" :value="$flaggedTotal" icon="bi-shield-exclamation" tone="warning" hint="Needs verification" /></div>
    </div>

    @if($flaggedTotal > 0)
        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div style="font-size:.83rem;"><i class="bi bi-exclamation-triangle me-1"></i>{{ $flaggedTotal }} attendance record{{ $flaggedTotal === 1 ? '' : 's' }} still need{{ $flaggedTotal === 1 ? 's' : '' }} your verification. Only flagged records can be verified or rejected.</div>
            <a href="{{ route('supervisor.attendance.index', ['status' => 'flagged']) }}" class="btn btn-sm btn-warning">Show flagged</a>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <span>Attendance records</span>
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-auto"><input type="month" name="month" value="{{ $monthDate->format('Y-m') }}" class="form-control form-control-sm"></div>
                <div class="col-auto">
                    <select name="status" class="form-select form-select-sm">
                        <option value="all" @selected($status === 'all')>All statuses</option>
                        <option value="flagged" @selected($status === 'flagged')>Flagged only</option>
                        @foreach(\App\Enums\AttendanceStatus::cases() as $case)
                            <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto"><input type="search" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Search student"></div>
                <div class="col-auto"><button class="btn btn-sm btn-outline-secondary">Filter</button></div>
                <div class="col-auto"><a href="{{ route('supervisor.attendance.index') }}" class="btn btn-sm btn-link text-decoration-none">Reset</a></div>
            </form>
        </div>

        @if($records->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Student</th><th>Date</th><th>In / Out</th><th>Distance</th><th>Worked</th><th>Status</th><th class="text-end">Decision</th></tr>
                    </thead>
                    <tbody>
                        @foreach($records as $attendance)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $attendance->student->user->name }}</div>
                                    <div class="text-muted-2" style="font-size:.72rem;">{{ $attendance->student->registration_number }}</div>
                                </td>
                                <td>{{ $attendance->date->format('d M Y') }}<div class="text-muted-2" style="font-size:.72rem;">{{ $attendance->date->format('l') }}</div></td>
                                <td>
                                    <div>{{ $attendance->check_in_time?->format('H:i') ?? '—' }} <span class="text-muted-2">to</span> {{ $attendance->check_out_time?->format('H:i') ?? '—' }}</div>
                                    @if($attendance->late_minutes)
                                        <div class="text-warning" style="font-size:.72rem;">{{ $attendance->late_minutes }} min late</div>
                                    @endif
                                </td>
                                <td>{{ $attendance->distance_from_company !== null ? number_format($attendance->distance_from_company).' m' : '—' }}</td>
                                <td>{{ $attendance->worked_minutes !== null ? intdiv($attendance->worked_minutes, 60).'h '.($attendance->worked_minutes % 60).'m' : '—' }}</td>
                                <td>
                                    <x-status-badge :status="$attendance->status" />
                                    @if($attendance->verification_method === \App\Enums\VerificationMethod::Manual)
                                        <div class="text-muted-2" style="font-size:.68rem;">Manual · {{ $attendance->reviewer?->name }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($attendance->status->requiresReview())
                                        <div class="d-flex justify-content-end gap-1">
                                            <form method="POST" action="{{ route('supervisor.attendance.verify', $attendance) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-success py-0" data-confirm="Verify this attendance record? It will count as attended.">Verify</button>
                                            </form>
                                            <button class="btn btn-sm btn-outline-danger py-0" data-bs-toggle="modal" data-bs-target="#rejectAttendance{{ $attendance->id }}">Reject</button>
                                        </div>
                                    @else
                                        <span class="text-muted-2" style="font-size:.75rem;">{{ $attendance->reviewed_at?->format('d M, H:i') ?? 'No action needed' }}</span>
                                    @endif
                                </td>
                            </tr>
                            @if($attendance->notes)
                                <tr class="table-light">
                                    <td colspan="7" class="py-1"><span class="text-muted-2" style="font-size:.73rem;"><i class="bi bi-info-circle me-1"></i>{{ $attendance->notes }}</span></td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body border-top">{{ $records->links() }}</div>
        @else
            <x-empty-state icon="bi-calendar-x" title="No attendance records" text="No records match this filter for the selected month." />
        @endif
    </div>

    @foreach($records as $attendance)
        @if($attendance->status->requiresReview())
            <div class="modal fade" id="rejectAttendance{{ $attendance->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('supervisor.attendance.reject', $attendance) }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Reject attendance</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted-2" style="font-size:.82rem;">{{ $attendance->student->user->name }} · {{ $attendance->date->format('d M Y') }}. The student will be notified and this day will not count as attended.</p>
                                <label for="reason{{ $attendance->id }}" class="form-label">Reason <span class="text-danger">*</span></label>
                                <textarea id="reason{{ $attendance->id }}" name="reason" rows="3" minlength="5" maxlength="1000" class="form-control" required placeholder="Why is this attendance being rejected?"></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn btn-danger">Reject attendance</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endsection
