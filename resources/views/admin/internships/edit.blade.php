@extends('layouts.app')

@section('title', 'Edit Placement')
@section('page-title', 'Edit Internship Placement')
@section('page-subtitle', $internship->student->user->name . ' at ' . $internship->company->name)

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('admin.internships.show', $internship) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to placement</a>
        <x-status-badge :status="$internship->status" />
    </div>

    @if($internship->attendances()->exists() || $internship->dailyLogs()->exists())
        <div class="alert alert-warning d-flex gap-2 align-items-start">
            <i class="bi bi-exclamation-triangle mt-1"></i>
            <div style="font-size:.82rem;">
                This placement already has attendance or logbook records. The student and company are locked, and the placement dates cannot exclude existing records. Contact the administrator if a historical correction is required.
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">Placement details</div>
                <div class="card-body">
                    @include('admin.internships._form', [
                        'action' => route('admin.internships.update', $internship),
                        'method' => 'PATCH',
                        'internship' => $internship,
                    ])
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Placement integrity</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.8rem;"><span class="text-muted-2">Attendance records</span><strong>{{ $internship->attendances()->count() }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.8rem;"><span class="text-muted-2">Daily logs</span><strong>{{ $internship->dailyLogs()->count() }}</strong></div>
                    <div class="d-flex justify-content-between py-2" style="font-size:.8rem;"><span class="text-muted-2">Evaluations</span><strong>{{ $internship->evaluations()->count() }}</strong></div>
                    <p class="text-muted-2 mb-0 mt-3" style="font-size:.76rem;">Changes are audited. Marking a placement active re-checks the company, period and staff account statuses.</p>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Need a status-only change?</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.internships.status', $internship) }}" class="d-flex gap-2" data-confirm="Change this placement's status?">
                        @csrf
                        <select name="status" class="form-select form-select-sm">
                            @foreach(\App\Enums\InternshipStatus::cases() as $case)
                                <option value="{{ $case->value }}" @selected($internship->status === $case)>{{ $case->label() }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-outline-primary text-nowrap">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
