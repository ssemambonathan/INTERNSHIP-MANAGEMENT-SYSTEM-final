@extends('layouts.app')

@section('title', 'Edit Period')
@section('page-title', 'Edit Internship Period')
@section('page-subtitle', $period->name)

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('admin.periods.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to periods</a>
        <x-status-badge :status="$period->status" />
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">Period details</div>
                <div class="card-body">
                    @include('admin.periods._form', [
                        'action' => route('admin.periods.update', $period),
                        'method' => 'PATCH',
                        'period' => $period,
                    ])
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Recorded placements</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.82rem;"><span class="text-muted-2">Placements in this period</span><strong>{{ $period->internships()->count() }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.82rem;"><span class="text-muted-2">Earliest start</span><span>{{ $period->internships()->min('start_date') ? \Illuminate\Support\Carbon::parse($period->internships()->min('start_date'))->format('d M Y') : '—' }}</span></div>
                    <div class="d-flex justify-content-between py-2" style="font-size:.82rem;"><span class="text-muted-2">Latest end</span><span>{{ $period->internships()->max('end_date') ? \Illuminate\Support\Carbon::parse($period->internships()->max('end_date'))->format('d M Y') : '—' }}</span></div>
                    <p class="text-muted-2 mb-0 mt-3" style="font-size:.76rem;">The dates cannot be narrowed so that existing placements fall outside the period.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
