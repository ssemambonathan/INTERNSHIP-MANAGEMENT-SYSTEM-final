@extends('layouts.app')

@section('title', 'Edit Student')
@section('page-title', 'Edit Student')
@section('page-subtitle', $student->user->name . ' · ' . $student->registration_number)

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('admin.students.show', $student) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to profile</a>
        <span class="badge badge-soft-{{ $student->user->status->badgeClass() }}">{{ $student->user->status->label() }}</span>
    </div>

    <div class="card">
        <div class="card-header">Student and account details</div>
        <div class="card-body">
            @include('admin.students._form', [
                'action' => route('admin.students.update', $student),
                'method' => 'PUT',
                'student' => $student,
            ])
        </div>
    </div>
@endsection
