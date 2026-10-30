@extends('errors.minimal')

@section('code', '503')
@section('title', 'Temporarily unavailable')
@section('icon')
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M14.7 6.3a4.5 4.5 0 0 0 5.9 5.9l-8.4 8.4a2.1 2.1 0 0 1-3-3l8.4-8.4a4.5 4.5 0 0 1-2.9-2.9Z"></path>
        <path d="m5 5 4 4"></path>
    </svg>
@endsection
@section('message')
    <p>InternTrack is temporarily unavailable for maintenance or is experiencing high load.</p>
    <p>Please try again in a few minutes. Attendance and logbook data already saved remains secure.</p>
@endsection
@section('actions')
    <a class="btn btn-primary" href="{{ url('/') }}">Try again</a>
@endsection
