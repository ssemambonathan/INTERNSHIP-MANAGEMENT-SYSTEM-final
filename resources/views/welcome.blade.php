@php
    $university = settings('university_name', 'Metropolitan University');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="InternTrack — verified internship attendance, digital logbooks and university supervision at {{ $university }}.">
    <title>InternTrack · {{ $university }} Internship Management</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="background:#fff;">
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container py-1">
        <a href="{{ route('home') }}" class="navbar-brand d-flex align-items-center gap-2">
            <span class="brand-mark" style="width:36px;height:36px;font-size:.9rem;">IT</span>
            <span>
                <span class="d-block fw-bold text-navy" style="line-height:1.1;">InternTrack</span>
                <span class="d-block text-muted-2" style="font-size:.65rem;">{{ Str::limit($university, 32) }}</span>
            </span>
        </a>
        <div class="d-flex align-items-center gap-2">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm px-3">Open dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary btn-sm px-3">Sign in</a>
            @endauth
        </div>
    </div>
</nav>

<section class="landing-hero">
    <div class="container" style="position:relative;z-index:1;">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="badge mb-3" style="background:rgba(240,180,41,.16);color:#f0b429;font-size:.72rem;">
                    <i class="bi bi-shield-check me-1"></i> University internship management
                </span>
                <h1 class="display-5 fw-bold mb-3" style="color:#fff;line-height:1.12;">Every internship day, verified.</h1>
                <p class="fs-5 mb-4" style="color:#b9cee0;max-width:650px;">
                    InternTrack replaces paper logbooks with GPS-verified attendance, evidence-backed daily logs,
                    supervisor approvals and real-time lecturer oversight.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('login') }}" class="btn btn-warning fw-semibold px-4 py-2" style="background:#f0b429;border-color:#f0b429;color:#102a43;">
                        Sign in to InternTrack <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                    <a href="#roles" class="btn btn-outline-light px-4 py-2">Explore the workflow</a>
                </div>
                <div class="d-flex flex-wrap gap-4 mt-4" style="font-size:.8rem;color:#a9c1d6;">
                    <span><i class="bi bi-geo-alt-fill me-1" style="color:#f0b429;"></i>Geofenced check-in</span>
                    <span><i class="bi bi-journal-check me-1" style="color:#f0b429;"></i>Daily logbook lock</span>
                    <span><i class="bi bi-graph-up-arrow me-1" style="color:#f0b429;"></i>Risk monitoring</span>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="p-4 rounded-4" style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);backdrop-filter:blur(4px);">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-uppercase" style="font-size:.68rem;letter-spacing:.08em;color:#93b3cf;">Today's internship pulse</span>
                        <span class="badge" style="background:rgba(22,163,74,.18);color:#86efac;">Live</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 rounded-3" style="background:rgba(255,255,255,.07);">
                                <div style="font-size:.7rem;color:#93b3cf;">ATTENDANCE</div>
                                <div class="fs-4 fw-bold text-white">GPS</div>
                                <div style="font-size:.7rem;color:#a9c1d6;">Verified on site</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3" style="background:rgba(255,255,255,.07);">
                                <div style="font-size:.7rem;color:#93b3cf;">LOGBOOK</div>
                                <div class="fs-4 fw-bold text-white">Same day</div>
                                <div style="font-size:.7rem;color:#a9c1d6;">No back-filling</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3" style="background:rgba(255,255,255,.07);">
                                <div style="font-size:.7rem;color:#93b3cf;">SUPERVISION</div>
                                <div class="fs-4 fw-bold text-white">Verified</div>
                                <div style="font-size:.7rem;color:#a9c1d6;">Weekly evaluations</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3" style="background:rgba(255,255,255,.07);">
                                <div style="font-size:.7rem;color:#93b3cf;">OVERSIGHT</div>
                                <div class="fs-4 fw-bold text-white">Alerts</div>
                                <div style="font-size:.7rem;color:#a9c1d6;">Early intervention</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5" id="roles">
    <div class="container">
        <div class="text-center mb-4">
            <div class="section-label mb-2">Built for every stakeholder</div>
            <h2 class="h3 mb-2">One internship record. Four accountable roles.</h2>
            <p class="text-muted-2 mx-auto" style="max-width:620px;font-size:.88rem;">From the first check-in to the final assessment, every action is attributable and every submission is timestamped.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <div class="landing-feature">
                    <div class="icon-box"><i class="bi bi-mortarboard"></i></div>
                    <h5 class="h6">Students</h5>
                    <p class="text-muted-2 mb-0" style="font-size:.82rem;">Check in with GPS, record daily learning, attach evidence and track approval status.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="landing-feature">
                    <div class="icon-box"><i class="bi bi-building"></i></div>
                    <h5 class="h6">Company supervisors</h5>
                    <p class="text-muted-2 mb-0" style="font-size:.82rem;">Review real daily entries, verify flagged attendance and evaluate professional growth.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="landing-feature">
                    <div class="icon-box"><i class="bi bi-person-workspace"></i></div>
                    <h5 class="h6">Lecturers</h5>
                    <p class="text-muted-2 mb-0" style="font-size:.82rem;">Monitor progress, detect inactivity early, schedule visits and generate assessment reports.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="landing-feature">
                    <div class="icon-box"><i class="bi bi-shield-check"></i></div>
                    <h5 class="h6">Administrators</h5>
                    <p class="text-muted-2 mb-0" style="font-size:.82rem;">Manage placements, companies, periods and institutional reports with a complete audit trail.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5" style="background:#f8fafc;border-top:1px solid var(--it-border);">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-5">
                <div class="section-label mb-2">Anti-fraud by design</div>
                <h2 class="h3 mb-3">Retrospective logbook filling is not possible.</h2>
                <p class="text-muted-2" style="font-size:.87rem;">The system enforces today-only submissions by default. Missed days are visible, and only an authorised lecturer or administrator can reopen a date — with a written reason and an audit record.</p>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="bg-white border rounded-3 p-3 h-100">
                            <i class="bi bi-geo-fill text-navy fs-5"></i>
                            <h6 class="mt-2 mb-1">Distance-checked check-ins</h6>
                            <p class="text-muted-2 mb-0" style="font-size:.8rem;">Coordinates are compared with the company's geofence using the Haversine formula.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="bg-white border rounded-3 p-3 h-100">
                            <i class="bi bi-lock text-navy fs-5"></i>
                            <h6 class="mt-2 mb-1">Locked approved logs</h6>
                            <p class="text-muted-2 mb-0" style="font-size:.8rem;">Once approved, a daily log cannot be silently changed by the student.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="bg-white border rounded-3 p-3 h-100">
                            <i class="bi bi-clock-history text-navy fs-5"></i>
                            <h6 class="mt-2 mb-1">Server-side timestamps</h6>
                            <p class="text-muted-2 mb-0" style="font-size:.8rem;">Attendance and submissions use trusted server time, not the student's device clock.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="bg-white border rounded-3 p-3 h-100">
                            <i class="bi bi-shield-lock text-navy fs-5"></i>
                            <h6 class="mt-2 mb-1">Immutable audit trail</h6>
                            <p class="text-muted-2 mb-0" style="font-size:.8rem;">Reopenings, approvals and corrections are permanently recorded against the actor.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="py-4 bg-white border-top">
    <div class="container d-flex flex-wrap justify-content-between gap-2" style="font-size:.76rem;color:var(--it-muted);">
        <span>&copy; {{ now()->year }} {{ $university }} · InternTrack v1.0</span>
        <span>{{ settings('university_address', 'Kampala, Uganda') }}</span>
    </div>
</footer>
</body>
</html>
