@extends('layouts.app')

@section('title', 'Audit Logs')
@section('page-title', 'Audit Logs')
@section('page-subtitle', 'Read-only trail of security-relevant actions')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="section-label">Immutable activity trail</div>
            <div class="text-muted-2" style="font-size:.82rem;">Audit entries cannot be edited or deleted from the application. Sensitive changes record the actor, reason and source address.</div>
        </div>
        <span class="badge badge-soft-secondary"><i class="bi bi-lock me-1"></i>Read only</span>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="All entries" :value="number_format($summary['total'])" icon="bi-journal-text" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Today" :value="number_format($summary['today'])" icon="bi-calendar-day" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Matching filters" :value="number_format($summary['filtered'])" icon="bi-funnel" tone="warning" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Distinct actors" :value="number_format($summary['actors'])" icon="bi-people" tone="primary" /></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="q">Search</label>
                    <input id="q" type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Description, action or actor">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="module">Module</label>
                    <select id="module" name="module" class="form-select">
                        <option value="">All modules</option>
                        @foreach($modules as $moduleOption)
                            <option value="{{ $moduleOption }}" @selected($module === $moduleOption)>{{ $moduleOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="action">Action</label>
                    <select id="action" name="action" class="form-select">
                        <option value="">All actions</option>
                        @foreach($actions as $actionOption)
                            <option value="{{ $actionOption }}" @selected($action === $actionOption)>{{ $actionOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="user_id">Actor</label>
                    <select id="user_id" name="user_id" class="form-select">
                        <option value="">Anyone</option>
                        @foreach($users as $actor)
                            <option value="{{ $actor->id }}" @selected($userId === $actor->id)>{{ $actor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="from">From</label>
                    <input id="from" type="date" name="from" value="{{ $from->toDateString() }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="to">To</label>
                    <input id="to" type="date" name="to" value="{{ $to->toDateString() }}" class="form-control">
                </div>
                <div class="col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
                <div class="col-md-2"><a href="{{ route('admin.audit-logs.index') }}" class="btn btn-link text-decoration-none px-0">Reset filters</a></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span>{{ $logs->total() }} entr{{ $logs->total() === 1 ? 'y' : 'ies' }}</span>
            <span class="text-muted-2 fw-normal" style="font-size:.74rem;">{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</span>
        </div>
        @if($logs->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>When</th><th>Actor</th><th>Action</th><th>Description</th><th>Source</th></tr></thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr>
                                <td class="text-nowrap" style="font-size:.77rem;">
                                    <div>{{ $log->created_at?->format('d M Y') }}</div>
                                    <div class="text-muted-2" style="font-size:.7rem;">{{ $log->created_at?->format('H:i:s') }}</div>
                                </td>
                                <td style="font-size:.8rem;">
                                    @if($log->user)
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar avatar-sm" style="width:26px;height:26px;font-size:.65rem;">{{ strtoupper(Str::substr($log->user->name, 0, 1)) }}</span>
                                            <div>
                                                <div>{{ $log->user->name }}</div>
                                                <div class="text-muted-2" style="font-size:.68rem;">{{ $log->user->role?->label }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted-2">System</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-soft-primary">{{ $log->action }}</span>
                                    <div class="text-muted-2 mt-1" style="font-size:.68rem;">{{ $log->module }}</div>
                                </td>
                                <td style="max-width:420px;">
                                    <div style="font-size:.79rem;">{{ $log->description }}</div>
                                    @if($log->record_type)
                                        <div class="text-muted-2" style="font-size:.68rem;">{{ class_basename($log->record_type) }}{{ $log->record_id ? ' #'.$log->record_id : '' }}</div>
                                    @endif
                                </td>
                                <td style="font-size:.73rem;">
                                    <div>{{ $log->ip_address ?? '—' }}</div>
                                    @if($log->user_agent)
                                        <div class="text-muted-2" style="font-size:.66rem;max-width:180px;" title="{{ $log->user_agent }}">{{ Str::limit($log->user_agent, 34) }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body border-top">{{ $logs->links() }}</div>
        @else
            <x-empty-state icon="bi-shield-check" title="No audit entries" text="No entries match the selected filters. Audit records are created automatically as users work in the system." />
        @endif
    </div>
@endsection
