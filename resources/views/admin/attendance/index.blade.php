@extends('layouts.app')

@section('title', 'Attendance')
@section('page-title', 'Attendance Records')
@section('page-subtitle', 'GPS check-ins, corrections and verification history')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="section-label">Attendance register</div>
            <div class="text-muted-2" style="font-size:.82rem;">Check-in times, GPS distance and verification method are stored server-side. Administrative corrections are audited and notified to the student.</div>
        </div>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-primary"><i class="bi bi-graph-up me-1"></i>Attendance analytics</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="Records in range" :value="(int) $counts->sum()" icon="bi-geo-alt" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Present" :value="(int) ($counts[\App\Enums\AttendanceStatus::Present->value] ?? 0)" icon="bi-check2-circle" tone="success" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Late" :value="(int) ($counts[\App\Enums\AttendanceStatus::Late->value] ?? 0)" icon="bi-clock-history" tone="warning" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Flagged" :value="(int) ($counts[\App\Enums\AttendanceStatus::OutsideLocation->value] ?? 0) + (int) ($counts[\App\Enums\AttendanceStatus::Incomplete->value] ?? 0)" icon="bi-shield-exclamation" tone="danger" /></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="q">Search</label>
                    <input id="q" type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Student name or registration">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="from">From</label>
                    <input id="from" type="date" name="from" value="{{ $from->toDateString() }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="to">To</label>
                    <input id="to" type="date" name="to" value="{{ $to->toDateString() }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="company_id">Company</label>
                    <select id="company_id" name="company_id" class="form-select">
                        <option value="">All companies</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected($companyId === $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach(\App\Enums\AttendanceStatus::cases() as $case)
                            <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1"><button class="btn btn-primary w-100">Filter</button></div>
            </form>
            @if($search !== '' || $status !== '' || $companyId)
                <div class="mt-2"><a href="{{ route('admin.attendance.index') }}" class="btn btn-sm btn-link text-decoration-none px-0">Clear filters</a></div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span>{{ $attendance->total() }} record{{ $attendance->total() === 1 ? '' : 's' }}</span>
            <span class="text-muted-2 fw-normal" style="font-size:.74rem;">{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</span>
        </div>
        @if($attendance->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Date</th><th>Student</th><th>Company</th><th>In / out</th><th>Worked</th><th>Distance</th><th>Verification</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        @foreach($attendance as $record)
                            <tr>
                                <td class="text-nowrap">
                                    <div style="font-size:.8rem;">{{ $record->date->format('d M Y') }}</div>
                                    <div class="text-muted-2" style="font-size:.68rem;">{{ $record->date->format('l') }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold" style="font-size:.82rem;">{{ $record->student?->user?->name ?? 'Student removed' }}</div>
                                    <div class="text-muted-2" style="font-size:.7rem;">{{ $record->student?->registration_number }}</div>
                                </td>
                                <td style="font-size:.78rem;">{{ $record->internship?->company?->name ?? '—' }}</td>
                                <td class="text-nowrap" style="font-size:.8rem;">
                                    {{ $record->check_in_time?->format('H:i') ?? '—' }} / {{ $record->check_out_time?->format('H:i') ?? '—' }}
                                    @if($record->late_minutes > 0)
                                        <div class="text-warning" style="font-size:.68rem;">{{ $record->late_minutes }} min late</div>
                                    @endif
                                </td>
                                <td style="font-size:.78rem;">{{ $record->worked_minutes !== null ? intdiv($record->worked_minutes, 60).'h '.($record->worked_minutes % 60).'m' : '—' }}</td>
                                <td style="font-size:.78rem;">
                                    {{ $record->distance_from_company !== null ? number_format($record->distance_from_company, 0).' m' : '—' }}
                                    @if($record->check_in_accuracy !== null)
                                        <div class="text-muted-2" style="font-size:.67rem;">±{{ number_format($record->check_in_accuracy, 0) }} m accuracy</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-soft-{{ $record->verification_method === \App\Enums\VerificationMethod::Manual ? 'warning' : 'secondary' }}">{{ $record->verification_method?->label() ?? '—' }}</span>
                                    @if($record->reviewer)
                                        <div class="text-muted-2" style="font-size:.66rem;">by {{ $record->reviewer->name }}</div>
                                    @endif
                                </td>
                                <td><x-status-badge :status="$record->status" /></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary py-0"
                                            data-bs-toggle="modal" data-bs-target="#correctionModal"
                                            data-id="{{ $record->id }}"
                                            data-student="{{ $record->student?->user?->name }}"
                                            data-date="{{ $record->date->format('d M Y') }}"
                                            data-in="{{ $record->check_in_time?->format('H:i') }}"
                                            data-out="{{ $record->check_out_time?->format('H:i') }}"
                                            data-status="{{ $record->status?->value }}"
                                            data-company="{{ $record->internship?->company?->name }}"
                                            data-notes="{{ $record->notes }}">Correct</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body border-top">{{ $attendance->links() }}</div>
        @else
            <x-empty-state icon="bi-geo-alt" title="No attendance recorded" text="No records match the selected dates and filters. Students can check in from their attendance page once a placement is active." />
        @endif
    </div>

    <div class="modal fade" id="correctionModal" tabindex="-1" aria-hidden="true" data-update-url="{{ route('admin.attendance.correct', ['attendance' => '__ID__']) }}">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">Correct attendance</h5>
                            <div class="text-muted-2" id="correctionContext" style="font-size:.75rem;"></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning" style="font-size:.78rem;">
                            <i class="bi bi-shield-exclamation me-1"></i>This record will be marked as a manual correction. The student is notified and the change is written to the audit log with your reason.
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label" for="correction_in">Check-in time</label>
                                <input id="correction_in" type="time" name="check_in_time" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="correction_out">Check-out time</label>
                                <input id="correction_out" type="time" name="check_out_time" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="correction_status">Status</label>
                                <select id="correction_status" name="status" class="form-select" required>
                                    @foreach(\App\Enums\AttendanceStatus::cases() as $case)
                                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Present and late records require a check-out time.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="correction_reason">Reason for correction</label>
                                <textarea id="correction_reason" name="reason" rows="3" class="form-control" minlength="10" maxlength="1000" placeholder="Explain why this record is being changed" required></textarea>
                                <div class="form-text">At least 10 characters. This appears in the audit log and the student's notification.</div>
                            </div>
                        </div>
                        <div class="text-muted-2 mt-3" id="correctionNotes" style="font-size:.73rem;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Apply correction</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('correctionModal');
            if (!modal) return;

            modal.addEventListener('show.bs.modal', event => {
                const trigger = event.relatedTarget;
                if (!trigger) return;
                const data = trigger.dataset;
                const form = modal.querySelector('form');
                form.action = modal.dataset.updateUrl.replace('__ID__', data.id);
                form.reset();

                form.elements.namedItem('check_in_time').value = data.in || '';
                form.elements.namedItem('check_out_time').value = data.out || '';
                form.elements.namedItem('status').value = data.status || 'present';

                document.getElementById('correctionContext').textContent = `${data.student || 'Student'} · ${data.date || ''} · ${data.company || ''}`;
                document.getElementById('correctionNotes').textContent = data.notes ? `Existing notes: ${data.notes}` : '';
            });
        })();
    </script>
@endpush
