@extends('layouts.app')

@section('title', 'Add Lecturer')
@section('page-title', 'Add Lecturer')
@section('page-subtitle', 'Create an academic supervisor account linked to a department')

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.lecturers.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to lecturers</a>
    </div>

    <div class="card">
        <div class="card-header">Lecturer and account details</div>
        <div class="card-body">
            @include('admin.lecturers._form', [
                'action' => route('admin.lecturers.store'),
                'method' => 'POST',
                'lecturer' => null,
            ])
        </div>
    </div>
@endsection
