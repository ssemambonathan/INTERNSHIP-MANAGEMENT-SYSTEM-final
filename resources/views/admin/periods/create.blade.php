@extends('layouts.app')

@section('title', 'New Period')
@section('page-title', 'New Internship Period')
@section('page-subtitle', 'Define the dates that placements must stay inside')

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.periods.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to periods</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">Period details</div>
                <div class="card-body">
                    @include('admin.periods._form', [
                        'action' => route('admin.periods.store'),
                        'method' => 'POST',
                        'period' => null,
                    ])
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">How periods are used</div>
                <div class="card-body text-muted-2" style="font-size:.8rem;line-height:1.7;">
                    <p class="mb-2">Every placement is tied to one period. The period controls when the placement may operate, and period dates must contain the placement dates.</p>
                    <p class="mb-2">Only one period can be <strong>active</strong> for a given date range. Activating an overlapping period is refused.</p>
                    <p class="mb-0">Closing a period stops new attendance check-ins without deleting any records.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
