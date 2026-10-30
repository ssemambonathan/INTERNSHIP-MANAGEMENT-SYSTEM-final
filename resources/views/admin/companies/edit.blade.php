@extends('layouts.app')

@section('title', 'Edit Company')
@section('page-title', 'Edit Company')
@section('page-subtitle', $company->name)

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.companies.show', $company) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to company</a>
    </div>

    <div class="card">
        <div class="card-header">Company details</div>
        <div class="card-body">
            @include('admin.companies._form', [
                'action' => route('admin.companies.update', $company),
                'method' => 'PUT',
                'company' => $company,
            ])
        </div>
    </div>
@endsection
