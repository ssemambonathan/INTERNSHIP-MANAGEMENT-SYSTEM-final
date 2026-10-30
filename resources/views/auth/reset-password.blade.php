@extends('layouts.guest')

@section('title', 'Choose a new password')

@section('content')
    <div class="mb-4">
        <h2 class="h4 mb-1">Set a new password</h2>
        <p class="text-muted-2 mb-0" style="font-size:.84rem;">Choose a strong password you have not used before.</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2" style="font-size:.82rem;">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">New password</label>
            <input id="password" type="password" name="password" class="form-control" required autocomplete="new-password">
        </div>
        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
        </div>
        <button class="btn btn-primary w-100 py-2 fw-semibold">Reset password</button>
    </form>
@endsection
