@extends('layouts.app')

@section('title', 'Edit Evaluation')
@section('page-title', 'Edit Weekly Evaluation')
@section('page-subtitle', $evaluation->internship->student->user->name . ' · week of ' . $evaluation->week_start->format('d M Y'))

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('supervisor.evaluations.show', $evaluation) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to evaluation</a>
        <span class="badge badge-soft-info">{{ $evaluation->week_start->format('d M') }} – {{ $evaluation->week_end->format('d M Y') }}</span>
    </div>

    <div class="card">
        <div class="card-header">Update scores and feedback</div>
        <div class="card-body">
            @include('supervisor.evaluations._form', [
                'action' => route('supervisor.evaluations.update', $evaluation),
                'method' => 'PUT',
            ])
        </div>
    </div>
@endsection
