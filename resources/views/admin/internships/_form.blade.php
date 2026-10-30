@php($current = $internship ?? null)

<form method="POST" action="{{ $action }}">
    @csrf
    @method($method)

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="student_id">Student</label>
            <select id="student_id" name="student_id" class="form-select @error('student_id') is-invalid @enderror" required>
                <option value="">Select a student…</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}" @selected((int) old('student_id', $current?->student_id) === $student->id)>
                        {{ $student->user?->name }} — {{ $student->registration_number }}{{ $student->department ? ' ('.$student->department->code.')' : '' }}
                    </option>
                @endforeach
            </select>
            @error('student_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="internship_period_id">Internship period</label>
            <select id="internship_period_id" name="internship_period_id" class="form-select @error('internship_period_id') is-invalid @enderror" required>
                <option value="">Select a period…</option>
                @foreach($periods as $period)
                    <option value="{{ $period->id }}"
                            data-start="{{ $period->start_date->toDateString() }}"
                            data-end="{{ $period->end_date->toDateString() }}"
                            data-status="{{ $period->status->value }}"
                            @selected((int) old('internship_period_id', $current?->internship_period_id) === $period->id)>
                        {{ $period->name }} · {{ $period->academicYear?->name }} ({{ $period->status->label() }})
                    </option>
                @endforeach
            </select>
            @error('internship_period_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Placement dates must sit inside the period.</div>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="company_id">Company</label>
            <select id="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                <option value="">Select a company…</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected((int) old('company_id', $current?->company_id) === $company->id)>
                        {{ $company->name }} — {{ $company->city }}{{ $company->isActive() ? '' : ' (inactive)' }}
                    </option>
                @endforeach
            </select>
            @error('company_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="company_supervisor_id">Company supervisor</label>
            <select id="company_supervisor_id" name="company_supervisor_id" class="form-select @error('company_supervisor_id') is-invalid @enderror" required>
                <option value="">Select a supervisor…</option>
                @foreach($supervisors as $supervisor)
                    <option value="{{ $supervisor->id }}"
                            data-company="{{ $supervisor->company_id }}"
                            @selected((int) old('company_supervisor_id', $current?->company_supervisor_id) === $supervisor->id)>
                        {{ $supervisor->user?->name }} — {{ $supervisor->company?->name }}
                    </option>
                @endforeach
            </select>
            @error('company_supervisor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">The list is limited to supervisors at the selected company.</div>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="lecturer_id">University lecturer</label>
            <select id="lecturer_id" name="lecturer_id" class="form-select @error('lecturer_id') is-invalid @enderror" required>
                <option value="">Select a lecturer…</option>
                @foreach($lecturers as $lecturer)
                    <option value="{{ $lecturer->id }}" @selected((int) old('lecturer_id', $current?->lecturer_id) === $lecturer->id)>
                        {{ $lecturer->user?->name }}{{ $lecturer->staff_number ? ' — '.$lecturer->staff_number : '' }}
                    </option>
                @endforeach
            </select>
            @error('lecturer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="status">Placement status</label>
            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                @foreach(\App\Enums\InternshipStatus::cases() as $case)
                    <option value="{{ $case->value }}" @selected(old('status', $current?->status?->value ?? 'pending') === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">A placement can only be active while the company, period and staff accounts are active.</div>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="start_date">Start date</label>
            <input id="start_date" name="start_date" type="date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', $current?->start_date?->toDateString()) }}" required>
            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="end_date">End date</label>
            <input id="end_date" name="end_date" type="date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', $current?->end_date?->toDateString()) }}" required>
            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <label class="form-label" for="notes">Placement notes <span class="text-muted-2 fw-normal">(optional)</span></label>
            <textarea id="notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" maxlength="2000" placeholder="Duties, reporting instructions or special arrangements">{{ old('notes', $current?->notes) }}</textarea>
            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>{{ $current ? 'Save placement' : 'Create placement' }}</button>
        <a href="{{ $current ? route('admin.internships.show', $current) : route('admin.internships.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

@push('scripts')
    <script>
        (() => {
            const company = document.getElementById('company_id');
            const supervisor = document.getElementById('company_supervisor_id');
            const period = document.getElementById('internship_period_id');
            const start = document.getElementById('start_date');
            const end = document.getElementById('end_date');
            if (!company || !supervisor) return;

            const supervisorOptions = Array.from(supervisor.querySelectorAll('option[data-company]'));

            const filterSupervisors = () => {
                const companyId = company.value;
                supervisorOptions.forEach(option => {
                    const related = companyId !== '' && option.dataset.company === companyId;
                    option.hidden = !related;
                    option.disabled = !related;
                });

                const selected = supervisor.selectedOptions[0];
                if (!selected || selected.disabled) {
                    supervisor.value = '';
                }
            };

            const applyPeriodBounds = () => {
                if (!period || !start || !end) return;
                const option = period.selectedOptions[0];
                const periodStart = option?.dataset.start || '';
                const periodEnd = option?.dataset.end || '';

                start.min = periodStart;
                start.max = periodEnd;
                end.min = periodStart;
                end.max = periodEnd;
            };

            company.addEventListener('change', filterSupervisors);
            period?.addEventListener('change', applyPeriodBounds);
            filterSupervisors();
            applyPeriodBounds();
        })();
    </script>
@endpush
