@extends('layouts.app')

@section('title', 'New Weekly Evaluation')
@section('page-title', 'New Weekly Evaluation')
@section('page-subtitle', 'Record this week\'s performance for a student at your company')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('supervisor.evaluations.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to evaluations</a>
    </div>

    @if($internships->isEmpty())
        <div class="card">
            <x-empty-state icon="bi-clipboard-x" title="No active placements" text="You can only evaluate students who are on an active placement assigned to you." />
        </div>
    @else
        <div class="card">
            <div class="card-header">Evaluation details</div>
            <div class="card-body">
                @include('supervisor.evaluations._form', [
                    'action' => route('supervisor.evaluations.store'),
                    'method' => 'POST',
                    'evaluation' => null,
                ])
            </div>
        </div>
    @endif
@endsection
