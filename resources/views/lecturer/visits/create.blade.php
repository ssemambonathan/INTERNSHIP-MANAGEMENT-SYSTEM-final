@extends('layouts.app')

@section('title', 'Schedule Visit')
@section('page-title', 'Schedule Supervision Visit')
@section('page-subtitle', 'Plan a site visit for one of your active placements')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('lecturer.visits.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to visits</a>
    </div>

    @if($internships->isEmpty())
        <div class="card">
            <x-empty-state icon="bi-pin-map" title="No active placements" text="Visits can only be scheduled for students on an active placement assigned to you." />
        </div>
    @else
        <div class="card">
            <div class="card-header">Visit details</div>
            <div class="card-body">
                @include('lecturer.visits._form', [
                    'action' => route('lecturer.visits.store'),
                    'method' => 'POST',
                    'visit' => null,
                ])
            </div>
        </div>
    @endif
@endsection
