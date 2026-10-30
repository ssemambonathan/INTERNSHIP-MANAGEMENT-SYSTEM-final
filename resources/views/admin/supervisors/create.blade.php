@extends('layouts.app')

@section('title', 'Add Company Supervisor')
@section('page-title', 'Add Company Supervisor')
@section('page-subtitle', 'Create a workplace supervisor account and attach it to a host company')

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.supervisors.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to supervisors</a>
    </div>

    <div class="card">
        <div class="card-header">Supervisor and account details</div>
        <div class="card-body">
            @include('admin.supervisors._form', [
                'action' => route('admin.supervisors.store'),
                'method' => 'POST',
                'supervisor' => null,
            ])
        </div>
    </div>
@endsection
