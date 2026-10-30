@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
    <div class="mb-4">
        <h2 class="h4 mb-1">Welcome back</h2>
        <p class="text-muted-2 mb-0" style="font-size:.84rem;">Sign in with your university internship account.</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2" style="font-size:.82rem;">
            @foreach($errors->all() as $error)
                <div><i class="bi bi-exclamation-circle me-1"></i>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
                <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username" placeholder="you@university.ac.ug">
            </div>
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between">
                <label for="password" class="form-label">Password</label>
                @if(Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-decoration-none" style="font-size:.76rem;">Forgot password?</a>
                @endif
            </div>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password" placeholder="Enter your password">
            </div>
        </div>

        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label" for="remember" style="font-size:.82rem;">Keep me signed in on this device</label>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
            Sign in <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </form>

    @if(app()->isLocal())
        <div class="mt-4 p-3 bg-light border rounded-3">
            <div class="section-label mb-2">Demo accounts</div>
            <div class="row g-1" style="font-size:.74rem;">
                <div class="col-6"><strong>Admin:</strong> admin@test.com</div>
                <div class="col-6"><strong>Student:</strong> student@test.com</div>
                <div class="col-6"><strong>Supervisor:</strong> supervisor@test.com</div>
                <div class="col-6"><strong>Lecturer:</strong> lecturer@test.com</div>
            </div>
            <div class="text-muted-2 mt-2" style="font-size:.7rem;">Password for all demo accounts: <code>password</code></div>
        </div>
    @endif
@endsection
