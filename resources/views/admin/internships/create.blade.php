@extends('layouts.app')

@section('title', 'New Placement')
@section('page-title', 'New Internship Placement')
@section('page-subtitle', 'Assign a student to a company, supervisor and lecturer')

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.internships.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to placements</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">Placement details</div>
                <div class="card-body">
                    @include('admin.internships._form', [
                        'action' => route('admin.internships.store'),
                        'method' => 'POST',
                        'internship' => null,
                    ])
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Before you create a placement</div>
                <div class="card-body">
                    <ul class="mb-0 ps-3 text-muted-2" style="font-size:.8rem;line-height:1.7;">
                        <li>The student, company, supervisor and lecturer must already exist.</li>
                        <li>Only supervisors attached to the selected company can be assigned.</li>
                        <li>The placement dates must fall within the selected internship period.</li>
                        <li>A student cannot have two active or pending placements with overlapping dates.</li>
                        <li>Attendance is checked against the company's saved GPS coordinates and radius.</li>
                    </ul>
                </div>
            </div>
            <div class="card">
                <div class="card-header">After creating</div>
                <div class="card-body text-muted-2" style="font-size:.8rem;line-height:1.7;">
                    Students check in from their attendance page once the placement is active and today is inside the placement dates. Daily logs can only be logged for days with verified attendance.
                </div>
            </div>
        </div>
    </div>
@endsection
