@php
    $scale = (int) settings('evaluation_scale', 5);
    $isEdit = isset($evaluation) && $evaluation !== null;
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-3 mb-4">
        @if($isEdit)
            <div class="col-md-6">
                <div class="section-label">Student</div>
                <div class="fw-semibold">{{ $evaluation->internship->student->user->name }}</div>
                <div class="text-muted-2" style="font-size:.75rem;">{{ $evaluation->internship->student->registration_number }} · {{ $evaluation->internship->company?->name }}</div>
            </div>
            <div class="col-md-3">
                <div class="section-label">Week</div>
                <div class="fw-semibold">{{ $evaluation->week_start->format('d M') }} – {{ $evaluation->week_end->format('d M Y') }}</div>
            </div>
        @else
            <div class="col-md-6">
                <label for="internship_id" class="form-label">Student placement <span class="text-danger">*</span></label>
                <select id="internship_id" name="internship_id" class="form-select" required>
                    <option value="">Select an active student</option>
                    @foreach($internships as $internship)
                        <option value="{{ $internship->id }}" @selected((int) old('internship_id', $internshipId) === $internship->id)>
                            {{ $internship->student->user->name }} — {{ $internship->student->registration_number }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="week_start" class="form-label">Week of <span class="text-danger">*</span></label>
                <input id="week_start" type="date" name="week_start" value="{{ old('week_start', $weekStart->format('Y-m-d')) }}" class="form-control" required>
                <div class="form-text">Any date in the week; it is normalised to Monday.</div>
            </div>
        @endif
        <div class="col-md-3">
            <div class="section-label">Scale</div>
            <div class="fw-semibold">1 – {{ $scale }}</div>
            <div class="text-muted-2" style="font-size:.75rem;">1 = needs significant improvement, {{ $scale }} = outstanding</div>
        </div>
    </div>

    <div class="row g-3">
        @foreach(\App\Models\Evaluation::$criteria as $field => $label)
            <div class="col-md-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0 fw-semibold">{{ $label }} <span class="text-danger">*</span></label>
                        <span class="text-muted-2" style="font-size:.72rem;">Score 1 – {{ $scale }}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2" role="group" aria-label="{{ $label }} score">
                        @for($score = 1; $score <= $scale; $score++)
                            <input type="radio" class="btn-check" name="{{ $field }}" id="{{ $field }}_{{ $score }}" value="{{ $score }}" autocomplete="off" @checked((int) old($field, $evaluation?->{$field}) === $score) required>
                            <label class="btn btn-outline-primary btn-sm evaluation-score" for="{{ $field }}_{{ $score }}">{{ $score }}</label>
                        @endfor
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mt-1">
        <div class="col-md-6">
            <label for="strengths" class="form-label">Key strengths</label>
            <textarea id="strengths" name="strengths" rows="3" maxlength="3000" class="form-control" placeholder="What did the student do particularly well this week?">{{ old('strengths', $evaluation?->strengths) }}</textarea>
        </div>
        <div class="col-md-6">
            <label for="areas_for_improvement" class="form-label">Areas for improvement</label>
            <textarea id="areas_for_improvement" name="areas_for_improvement" rows="3" maxlength="3000" class="form-control" placeholder="What should the student focus on next?">{{ old('areas_for_improvement', $evaluation?->areas_for_improvement) }}</textarea>
        </div>
        <div class="col-md-6">
            <label for="remarks" class="form-label">Remarks</label>
            <textarea id="remarks" name="remarks" rows="3" maxlength="3000" class="form-control" placeholder="Additional observations for the lecturer">{{ old('remarks', $evaluation?->remarks) }}</textarea>
        </div>
        <div class="col-md-6">
            <label for="recommendation" class="form-label">Overall recommendation</label>
            <select id="recommendation" name="recommendation" class="form-select">
                <option value="">No recommendation</option>
                @foreach(['Recommended', 'Recommended with reservations', 'Not recommended'] as $option)
                    <option value="{{ $option }}" @selected(old('recommendation', $evaluation?->recommendation) === $option)>{{ $option }}</option>
                @endforeach
            </select>
            <div class="form-text">Visible to the student and their lecturer.</div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button class="btn btn-primary" @disabled(! $isEdit && isset($internships) && $internships->isEmpty())><i class="bi bi-save me-1"></i>{{ $isEdit ? 'Update evaluation' : 'Save evaluation' }}</button>
        <a href="{{ $isEdit ? route('supervisor.evaluations.show', $evaluation) : route('supervisor.evaluations.index') }}" class="btn btn-light border">Cancel</a>
    </div>
</form>
