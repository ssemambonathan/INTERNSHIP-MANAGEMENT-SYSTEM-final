@extends('errors.minimal')

@section('code', '500')
@section('title', 'Something went wrong')
@section('icon')
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="9"></circle>
        <path d="M12 8v5"></path>
        <circle cx="12" cy="16.5" r="1"></circle>
    </svg>
@endsection
@section('message')
    <p>An unexpected error occurred while processing your request. Your data was not intentionally changed unless the action confirmed successfully.</p>
    <p>Try again. If the problem continues, report it to the system administrator with the time the error occurred.</p>
@endsection
@section('actions')
    <a class="btn btn-primary" href="{{ url('/dashboard') }}">Go to dashboard</a>
@endsection
