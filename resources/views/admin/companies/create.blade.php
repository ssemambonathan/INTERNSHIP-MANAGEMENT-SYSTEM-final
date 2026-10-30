@extends('layouts.app')

@section('title', 'Register Company')
@section('page-title', 'Register Company')
@section('page-subtitle', 'Add a host organisation and configure its attendance geofence')

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.companies.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to companies</a>
    </div>

    <div class="card">
        <div class="card-header">Company details</div>
        <div class="card-body">
            @include('admin.companies._form', [
                'action' => route('admin.companies.store'),
                'method' => 'POST',
                'company' => null,
            ])
        </div>
    </div>
@endsection
