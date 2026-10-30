@php($current = $period ?? null)

<form method="POST" action="{{ $action }}">
    @csrf
    @method($method)

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="academic_year_id">Academic year</label>
            <select id="academic_year_id" name="academic_year_id" class="form-select @error('academic_year_id') is-invalid @enderror" required>
                <option value="">Select an academic year…</option>
                @foreach($academicYears as $year)
                    <option value="{{ $year->id }}"
                            data-start="{{ $year->start_date->toDateString() }}"
                            data-end="{{ $year->end_date->toDateString() }}"
                            @selected((int) old('academic_year_id', $current?->academic_year_id) === $year->id)>
                        {{ $year->name }}{{ $year->is_current ? ' (current)' : '' }} · {{ $year->start_date->format('d M Y') }} – {{ $year->end_date->format('d M Y') }}
                    </option>
                @endforeach
            </select>
            @error('academic_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="name">Period name</label>
            <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $current?->name) }}" placeholder="e.g. Semester I Internship 2025/2026" required maxlength="150">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

        <div class="col-md-6">
            <label class="form-label" for="status">Status</label>
            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                @foreach(\App\Enums\PeriodStatus::cases() as $case)
                    <option value="{{ $case->value }}" @selected(old('status', $current?->status?->value ?? 'draft') === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Only active periods allow placements to operate. Draft hides the period from normal operations; closed stops check-ins.</div>
        </div>
    </div>

    <div class="alert alert-info mt-3 mb-0" style="font-size:.78rem;">
        <i class="bi bi-info-circle me-1"></i>Periods in the same academic year cannot overlap, and all dates must stay inside the academic year. Placements already recorded in the period constrain how far the dates can be adjusted.
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>{{ $current ? 'Save period' : 'Create period' }}</button>
        <a href="{{ route('admin.periods.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

@push('scripts')
    <script>
        (() => {
            const year = document.getElementById('academic_year_id');
            const start = document.getElementById('start_date');
            const end = document.getElementById('end_date');
            if (!year || !start || !end) return;

            const applyBounds = () => {
                const option = year.selectedOptions[0];
                const yearStart = option?.dataset.start || '';
                const yearEnd = option?.dataset.end || '';
                start.min = yearStart;
                start.max = yearEnd;
                end.min = yearStart;
                end.max = yearEnd;
            };

            year.addEventListener('change', applyBounds);
            applyBounds();
        })();
    </script>
@endpush
