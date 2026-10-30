@extends('layouts.app')

@section('title', 'Add Student')
@section('page-title', 'Add Student')
@section('page-subtitle', 'Create a student account linked to the academic structure')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('admin.students.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to students</a>
        <a href="{{ route('admin.students.import.form') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-filetype-csv me-1"></i>Import CSV instead</a>
    </div>

    <div class="card">
        <div class="card-header">Student and account details</div>
        <div class="card-body">
            @include('admin.students._form', [
                'action' => route('admin.students.store'),
                'method' => 'POST',
                'student' => null,
            ])
        </div>
    </div>
@endsection
