@php
    $university = settings('university_name', 'Metropolitan University');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sign in') · InternTrack</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="guest-shell">
    <aside class="guest-aside">
        <div>
            <div class="d-flex align-items-center gap-2 mb-5">
                <span class="brand-mark" style="width:42px;height:42px;">IT</span>
                <div>
                    <div class="fw-bold fs-5 text-white">InternTrack</div>
                    <div class="text-uppercase" style="font-size:.68rem;letter-spacing:.1em;color:#93b3cf;">Internship Management System</div>
                </div>
            </div>

            <h1 class="display-6 fw-bold text-white mb-3" style="max-width:520px;line-height:1.15;">Verified internships. Real learning. Clear oversight.</h1>
            <p class="mb-4" style="max-width:480px;color:#b7cde0;">{{ $university }}'s central platform for GPS-verified attendance, daily digital logbooks, supervisor approval, and lecturer monitoring.</p>

            <div class="feature-line">
                <i class="bi bi-geo-alt-fill"></i>
                <div><strong>Geofenced attendance</strong><span>Check-ins validated against each company's location.</span></div>
            </div>
            <div class="feature-line">
                <i class="bi bi-journal-check"></i>
                <div><strong>Daily evidence, on time</strong><span>Same-day log submissions with supervisor verification.</span></div>
            </div>
            <div class="feature-line">
                <i class="bi bi-graph-up-arrow"></i>
                <div><strong>Early intervention</strong><span>Risk alerts help lecturers support students before they fall behind.</span></div>
            </div>
        </div>

        <div style="position:relative;z-index:1;color:#8aa8c2;font-size:.75rem;">
            &copy; {{ now()->year }} {{ $university }} · InternTrack v1.0
        </div>
    </aside>

    <main class="guest-main">
        <div class="guest-card">
            <div class="d-lg-none d-flex align-items-center gap-2 mb-4">
                <span class="brand-mark" style="width:38px;height:38px;background:#102a43;color:#fff;border-radius:.6rem;display:grid;place-items:center;font-weight:800;">IT</span>
                <span class="fw-bold fs-5 text-navy">InternTrack</span>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-auto-dismiss">{{ session('success') }}</div>
            @endif
            @if(session('status'))
                <div class="alert alert-info alert-auto-dismiss">{{ session('status') }}</div>
            @endif

            @yield('content')

            <p class="text-center text-muted-2 mt-4 mb-0" style="font-size:.72rem;">
                Authorised users only. Activity is recorded in the audit log.
            </p>
        </div>
    </main>
</div>
</body>
</html>
