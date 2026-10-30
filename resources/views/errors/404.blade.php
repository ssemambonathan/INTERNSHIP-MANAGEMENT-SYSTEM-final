@extends('errors.minimal')

@section('code', '404')
@section('title', 'Page not found')
@section('icon')
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="11" cy="11" r="7"></circle>
        <path d="m20 20-3.5-3.5"></path>
        <path d="M8 11h6"></path>
    </svg>
@endsection
@section('message')
    <p>The page you are looking for does not exist, has been moved, or the record is no longer available.</p>
    <p>Check the address or return to your dashboard to continue working.</p>
@endsection
@section('actions')
    <a class="btn btn-primary" href="{{ url('/dashboard') }}">Go to dashboard</a>
@endsection
