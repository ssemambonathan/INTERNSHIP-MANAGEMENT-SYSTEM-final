@extends('layouts.app')

@section('title', 'Student Dashboard')
@section('page-title', 'Internship Dashboard')
@section('page-subtitle', now()->format('l, d F Y') . ' · ' . settings('working_start_time', '08:00') . '–' . settings('working_end_time', '17:00'))

@section('content')
    @if(! $student)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Your account is not yet linked to a student record. Contact the internship administrator.
        </div>
    @elseif(! $internship)
        <div class="hero-band mb-3">
            <h2 class="h4 mb-1">Hello, {{ Str::of($student->name)->explode(' ')->first() }}</h2>
            <p class="mb-0 text-hero-muted">You do not have an active internship placement at the moment.</p>
        </div>
        <div class="card">
            <x-empty-state icon="bi-briefcase" title="No active internship placement" text="Once the internship office assigns your company and supervisor and activates your placement, attendance and daily logging will become available here." />
        </div>
    @else
        <div class="hero-band mb-3">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <div class="text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.09em;color:#f0b429;">Active internship</div>
                    <h2 class="h4 mb-1">{{ $internship->company?->name }}</h2>
                    <div class="text-hero-muted" style="font-size:.85rem;">
                        <i class="bi bi-calendar-range me-1"></i>{{ $internship->start_date->format('d M Y') }} – {{ $internship->end_date->format('d M Y') }}
                        <span class="mx-2">·</span>
                        <i class="bi bi-building me-1"></i>{{ $internship->company?->city }}
                    </div>
                    <div class="text-hero-muted mt-2" style="font-size:.8rem;">
                        Supervisor: {{ $internship->supervisor?->name ?? 'Not assigned' }}
                        <span class="mx-2">·</span>
                        Lecturer: {{ $internship->lecturer?->user?->name ?? 'Not assigned' }}
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="badge" style="background:rgba(255,255,255,.12);color:#fff;font-size:.75rem;">
                        <i class="bi bi-briefcase me-1"></i>{{ $internship->period?->name }}
                    </div>
                    <div class="mt-2" style="color:#c7d9ea;font-size:.76rem;">
                        <i class="bi bi-geo-alt me-1"></i>Geofence: {{ $internship->company?->allowed_radius }} m radius
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3">
                <x-stat label="Attendance" :value="$attendancePercentage . '%'" icon="bi-calendar-check" tone="success" :hint="$attendedDays . ' of ' . $expectedDays . ' expected days'" />
            </div>
            <div class="col-6 col-xl-3">
                <x-stat label="Approved logs" :value="$approvedLogs" icon="bi-journal-check" tone="primary" hint="Verified by supervisor" />
            </div>
            <div class="col-6 col-xl-3">
                <x-stat label="Corrections" :value="$pendingCorrections" icon="bi-arrow-repeat" :tone="$pendingCorrections ? 'warning' : 'primary'" hint="Rejected logs to fix" />
            </div>
            <div class="col-6 col-xl-3">
                <x-stat label="Missed logs" :value="$missedLogs" icon="bi-journal-x" :tone="$missedLogs ? 'danger' : 'primary'" hint="Last 30 days" />
            </div>
        </div>

        @if($pendingCorrections > 0)
            <div class="attendance-banner warning mb-3 d-flex justify-content-between align-items-center gap-3">
                <div><i class="bi bi-exclamation-triangle me-1"></i>You have {{ $pendingCorrections }} log{{ $pendingCorrections === 1 ? '' : 's' }} returned for correction. Correct and resubmit promptly.</div>
                <a href="{{ route('student.logs.index', ['status' => 'rejected']) }}" class="btn btn-sm btn-warning text-nowrap">Review</a>
            </div>
        @endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <span><i class="bi bi-clock me-1"></i>Today's attendance</span>
                        <span class="text-muted-2 fw-normal" style="font-size:.75rem;">{{ today()->format('d M Y') }}</span>
                    </div>
                    <div class="card-body">
                        @if($todayAttendance)
                            <div class="row g-3 align-items-center">
                                <div class="col-md-4">
                                    <div class="section-label mb-1">Check-in</div>
                                    <div class="action-time">{{ $todayAttendance->check_in_time?->format('H:i') }}</div>
                                    <x-status-badge :status="$todayAttendance->status" />
                                </div>
                                <div class="col-md-4">
                                    <div class="section-label mb-1">Check-out</div>
                                    <div class="action-time">{{ $todayAttendance->check_out_time?->format('H:i') ?? '—' }}</div>
                                    @if($todayAttendance->worked_minutes !== null)
                                        <div class="text-muted-2" style="font-size:.76rem;">{{ intdiv($todayAttendance->worked_minutes, 60) }}h {{ str_pad((string) ($todayAttendance->worked_minutes % 60), 2, '0', STR_PAD_LEFT) }}m worked</div>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    <div class="section-label mb-1">Location</div>
                                    <div style="font-size:.85rem;">
                                        {{ $todayAttendance->distance_from_company !== null ? number_format($todayAttendance->distance_from_company).' m from company' : '—' }}
                                    </div>
                                    <div class="text-muted-2" style="font-size:.74rem;">Method: {{ $todayAttendance->verification_method?->label() }}</div>
                                </div>
                            </div>

                            @if($todayAttendance->notes)
                                <div class="alert alert-light border mt-3 mb-0 py-2" style="font-size:.8rem;">
                                    <i class="bi bi-info-circle me-1"></i>{{ $todayAttendance->notes }}
                                </div>
                            @endif

                            @if($canCheckOut)
                                <hr class="my-3">
                                <form method="POST" action="{{ route('student.attendance.check-out') }}" class="js-geo-form">
                                    @csrf
                                    <input type="hidden" name="latitude" value="">
                                    <input type="hidden" name="longitude" value="">
                                    <input type="hidden" name="accuracy" value="">
                                    <div class="geo-status"></div>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <button type="submit" class="btn btn-outline-primary checkin-btn">
                                            <i class="bi bi-box-arrow-right me-1"></i>CHECK OUT
                                        </button>
                                        <span class="text-muted-2" style="font-size:.76rem;">Your GPS location will be captured for verification.</span>
                                    </div>
                                </form>
                            @elseif($todayAttendance->status === \App\Enums\AttendanceStatus::Rejected)
                                <div class="attendance-banner danger mt-3">This attendance record was rejected. Contact your supervisor or the internship office.</div>
                            @else
                                <div class="attendance-banner success mt-3">
                                    <i class="bi bi-check-circle me-1"></i>Attendance complete for today. Check-out was recorded at {{ $todayAttendance->check_out_time?->format('H:i') }}.
                                </div>
                            @endif
                        @elseif($canCheckIn)
                            <div class="row align-items-center g-3">
                                <div class="col-md-7">
                                    <h6 class="mb-1">You are due on site today</h6>
                                    <p class="text-muted-2 mb-0" style="font-size:.82rem;">Check in when you arrive at {{ $internship->company?->name }}. Late check-ins after {{ settings('late_after', '08:30') }} are flagged.</p>
                                </div>
                                <div class="col-md-5">
                                    <form method="POST" action="{{ route('student.attendance.check-in') }}" class="js-geo-form">
                                        @csrf
                                        <input type="hidden" name="latitude" value="">
                                        <input type="hidden" name="longitude" value="">
                                        <input type="hidden" name="accuracy" value="">
                                        <div class="geo-status"></div>
                                        <button type="submit" class="btn btn-primary checkin-btn w-100">
                                            <i class="bi bi-geo-alt me-1"></i>CHECK IN
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="attendance-banner info">
                                <i class="bi bi-info-circle me-1"></i>
                                @if(! $internship->isOperational())
                                    Attendance is not available because your placement or company is not currently active.
                                @elseif(! $isWorkingDay)
                                    Today is not a scheduled internship working day. No check-in is required.
                                @elseif(! $internship->period?->isActive())
                                    Your internship period is closed, so attendance is not available.
                                @else
                                    There is no attendance action available right now.
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <span><i class="bi bi-journal-text me-1"></i>Today's logbook</span>
                        @if($todayLog)
                            <x-status-badge :status="$todayLog->status" />
                        @endif
                    </div>
                    <div class="card-body">
                        @if($todayLog)
                            <p class="mb-2 text-truncate-3" style="font-size:.84rem;">{{ $todayLog->activities_performed ?: 'Draft has no activities recorded yet.' }}</p>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <a href="{{ route('student.logs.show', $todayLog) }}" class="btn btn-sm btn-outline-primary">Open today's log</a>
                                @if($todayLog->isEditableByStudent())
                                    <a href="{{ route('student.logs.edit', $todayLog) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                @endif
                                <span class="text-muted-2" style="font-size:.76rem;">{{ $todayLog->hours_worked }} hours recorded</span>
                            </div>
                        @elseif($canSubmitTodayLog)
                            <div class="d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <h6 class="mb-1">Write today's learning record</h6>
                                    <p class="text-muted-2 mb-0" style="font-size:.82rem;">Logs are locked to today. Describe what you actually did, learned and struggled with.</p>
                                </div>
                                <a href="{{ route('student.logs.create') }}" class="btn btn-primary text-nowrap"><i class="bi bi-pencil-square me-1"></i>Write log</a>
                            </div>
                        @else
                            <div class="text-muted-2" style="font-size:.84rem;">
                                <i class="bi bi-lock me-1"></i>
                                @if(! $todayAttendance)
                                    Check in first — a daily log requires a verified attendance record for the same date.
                                @elseif(! $todayAttendance->qualifiesForLog())
                                    Your attendance is {{ $todayAttendance->status->label() }}. A supervisor must verify it before you can submit today's log.
                                @else
                                    Daily log actions will appear here after check-in.
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span>Recent logs</span>
                        <a href="{{ route('student.logs.index') }}" class="btn btn-sm btn-link text-decoration-none">View all</a>
                    </div>
                    @if($recentLogs->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr><th>Date</th><th>Hours</th><th>Status</th><th class="text-end"></th></tr>
                                </thead>
                                <tbody>
                                    @foreach($recentLogs as $log)
                                        <tr>
                                            <td><span class="fw-semibold">{{ $log->date->format('d M Y') }}</span><div class="text-muted-2" style="font-size:.72rem;">{{ $log->date->format('l') }}</div></td>
                                            <td>{{ $log->hours_worked }}</td>
                                            <td><x-status-badge :status="$log->status" /></td>
                                            <td class="text-end"><a href="{{ route('student.logs.show', $log) }}" class="btn btn-sm btn-outline-secondary py-0">View</a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-empty-state icon="bi-journal" title="No logs yet" text="Your first daily log will appear here once you check in." />
                    @endif
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header">Attendance breakdown</div>
                    <div class="card-body">
                        @if($chart)
                            <div style="height:220px;"><canvas data-chart="{{ json_encode($chart) }}"></canvas></div>
                            <div class="text-center text-muted-2 mt-2" style="font-size:.74rem;">{{ $attendancePercentage }}% attendance across {{ $expectedDays }} expected working days</div>
                        @else
                            <x-empty-state icon="bi-graph-up" title="No attendance data" text="Attendance will be charted once you check in." />
                        @endif
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">Placement details</div>
                    <div class="card-body" style="font-size:.83rem;">
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Company</span><span class="text-end fw-semibold">{{ $internship->company?->name }}</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Industry</span><span class="text-end">{{ $internship->company?->industry ?: '—' }}</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Address</span><span class="text-end">{{ $internship->company?->address }}</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Supervisor</span><span class="text-end">{{ $internship->supervisor?->name ?? '—' }}</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Lecturer</span><span class="text-end">{{ $internship->lecturer?->user?->name ?? '—' }}</span></div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Expected hours</span><span class="fw-semibold">{{ settings('minimum_working_hours', 7.5) }}h/day</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Late after</span><span>{{ settings('late_after', '08:30') }}</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Last attendance</span><span>{{ $lastAttendance?->format('d M Y') ?? '—' }}</span></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Notifications</div>
                    <div class="card-body p-0">
                        @php($latestNotifications = auth()->user()->notifications()->limit(4)->get())
                        @forelse($latestNotifications as $notification)
                            <a href="{{ route('notifications.index') }}" class="d-block border-bottom p-3 text-decoration-none">
                                <div class="fw-semibold text-navy" style="font-size:.8rem;">{{ $notification->data['title'] ?? 'Notification' }}</div>
                                <div class="text-muted-2" style="font-size:.74rem;">{{ Str::limit($notification->data['message'] ?? '', 80) }}</div>
                                <div class="text-muted-2 mt-1" style="font-size:.68rem;">{{ $notification->created_at->diffForHumans() }}</div>
                            </a>
                        @empty
                            <div class="p-3 text-muted-2" style="font-size:.8rem;">No notifications yet.</div>
                        @endforelse
                        <a href="{{ route('notifications.index') }}" class="d-block text-center py-2 text-decoration-none" style="font-size:.78rem;">View all notifications</a>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
