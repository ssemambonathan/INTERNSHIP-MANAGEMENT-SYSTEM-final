@extends('errors.minimal')

@section('code', '429')
@section('title', 'Too many requests')
@section('icon')
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 8v5"></path>
        <circle cx="12" cy="16.5" r="1"></circle>
        <path d="M10.3 3.8 2.6 17a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 3.8a2 2 0 0 0-3.4 0Z"></path>
    </svg>
@endsection
@section('message')
    <p>You have made too many requests in a short period. This limit protects attendance and account security.</p>
    <p>Wait a minute, then try the action again.</p>
@endsection
@section('actions')
    <a class="btn btn-primary" href="{{ url('/dashboard') }}">Go to dashboard</a>
@endsection
