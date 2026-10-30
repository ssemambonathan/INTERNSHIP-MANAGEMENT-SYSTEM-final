@extends('errors.minimal')

@section('code', '403')
@section('title', 'Access denied')
@section('icon')
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="4" y="10" width="16" height="11" rx="2"></rect>
        <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
        <circle cx="12" cy="16" r="1"></circle>
    </svg>
@endsection
@section('message')
    <p>You do not have permission to view this page or perform this action.</p>
    <p>Access is limited to your assigned role. If you believe this is a mistake, contact the internship coordinator so your account or assignment can be reviewed.</p>
@endsection
@section('actions')
    <a class="btn btn-primary" href="{{ url('/dashboard') }}">Go to dashboard</a>
@endsection
