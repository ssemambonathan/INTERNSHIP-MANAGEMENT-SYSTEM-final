{{-- Expects: $action, $method, $log (nullable), $openDates, $dateValue --}}
<form method="POST" action="{{ $action }}">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-3">
        <div class="col-md-4">
            <label for="date" class="form-label required">Log date</label>
            @if($log)
                <input type="text" class="form-control" value="{{ $log->date->format('l, d F Y') }}" disabled>
                <input type="hidden" name="date" value="{{ $log->date->toDateString() }}">
                <div class="form-text">A log date cannot be changed after it is created.</div>
            @else
                <select id="date" name="date" class="form-select" required>
                    @foreach($openDates as $option)
                        <option value="{{ $option['value'] }}" @selected(old('date', $dateValue) === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
                <div class="form-text">Only open dates are listed. Other dates are locked to prevent back-filling.</div>
            @endif
        </div>

        <div class="col-md-3">
            <label for="hours_worked" class="form-label required">Hours worked</label>
            <div class="input-group">
                <input id="hours_worked" type="number" step="0.5" min="0.5" max="24" name="hours_worked" value="{{ old('hours_worked', $log?->hours_worked) }}" class="form-control" placeholder="e.g. 8">
                <span class="input-group-text">hrs</span>
            </div>
            <div class="form-text">Minimum 0.5, maximum 24.</div>
        </div>

        <div class="col-md-5 d-flex align-items-end">
            <div class="alert alert-light border py-2 mb-0 w-100" style="font-size:.78rem;">
                <i class="bi bi-info-circle me-1"></i>Be specific and honest. Your supervisor compares entries with the work you actually performed.
            </div>
        </div>

        <div class="col-12">
            <label for="activities_performed" class="form-label required">Activities performed</label>
            <textarea id="activities_performed" name="activities_performed" rows="4" class="form-control" placeholder="Describe the tasks you completed, tools used, and outputs produced." maxlength="5000">{{ old('activities_performed', $log?->activities_performed) }}</textarea>
        </div>

        <div class="col-md-6">
            <label for="skills_learned" class="form-label required">Skills learned</label>
            <textarea id="skills_learned" name="skills_learned" rows="3" class="form-control" placeholder="Technical and professional skills developed today." maxlength="5000">{{ old('skills_learned', $log?->skills_learned) }}</textarea>
        </div>

        <div class="col-md-6">
            <label for="challenges" class="form-label required">Challenges</label>
            <textarea id="challenges" name="challenges" rows="3" class="form-control" placeholder="What was difficult or unclear?" maxlength="5000">{{ old('challenges', $log?->challenges) }}</textarea>
        </div>

        <div class="col-md-6">
            <label for="solutions" class="form-label required">Solutions</label>
            <textarea id="solutions" name="solutions" rows="3" class="form-control" placeholder="How did you address the challenges?" maxlength="5000">{{ old('solutions', $log?->solutions) }}</textarea>
        </div>

        <div class="col-md-6">
            <label for="daily_reflection" class="form-label required">Daily reflection</label>
            <textarea id="daily_reflection" name="daily_reflection" rows="3" class="form-control" placeholder="What did today teach you about your profession?" maxlength="5000">{{ old('daily_reflection', $log?->daily_reflection) }}</textarea>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">
            <i class="bi bi-save me-1"></i>Save draft
        </button>
        <button type="submit" name="action" value="submit" class="btn btn-primary">
            <i class="bi bi-send me-1"></i>{{ $log ? 'Save and resubmit' : 'Submit for review' }}
        </button>
        <a href="{{ route('student.logs.index') }}" class="btn btn-link text-decoration-none">Cancel</a>
    </div>
</form>
