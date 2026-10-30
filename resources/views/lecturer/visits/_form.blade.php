@php($isEdit = isset($visit) && $visit !== null)

<form method="POST" action="{{ $action }}">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-3">
        @if($isEdit)
            <div class="col-md-6">
                <div class="section-label">Student</div>
                <div class="fw-semibold">{{ $visit->internship->student->user->name }}</div>
                <div class="text-muted-2" style="font-size:.76rem;">{{ $visit->internship->student->registration_number }} · {{ $visit->internship->company?->name }}</div>
            </div>
        @else
            <div class="col-md-6">
                <label for="internship_id" class="form-label">Student placement <span class="text-danger">*</span></label>
                <select id="internship_id" name="internship_id" class="form-select" required>
                    <option value="">Select an active student</option>
                    @foreach($internships as $internship)
                        <option value="{{ $internship->id }}" @selected((int) old('internship_id', $internshipId) === $internship->id)>
                            {{ $internship->student->user->name }} — {{ $internship->company?->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-md-3">
            <label for="visit_date" class="form-label">Visit date <span class="text-danger">*</span></label>
            <input id="visit_date" type="date" name="visit_date" value="{{ old('visit_date', $isEdit ? $visit->visit_date->format('Y-m-d') : now()->addDays(3)->format('Y-m-d')) }}" min="{{ today()->format('Y-m-d') }}" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label for="purpose" class="form-label">Purpose <span class="text-danger">*</span></label>
            <input id="purpose" type="text" name="purpose" value="{{ old('purpose', $visit?->purpose) }}" maxlength="500" class="form-control" placeholder="e.g. Mid-placement review" required>
        </div>
    </div>

    <div class="alert alert-info mt-3 mb-0" style="font-size:.8rem;">
        <i class="bi bi-info-circle me-1"></i>The student is notified when a visit is scheduled or rescheduled. Visit outcomes and evidence are added after the visit.
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3">
        <button class="btn btn-primary"><i class="bi bi-save me-1"></i>{{ $isEdit ? 'Update visit' : 'Schedule visit' }}</button>
        <a href="{{ $isEdit ? route('lecturer.visits.show', $visit) : route('lecturer.visits.index') }}" class="btn btn-light border">Cancel</a>
    </div>
</form>
