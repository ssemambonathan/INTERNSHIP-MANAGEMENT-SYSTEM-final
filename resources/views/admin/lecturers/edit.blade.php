@extends('layouts.app')

@section('title', 'Edit Lecturer')
@section('page-title', 'Edit Lecturer')
@section('page-subtitle', $lecturer->user->name . ' · ' . $lecturer->staff_number)

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.lecturers.show', $lecturer) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to lecturer</a>
    </div>

    <div class="card">
        <div class="card-header">Lecturer and account details</div>
        <div class="card-body">
            @include('admin.lecturers._form', [
                'action' => route('admin.lecturers.update', $lecturer),
                'method' => 'PUT',
                'lecturer' => $lecturer,
            ])
        </div>
    </div>
@endsection
