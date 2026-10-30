@extends('layouts.app')

@section('title', 'Notifications')
@section('page-title', 'Notifications')
@section('page-subtitle', 'Updates about attendance, log reviews, visits and risk alerts')

@section('content')
    <div class="card">
        <div class="card-header">
            <span>All notifications</span>
            @if($notifications->contains(fn ($notification) => $notification->read_at === null))
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-check2-all me-1"></i>Mark all as read</button>
                </form>
            @endif
        </div>

        @if($notifications->count() > 0)
            <div class="list-group list-group-flush">
                @foreach($notifications as $notification)
                    <div class="list-group-item d-flex gap-3 {{ $notification->read_at ? '' : 'bg-soft-primary' }}">
                        <span class="notification-icon flex-shrink-0"><i class="bi {{ $notification->data['icon'] ?? 'bi-bell' }}"></i></span>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between gap-3">
                                <span class="fw-semibold text-navy">{{ $notification->data['title'] ?? 'Notification' }}</span>
                                <small class="text-muted-2 text-nowrap">{{ $notification->created_at->diffForHumans() }}</small>
                            </div>
                            <div class="text-muted-2 mt-1" style="font-size:.83rem;">{{ $notification->data['message'] ?? '' }}</div>
                            <div class="mt-2 d-flex gap-2">
                                @if(! empty($notification->data['url']))
                                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-primary py-0">Open</button>
                                    </form>
                                @endif
                                @if(! $notification->read_at)
                                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-link py-0 text-decoration-none">Mark read</button>
                                    </form>
                                @else
                                    <span class="badge badge-soft-secondary">Read</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="card-body border-top">{{ $notifications->links() }}</div>
        @else
            <x-empty-state icon="bi-bell-slash" title="No notifications yet" text="Attendance reminders, log reviews and alerts will appear here." />
        @endif
    </div>
@endsection
