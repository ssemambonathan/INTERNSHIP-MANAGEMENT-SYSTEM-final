@extends('layouts.app')

@section('title', 'System Settings')
@section('page-title', 'System Settings')
@section('page-subtitle', 'University identity, working week and operational thresholds')

@section('content')
    @php
        $workingDays = array_map('intval', (array) old('settings.working_days', settings('working_days', ['1','2','3','4','5'])));
        $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="section-label">Operational configuration</div>
            <div class="text-muted-2" style="font-size:.82rem;">Changes apply to future attendance, logbook and evaluation activity. Historical records keep the values they were recorded with.</div>
        </div>
        <span class="badge badge-soft-secondary"><i class="bi bi-clock-history me-1"></i>Changes are audited</span>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><span><i class="bi bi-building me-2"></i>University identity</span><span class="text-muted-2 fw-normal" style="font-size:.72rem;">Shown in reports and emails</span></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="university_name">University name</label>
                            <input id="university_name" name="settings[university_name]" class="form-control @error('settings.university_name') is-invalid @enderror" value="{{ old('settings.university_name', settings('university_name')) }}" required maxlength="150">
                            @error('settings.university_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="university_address">Postal / physical address</label>
                            <input id="university_address" name="settings[university_address]" class="form-control @error('settings.university_address') is-invalid @enderror" value="{{ old('settings.university_address', settings('university_address')) }}" required maxlength="255">
                            @error('settings.university_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row g-3">
                            <div class="col-sm-7">
                                <label class="form-label" for="university_email">Contact email</label>
                                <input id="university_email" type="email" name="settings[university_email]" class="form-control @error('settings.university_email') is-invalid @enderror" value="{{ old('settings.university_email', settings('university_email')) }}" required maxlength="150">
                                @error('settings.university_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-5">
                                <label class="form-label" for="university_phone">Phone</label>
                                <input id="university_phone" name="settings[university_phone]" class="form-control @error('settings.university_phone') is-invalid @enderror" value="{{ old('settings.university_phone', settings('university_phone')) }}" maxlength="30">
                                @error('settings.university_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><span><i class="bi bi-calendar-week me-2"></i>Working week</span></div>
                    <div class="card-body">
                        <label class="form-label">Working days</label>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            @foreach($dayNames as $value => $label)
                                <div class="form-check form-check-inline border rounded px-3 py-2 me-0" style="font-size:.8rem;">
                                    <input class="form-check-input" type="checkbox" id="day_{{ $value }}" name="settings[working_days][]" value="{{ $value }}" @checked(in_array($value, $workingDays, true))>
                                    <label class="form-check-label" for="day_{{ $value }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                        @error('settings.working_days')<div class="text-danger" style="font-size:.76rem;">{{ $message }}</div>@enderror
                        @error('settings.working_days.*')<div class="text-danger" style="font-size:.76rem;">{{ $message }}</div>@enderror
                        <div class="form-text mb-3">Working days drive expected attendance, eligibility for daily logs and missed-log detection.</div>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label" for="working_start_time">Expected start time</label>
                                <input id="working_start_time" type="time" name="settings[working_start_time]" class="form-control @error('settings.working_start_time') is-invalid @enderror" value="{{ old('settings.working_start_time', settings('working_start_time', '08:00')) }}" required>
                                @error('settings.working_start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="working_end_time">Expected end time</label>
                                <input id="working_end_time" type="time" name="settings[working_end_time]" class="form-control @error('settings.working_end_time') is-invalid @enderror" value="{{ old('settings.working_end_time', settings('working_end_time', '17:00')) }}" required>
                                @error('settings.working_end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label" for="minimum_working_hours">Minimum working hours per day</label>
                            <input id="minimum_working_hours" type="number" step="0.5" min="1" max="16" name="settings[minimum_working_hours]" class="form-control @error('settings.minimum_working_hours') is-invalid @enderror" value="{{ old('settings.minimum_working_hours', settings('minimum_working_hours', 7.5)) }}" required>
                            @error('settings.minimum_working_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><span><i class="bi bi-geo-alt me-2"></i>Attendance &amp; geofencing</span></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label" for="late_after">Late after</label>
                                <input id="late_after" type="time" name="settings[late_after]" class="form-control @error('settings.late_after') is-invalid @enderror" value="{{ old('settings.late_after', settings('late_after', '08:30')) }}" required>
                                @error('settings.late_after')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">Must sit between the expected start and end time.</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="default_gps_radius">Default geofence radius (m)</label>
                                <input id="default_gps_radius" type="number" min="50" max="5000" name="settings[default_gps_radius]" class="form-control @error('settings.default_gps_radius') is-invalid @enderror" value="{{ old('settings.default_gps_radius', settings('default_gps_radius', 150)) }}" required>
                                @error('settings.default_gps_radius')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">Used as the default when registering a company.</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="location_accuracy_warning">GPS accuracy warning (m)</label>
                                <input id="location_accuracy_warning" type="number" min="10" max="1000" name="settings[location_accuracy_warning]" class="form-control @error('settings.location_accuracy_warning') is-invalid @enderror" value="{{ old('settings.location_accuracy_warning', settings('location_accuracy_warning', 100)) }}" required>
                                @error('settings.location_accuracy_warning')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">Check-ins with a weaker GPS accuracy are flagged.</div>
                            </div>
                        </div>
                        <div class="alert alert-info mt-3 mb-0" style="font-size:.77rem;">
                            <i class="bi bi-info-circle me-1"></i>A company's own radius overrides the default. Lowering defaults does not alter existing attendance records.
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><span><i class="bi bi-paperclip me-2"></i>Evidence uploads</span></div>
                    <div class="card-body">
                        <label class="form-label" for="max_upload_size_kb">Maximum file size (KB)</label>
                        <input id="max_upload_size_kb" type="number" min="1024" max="20480" step="1024" name="settings[max_upload_size_kb]" class="form-control @error('settings.max_upload_size_kb') is-invalid @enderror" value="{{ old('settings.max_upload_size_kb', settings('max_upload_size_kb', 10240)) }}" required>
                        @error('settings.max_upload_size_kb')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text" id="uploadSizeHint">Allowed: PDF, DOCX, JPG, JPEG and PNG. Files are stored outside the database with generated names.</div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between py-1 border-bottom" style="font-size:.78rem;"><span class="text-muted-2">Maximum size</span><span id="uploadSizeDisplay">{{ number_format(settings('max_upload_size_kb', 10240) / 1024, 0) }} MB</span></div>
                            <div class="d-flex justify-content-between py-1" style="font-size:.78rem;"><span class="text-muted-2">Allowed types</span><span>PDF, DOCX, JPG, JPEG, PNG</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><span><i class="bi bi-clipboard-data me-2"></i>Weekly evaluation</span></div>
                    <div class="card-body">
                        <label class="form-label" for="evaluation_scale">Maximum score per criterion</label>
                        <input id="evaluation_scale" type="number" min="3" max="10" name="settings[evaluation_scale]" class="form-control @error('settings.evaluation_scale') is-invalid @enderror" value="{{ old('settings.evaluation_scale', settings('evaluation_scale', 5)) }}" required>
                        @error('settings.evaluation_scale')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Applied to the six criteria: technical skills, communication, professionalism, teamwork, punctuality and initiative.</div>
                        <div class="mt-3 p-3 rounded" style="background:#f7f9fc;font-size:.78rem;">
                            <div class="text-muted-2 mb-1">Current scale</div>
                            <div class="fw-semibold">1 – {{ old('settings.evaluation_scale', settings('evaluation_scale', 5)) }} per criterion</div>
                            <div class="text-muted-2">Average across six criteria is stored with each weekly evaluation.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><span><i class="bi bi-bell me-2"></i>Notifications</span></div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="mail_notifications_enabled" name="settings[mail_notifications_enabled]" value="1" @checked(old('settings.mail_notifications_enabled', settings('mail_notifications_enabled', true)))>
                            <label class="form-check-label" for="mail_notifications_enabled">Send email notifications</label>
                        </div>
                        <div class="form-text mb-3">In-app notifications are always recorded. Email delivery requires a configured mail transport in the deployment environment.</div>
                        <div class="alert alert-warning mb-0" style="font-size:.77rem;">
                            <i class="bi bi-shield-lock me-1"></i>Mail credentials are never stored in this application or shown in this interface. Configure the mail transport through environment variables.
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="text-muted-2" style="font-size:.78rem;"><i class="bi bi-clock-history me-1"></i>Settings are validated on the server and recorded in the audit log.</div>
                        <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save settings</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (() => {
            const input = document.getElementById('max_upload_size_kb');
            const display = document.getElementById('uploadSizeDisplay');
            if (!input || !display) return;

            input.addEventListener('input', () => {
                const kb = Number(input.value);
                display.textContent = Number.isFinite(kb) && kb > 0 ? `${Math.round(kb / 1024)} MB` : '—';
            });
        })();
    </script>
@endpush
