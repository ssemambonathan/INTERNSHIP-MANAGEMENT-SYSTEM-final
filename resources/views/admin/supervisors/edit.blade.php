@extends('layouts.app')

@section('title', 'Edit Company Supervisor')
@section('page-title', 'Edit Company Supervisor')
@section('page-subtitle', $supervisor->user->name)

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.supervisors.show', $supervisor) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to supervisor</a>
    </div>

    <div class="card">
        <div class="card-header">Supervisor and account details</div>
        <div class="card-body">
            @include('admin.supervisors._form', [
                'action' => route('admin.supervisors.update', $supervisor),
                'method' => 'PUT',
                'supervisor' => $supervisor,
            ])
        </div>
    </div>
@endsection
