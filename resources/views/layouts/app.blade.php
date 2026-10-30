@php
    $user = auth()->user();
    $unreadCount = $user->unreadNotifications()->count();
    $navNotifications = $user->notifications()->limit(6)->get();
    $roleLabel = $user->role?->label ?? 'User';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#102a43">
    <title>@yield('title', 'Dashboard') · {{ settings('university_name', config('app.name')) }} InternTrack</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="app-shell">
    @include('layouts.partials.sidebar')

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="app-main">
        <header class="app-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle" type="button" aria-label="Toggle navigation">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <div class="fw-semibold text-navy" style="font-size:.95rem;">@yield('page-title', 'Dashboard')</div>
                    <div class="text-muted-2" style="font-size:.75rem;">@yield('page-subtitle', now()->format('l, d F Y'))</div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn btn-light border position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                        @if($unreadCount > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.6rem;">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow notification-menu">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                            <span class="fw-semibold" style="font-size:.85rem;">Notifications</span>
                            @if($unreadCount > 0)
                                <form method="POST" action="{{ route('notifications.read-all') }}">
                                    @csrf
                                    <button class="btn btn-link btn-sm p-0 text-decoration-none" style="font-size:.75rem;">Mark all read</button>
                                </form>
                            @endif
                        </div>
                        @forelse($navNotifications as $notification)
                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                @csrf
                                <button type="submit" class="notification-item w-100 border-0 text-start {{ $notification->read_at ? '' : 'unread' }}">
                                    <span class="notification-icon"><i class="bi {{ $notification->data['icon'] ?? 'bi-bell' }}"></i></span>
                                    <span>
                                        <span class="fw-semibold d-block text-navy" style="font-size:.8rem;">{{ $notification->data['title'] ?? 'Notification' }}</span>
                                        <span class="d-block text-muted-2" style="font-size:.75rem;">{{ Str::limit($notification->data['message'] ?? '', 90) }}</span>
                                        <span class="d-block text-muted-2 mt-1" style="font-size:.68rem;">{{ $notification->created_at->diffForHumans() }}</span>
                                    </span>
                                </button>
                            </form>
                        @empty
                            <div class="px-3 py-4 text-center text-muted-2" style="font-size:.8rem;">
                                <i class="bi bi-bell-slash d-block fs-4 mb-2"></i>
                                No notifications yet
                            </div>
                        @endforelse
                        <a href="{{ route('notifications.index') }}" class="dropdown-item text-center border-top py-2" style="font-size:.8rem;">View all notifications</a>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="btn btn-light border d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar avatar-sm">{{ strtoupper(Str::substr($user->name, 0, 1)) }}</span>
                        <span class="d-none d-md-block text-start" style="line-height:1.1;">
                            <span class="d-block fw-semibold" style="font-size:.8rem;">{{ Str::limit($user->name, 24) }}</span>
                            <span class="d-block text-muted-2" style="font-size:.68rem;">{{ $roleLabel }}</span>
                        </span>
                        <i class="bi bi-chevron-down small text-muted"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>My profile</a></li>
                        <li><a class="dropdown-item" href="{{ route('notifications.index') }}"><i class="bi bi-bell me-2"></i>Notifications</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="page-wrap">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show alert-auto-dismiss" role="alert">
                    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('status') && ! in_array(session('status'), ['profile-updated', 'password-updated']))
                <div class="alert alert-info alert-dismissible fade show alert-auto-dismiss" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('status') === 'profile-updated')
                <div class="alert alert-success alert-dismissible fade show alert-auto-dismiss" role="alert">
                    <i class="bi bi-check-circle me-1"></i>Your profile has been updated.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('status') === 'password-updated')
                <div class="alert alert-success alert-dismissible fade show alert-auto-dismiss" role="alert">
                    <i class="bi bi-check-circle me-1"></i>Your password has been updated.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Please correct the following:</div>
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="px-4 py-3 text-muted-2 border-top bg-white d-flex flex-wrap justify-content-between gap-2" style="font-size:.73rem;">
            <span>&copy; {{ now()->year }} {{ settings('university_name', 'InternTrack') }} · InternTrack v1.0</span>
            <span>Internship Monitoring &amp; Assessment System</span>
        </footer>
    </div>
</div>

@stack('scripts')
</body>
</html>
