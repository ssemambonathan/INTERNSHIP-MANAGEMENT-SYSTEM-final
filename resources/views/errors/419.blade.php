@extends('errors.minimal')

@section('code', '419')
@section('title', 'Session expired')
@section('icon')
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="9"></circle>
        <path d="M12 7v5l3 2"></path>
    </svg>
@endsection
@section('message')
    <p>Your session has expired for security reasons, or the page was left open for too long.</p>
    <p>Please sign in again to continue. Any unsaved form changes will need to be re-entered.</p>
@endsection
@section('actions')
    <a class="btn btn-primary" href="{{ url('/login') }}">Sign in again</a>
@endsection
