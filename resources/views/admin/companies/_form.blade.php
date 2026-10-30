@php($current = $company ?? null)

<form method="POST" action="{{ $action }}">
    @csrf
    @method($method)

    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label" for="name">Company name</label>
            <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $current?->name) }}" required maxlength="150">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="status">Status</label>
            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                @foreach(\App\Enums\CompanyStatus::cases() as $case)
                    <option value="{{ $case->value }}" @selected(old('status', $current?->status?->value ?? 'active') === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label" for="industry">Industry</label>
            <input id="industry" name="industry" type="text" class="form-control @error('industry') is-invalid @enderror" value="{{ old('industry', $current?->industry) }}" required maxlength="100">
            @error('industry')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="city">City / town</label>
            <input id="city" name="city" type="text" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $current?->city) }}" required maxlength="100">
            @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label" for="address">Physical address</label>
            <input id="address" name="address" type="text" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $current?->address) }}" required maxlength="255">
            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="phone">Phone</label>
            <input id="phone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $current?->phone) }}" maxlength="30">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="email">Email</label>
            <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $current?->email) }}" maxlength="150">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <hr class="my-4">

    <div class="section-label">Attendance geofence</div>
    <p class="text-muted-2" style="font-size:.8rem;">GPS attendance is verified against this location. Use decimal degrees (for Kampala, latitude is about 0.3476 and longitude about 32.5825).</p>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label" for="latitude">Latitude</label>
            <input id="latitude" name="latitude" type="number" step="0.0000001" min="-90" max="90" class="form-control @error('latitude') is-invalid @enderror" value="{{ old('latitude', $current?->latitude) }}" required>
            @error('latitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="longitude">Longitude</label>
            <input id="longitude" name="longitude" type="number" step="0.0000001" min="-180" max="180" class="form-control @error('longitude') is-invalid @enderror" value="{{ old('longitude', $current?->longitude) }}" required>
            @error('longitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="allowed_radius">Allowed radius (metres)</label>
            <input id="allowed_radius" name="allowed_radius" type="number" min="50" max="5000" class="form-control @error('allowed_radius') is-invalid @enderror" value="{{ old('allowed_radius', $current?->allowed_radius ?? settings('default_gps_radius', 150)) }}" required>
            @error('allowed_radius')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Check-ins outside this radius are flagged for supervisor review.</div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>{{ $current ? 'Save changes' : 'Register company' }}</button>
        <a href="{{ $current ? route('admin.companies.show', $current) : route('admin.companies.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
