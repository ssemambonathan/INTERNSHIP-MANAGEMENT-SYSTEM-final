@extends('layouts.guest')

@section('title', 'Reset password')

@section('content')
    <div class="mb-4">
        <h2 class="h4 mb-1">Forgot your password?</h2>
        <p class="text-muted-2 mb-0" style="font-size:.84rem;">Enter your email address and we will send you a secure reset link.</p>
    </div>

    @if(session('status'))
        <div class="alert alert-success py-2" style="font-size:.82rem;">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger py-2" style="font-size:.82rem;">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-4">
            <label for="email" class="form-label">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus placeholder="you@university.ac.ug">
        </div>
        <button class="btn btn-primary w-100 py-2 fw-semibold">Send reset link</button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('login') }}" class="text-decoration-none" style="font-size:.82rem;"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a>
    </div>
@endsection
