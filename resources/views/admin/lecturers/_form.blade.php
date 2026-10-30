@php
    $current = $lecturer ?? null;
    $currentUser = $current?->user;
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @method($method)

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="name">Full name</label>
            <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $currentUser?->name) }}" required maxlength="120">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="email">Email address</label>
            <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $currentUser?->email) }}" required maxlength="150">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="phone">Phone number</label>
            <input id="phone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $currentUser?->phone) }}" maxlength="30">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="status">Account status</label>
            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                @foreach(\App\Enums\UserStatus::cases() as $case)
                    <option value="{{ $case->value }}" @selected(old('status', $currentUser?->status?->value ?? 'active') === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="staff_number">Staff number</label>
            <input id="staff_number" name="staff_number" type="text" class="form-control @error('staff_number') is-invalid @enderror" value="{{ old('staff_number', $current?->staff_number) }}" required maxlength="40">
            @error('staff_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="position">Position</label>
            <input id="position" name="position" type="text" class="form-control @error('position') is-invalid @enderror" value="{{ old('position', $current?->position) }}" maxlength="80" placeholder="e.g. Senior Lecturer">
            @error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="department_id">Department</label>
            <select id="department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                <option value="">Select department</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) old('department_id', $current?->department_id) === (string) $department->id)>{{ $department->name }} — {{ $department->faculty?->code }}</option>
                @endforeach
            </select>
            @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="password">{{ $current ? 'Reset password (optional)' : 'Temporary password' }}</label>
            <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" {{ $current ? '' : 'required' }} autocomplete="new-password" minlength="8">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @if($current)<div class="form-text">Leave blank to keep the current password.</div>@endif
        </div>
        <div class="col-md-6">
            <label class="form-label" for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" {{ $current ? '' : 'required' }} autocomplete="new-password">
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>{{ $current ? 'Save changes' : 'Create lecturer' }}</button>
        <a href="{{ $current ? route('admin.lecturers.show', $current) : route('admin.lecturers.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
