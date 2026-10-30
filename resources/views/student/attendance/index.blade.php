@extends('layouts.app')

@section('title', 'My Attendance')
@section('page-title', 'GPS Attendance')
@section('page-subtitle', 'Geofenced check-in and check-out history')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <x-stat label="Attendance rate" :value="$percentage . '%'" icon="bi-calendar-check" tone="success" :hint="$attendedDays . ' attended / ' . $expectedDays . ' expected'" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat label="Last attendance" :value="$lastAttendance?->format('d M') ?? '—'" icon="bi-clock-history" tone="primary" :hint="$lastAttendance?->format('Y') ?? 'No record yet'" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat label="Today" :value="$today ? $today->status->label() : 'Not checked in'" icon="bi-geo-alt" :tone="$today ? 'success' : 'warning'" :hint="$today?->check_in_time?->format('H:i')" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat label="Geofence" :value="($internship?->company?->allowed_radius ?? settings('default_gps_radius', 150)) . ' m'" icon="bi-broadcast-pin" tone="primary" hint="Allowed company radius" />
        </div>
    </div>

    @if(! $internship)
        <div class="card">
            <x-empty-state icon="bi-geo-alt" title="No active placement" text="GPS attendance becomes available when the internship office activates your placement." />
        </div>
    @else
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card mb-3">
                    <div class="card-header">
                        <span><i class="bi bi-geo-alt me-1"></i>Today · {{ today()->format('d M Y') }}</span>
                        @if($today)
                            <x-status-badge :status="$today->status" />
                        @else
                            <span class="badge badge-soft-secondary">No record</span>
                        @endif
                    </div>
                    <div class="card-body">
                        @if($today)
                            <div class="row g-3 text-center">
                                <div class="col-4">
                                    <div class="section-label">Check-in</div>
                                    <div class="action-time">{{ $today->check_in_time?->format('H:i') }}</div>
                                </div>
                                <div class="col-4">
                                    <div class="section-label">Check-out</div>
                                    <div class="action-time">{{ $today->check_out_time?->format('H:i') ?? '—' }}</div>
                                </div>
                                <div class="col-4">
                                    <div class="section-label">Worked</div>
                                    <div class="action-time">{{ $today->worked_minutes !== null ? intdiv($today->worked_minutes, 60) . 'h ' . str_pad((string) ($today->worked_minutes % 60), 2, '0', STR_PAD_LEFT) : '—' }}</div>
                                </div>
                            </div>

                            <div class="row g-2 mt-3" style="font-size:.78rem;">
                                <div class="col-sm-6"><span class="text-muted-2">Distance from company:</span> <strong>{{ $today->distance_from_company !== null ? number_format($today->distance_from_company) . ' m' : '—' }}</strong></div>
                                <div class="col-sm-6"><span class="text-muted-2">GPS accuracy:</span> <strong>{{ $today->check_in_accuracy !== null ? round($today->check_in_accuracy) . ' m' : '—' }}</strong></div>
                                <div class="col-sm-6"><span class="text-muted-2">Verification:</span> <strong>{{ $today->verification_method?->label() }}</strong></div>
                                <div class="col-sm-6"><span class="text-muted-2">Late by:</span> <strong>{{ $today->late_minutes ? $today->late_minutes . ' min' : 'No' }}</strong></div>
                            </div>

                            @if($today->notes)
                                <div class="alert alert-light border mt-3 mb-0 py-2" style="font-size:.8rem;">{{ $today->notes }}</div>
                            @endif

                            @if($canCheckOut)
                                <form method="POST" action="{{ route('student.attendance.check-out') }}" class="js-geo-form mt-3">
                                    @csrf
                                    <input type="hidden" name="latitude" value="">
                                    <input type="hidden" name="longitude" value="">
                                    <input type="hidden" name="accuracy" value="">
                                    <div class="geo-status"></div>
                                    <button class="btn btn-primary checkin-btn w-100" type="submit"><i class="bi bi-box-arrow-right me-1"></i>CHECK OUT</button>
                                    <div class="form-text text-center mt-2">Stay on site — your checkout location is verified against the geofence.</div>
                                </form>
                            @elseif($today->status === \App\Enums\AttendanceStatus::Rejected)
                                <div class="attendance-banner danger mt-3 mb-0">This record was rejected and cannot be checked out.</div>
                            @else
                                <div class="attendance-banner success mt-3 mb-0"><i class="bi bi-check-circle me-1"></i>Attendance is complete for today.</div>
                            @endif
                        @elseif($canCheckIn)
                            <div class="text-center py-3">
                                <i class="bi bi-geo-alt fs-1 text-navy"></i>
                                <h6 class="mt-2">Ready to check in?</h6>
                                <p class="text-muted-2 mx-auto" style="max-width:420px;font-size:.82rem;">You must be within {{ $internship->company?->allowed_radius }} metres of {{ $internship->company?->name }}. We use your device GPS only at the moment you check in or out.</p>
                                <form method="POST" action="{{ route('student.attendance.check-in') }}" class="js-geo-form">
                                    @csrf
                                    <input type="hidden" name="latitude" value="">
                                    <input type="hidden" name="longitude" value="">
                                    <input type="hidden" name="accuracy" value="">
                                    <div class="geo-status"></div>
                                    <button class="btn btn-primary checkin-btn" type="submit"><i class="bi bi-geo-alt me-1"></i>CHECK IN</button>
                                </form>
                                <div class="form-text mt-2">Late after {{ settings('late_after', '08:30') }} · {{ settings('university_name') }}</div>
                            </div>
                        @else
                            <div class="attendance-banner info mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                @if(! $isWorkingDay)
                                    Today is not a scheduled working day.
                                @elseif(! $internship->period?->isActive())
                                    Your internship period is not open.
                                @else
                                    Check-in is not available because your placement or company is inactive.
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span>Attendance history</span>
                        <form method="GET" class="d-flex gap-2">
                            <input type="month" name="month" value="{{ $monthDate->format('Y-m') }}" class="form-control form-control-sm" style="width:150px;">
                            <button class="btn btn-sm btn-outline-secondary">Filter</button>
                        </form>
                    </div>
                    @if($records->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr><th>Date</th><th>In</th><th>Out</th><th>Distance</th><th>Status</th><th></th></tr>
                                </thead>
                                <tbody>
                                    @foreach($records as $record)
                                        <tr>
                                            <td>
                                                <span class="fw-semibold">{{ $record->date->format('d M') }}</span>
                                                <div class="text-muted-2" style="font-size:.72rem;">{{ $record->date->format('D') }}</div>
                                            </td>
                                            <td>{{ $record->check_in_time?->format('H:i') ?? '—' }}</td>
                                            <td>{{ $record->check_out_time?->format('H:i') ?? '—' }}</td>
                                            <td>{{ $record->distance_from_company !== null ? number_format($record->distance_from_company) . ' m' : '—' }}</td>
                                            <td><x-status-badge :status="$record->status" /></td>
                                            <td class="text-end">
                                                @if($record->notes)
                                                    <span data-bs-toggle="tooltip" title="{{ $record->notes }}"><i class="bi bi-info-circle text-muted"></i></span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-body border-top">{{ $records->links() }}</div>
                    @else
                        <x-empty-state icon="bi-calendar-x" title="No attendance records" text="There are no attendance records for {{ $monthDate->format('F Y') }}." />
                    @endif
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card mb-3">
                    <div class="card-header">Last 14 days</div>
                    <div class="card-body">
                        @if($trend)
                            <div style="height:260px;"><canvas data-chart="{{ json_encode($trend) }}"></canvas></div>
                        @else
                            <x-empty-state icon="bi-bar-chart" title="No trend data" text="Your daily attendance trend will appear here." />
                        @endif
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">How verification works</div>
                    <div class="card-body">
                        <ul class="timeline mb-0">
                            <li class="timeline-success"><strong>Check in on site.</strong><div class="text-muted-2">Your coordinates are compared with {{ $internship->company?->name }}'s geofence.</div></li>
                            <li class="timeline-warning"><strong>Outside the radius?</strong><div class="text-muted-2">The record is flagged for your supervisor to review. It is never silently discarded.</div></li>
                            <li class="timeline-success"><strong>Check out later.</strong><div class="text-muted-2">Working hours are calculated from server timestamps.</div></li>
                            <li><strong>Flagged reviews.</strong><div class="text-muted-2">Outside-location or incomplete records show their status here once reviewed.</div></li>
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Placement geofence</div>
                    <div class="card-body" style="font-size:.83rem;">
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Company</span><span class="text-end fw-semibold">{{ $internship->company?->name }}</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Address</span><span class="text-end">{{ $internship->company?->address }}</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Centre</span><span>{{ number_format($internship->company?->latitude ?? 0, 5) }}, {{ number_format($internship->company?->longitude ?? 0, 5) }}</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Radius</span><span class="fw-semibold">{{ $internship->company?->allowed_radius }} m</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Working hours</span><span>{{ settings('working_start_time', '08:00') }} – {{ settings('working_end_time', '17:00') }}</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Late threshold</span><span>{{ settings('late_after', '08:30') }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
