@php
    $current = $supervisor ?? null;
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
        <div class="col-md-7">
            <label class="form-label" for="company_id">Company</label>
            <select id="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                <option value="">Select company</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) old('company_id', $current?->company_id) === (string) $company->id)>{{ $company->name }}{{ $company->status === \App\Enums\CompanyStatus::Inactive ? ' (inactive)' : '' }}</option>
                @endforeach
            </select>
            @error('company_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @if($current)
                <div class="form-text">The company cannot be changed while this supervisor has active or pending placements.</div>
            @endif
        </div>
        <div class="col-md-5">
            <label class="form-label" for="position">Position</label>
            <input id="position" name="position" type="text" class="form-control @error('position') is-invalid @enderror" value="{{ old('position', $current?->position) }}" maxlength="80" placeholder="e.g. Engineering Lead">
            @error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
        <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>{{ $current ? 'Save changes' : 'Create supervisor' }}</button>
        <a href="{{ $current ? route('admin.supervisors.show', $current) : route('admin.supervisors.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
