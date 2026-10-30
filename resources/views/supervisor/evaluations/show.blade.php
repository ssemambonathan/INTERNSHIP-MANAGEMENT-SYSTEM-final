@extends('layouts.app')

@section('title', 'Weekly Evaluation')
@section('page-title', 'Weekly Evaluation')
@section('page-subtitle', $evaluation->internship->student->user->name . ' · week of ' . $evaluation->week_start->format('d M Y'))

@section('content')
    @php($scale = (int) settings('evaluation_scale', 5))

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('supervisor.evaluations.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to evaluations</a>
        <div class="d-flex gap-2">
            <a href="{{ route('supervisor.students.show', $evaluation->internship->student) }}" class="btn btn-sm btn-outline-primary">Student profile</a>
            @can('update', $evaluation)
                <a href="{{ route('supervisor.evaluations.edit', $evaluation) }}" class="btn btn-sm btn-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="section-label">Overall average</div>
                    <div class="display-5 fw-bold text-navy">{{ $evaluation->averageScore() }}</div>
                    <div class="text-muted-2 mb-3" style="font-size:.8rem;">out of {{ $scale }}</div>
                    <div class="progress mb-2" style="height:8px;">
                        <div class="progress-bar {{ $evaluation->averageScore() >= $scale * 0.8 ? 'bg-success' : ($evaluation->averageScore() >= $scale * 0.5 ? 'bg-warning' : 'bg-danger') }}" style="width:{{ round(($evaluation->averageScore() / $scale) * 100) }}%"></div>
                    </div>
                    <div class="text-muted-2" style="font-size:.75rem;">{{ $evaluation->week_start->format('d M') }} – {{ $evaluation->week_end->format('d M Y') }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="avatar avatar-lg">{{ strtoupper(Str::substr($evaluation->internship->student->user->name, 0, 1)) }}</span>
                        <div>
                            <h4 class="h5 mb-1">{{ $evaluation->internship->student->user->name }}</h4>
                            <div class="text-muted-2" style="font-size:.8rem;">{{ $evaluation->internship->student->registration_number }} · {{ $evaluation->internship->company?->name }}</div>
                            <div class="text-muted-2" style="font-size:.75rem;">Supervisor: {{ $evaluation->supervisor?->name ?? '—' }} · recorded {{ $evaluation->created_at->format('d M Y, H:i') }}</div>
                        </div>
                    </div>
                    @if($evaluation->recommendation)
                        <div class="d-flex justify-content-between align-items-center border-top pt-3" style="font-size:.84rem;">
                            <span class="text-muted-2">Overall recommendation</span>
                            <span class="badge badge-soft-{{ $evaluation->recommendation === 'Recommended' ? 'success' : ($evaluation->recommendation === 'Not recommended' ? 'danger' : 'warning') }}">{{ $evaluation->recommendation }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header">Criterion scores</div>
                <div class="card-body">
                    @foreach(\App\Models\Evaluation::$criteria as $field => $label)
                        @php($score = (int) $evaluation->{$field})
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1" style="font-size:.82rem;">
                                <span class="fw-semibold">{{ $label }}</span>
                                <span class="text-muted-2">{{ $score }} / {{ $scale }}</span>
                            </div>
                            <div class="progress" style="height:7px;">
                                <div class="progress-bar {{ $score >= $scale * 0.8 ? 'bg-success' : ($score >= $scale * 0.5 ? 'bg-warning' : 'bg-danger') }}" style="width:{{ round(($score / $scale) * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header">Written feedback</div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="section-label mb-1"><i class="bi bi-star me-1"></i>Key strengths</div>
                        <div style="font-size:.84rem;white-space:pre-line;">{{ $evaluation->strengths ?: '—' }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="section-label mb-1"><i class="bi bi-arrow-up-right me-1"></i>Areas for improvement</div>
                        <div style="font-size:.84rem;white-space:pre-line;">{{ $evaluation->areas_for_improvement ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="section-label mb-1"><i class="bi bi-chat-left-text me-1"></i>Remarks</div>
                        <div style="font-size:.84rem;white-space:pre-line;">{{ $evaluation->remarks ?: '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Placement</div>
                <div class="card-body" style="font-size:.82rem;">
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Company</span><span class="text-end">{{ $evaluation->internship->company?->name }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Internship dates</span><span class="text-end">{{ $evaluation->internship->start_date->format('d M Y') }} – {{ $evaluation->internship->end_date->format('d M Y') }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2">Status</span><x-status-badge :status="$evaluation->internship->status" /></div>
                </div>
            </div>
        </div>
    </div>
@endsection
