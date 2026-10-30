@extends('layouts.app')

@section('title', 'Edit Visit')
@section('page-title', 'Edit Supervision Visit')
@section('page-subtitle', $visit->internship->student->user->name . ' · ' . $visit->visit_date->format('d M Y'))

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('lecturer.visits.show', $visit) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to visit</a>
        <x-status-badge :status="$visit->status" />
    </div>

    <div class="card">
        <div class="card-header">Visit details</div>
        <div class="card-body">
            @include('lecturer.visits._form', [
                'action' => route('lecturer.visits.update', $visit),
                'method' => 'PUT',
            ])
        </div>
    </div>
@endsection
