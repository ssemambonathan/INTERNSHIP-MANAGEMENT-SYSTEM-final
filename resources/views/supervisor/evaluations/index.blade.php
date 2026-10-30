@extends('layouts.app')

@section('title', 'Weekly Evaluations')
@section('page-title', 'Weekly Evaluations')
@section('page-subtitle', 'Six-criterion progress records for students at your company')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <span>Evaluation history</span>
                    <a href="{{ route('supervisor.evaluations.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>New evaluation</a>
                </div>

                @if($evaluations->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr><th>Student</th><th>Week</th><th>Average</th><th>Recommendation</th><th>Recorded</th><th class="text-end"></th></tr>
                            </thead>
                            <tbody>
                                @foreach($evaluations as $evaluation)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $evaluation->internship->student->user->name }}</div>
                                            <div class="text-muted-2" style="font-size:.72rem;">{{ $evaluation->internship->student->registration_number }}</div>
                                        </td>
                                        <td>{{ $evaluation->week_start->format('d M') }} – {{ $evaluation->week_end->format('d M Y') }}</td>
                                        <td>
                                            <span class="fw-semibold">{{ $evaluation->averageScore() }}</span>
                                            <span class="text-muted-2" style="font-size:.75rem;">/ {{ settings('evaluation_scale', 5) }}</span>
                                        </td>
                                        <td>{{ $evaluation->recommendation ?: '—' }}</td>
                                        <td class="text-nowrap">{{ $evaluation->created_at->format('d M Y') }}</td>
                                        <td class="text-end"><a href="{{ route('supervisor.evaluations.show', $evaluation) }}" class="btn btn-sm btn-outline-secondary py-0">Open</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-body border-top">{{ $evaluations->links() }}</div>
                @else
                    <x-empty-state icon="bi-clipboard-data" title="No evaluations yet" text="Record a weekly evaluation for an active student to start tracking their progress." :action="route('supervisor.evaluations.create')" action-label="Create first evaluation" />
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Active students</div>
                <div class="card-body">
                    @if($activeInternships->count() > 0)
                        <p class="text-muted-2" style="font-size:.8rem;">Start a new evaluation for any active placement. Each week can only be evaluated once per student.</p>
                        <div class="list-group list-group-flush">
                            @foreach($activeInternships as $internship)
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-semibold" style="font-size:.84rem;">{{ $internship->student->user->name }}</div>
                                        <div class="text-muted-2" style="font-size:.72rem;">{{ $internship->student->registration_number }} · {{ $internship->company?->name }}</div>
                                    </div>
                                    <a href="{{ route('supervisor.evaluations.create', ['internship_id' => $internship->id]) }}" class="btn btn-sm btn-outline-primary py-0">Evaluate</a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted-2 mb-0" style="font-size:.82rem;">No active placements are assigned to you.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
