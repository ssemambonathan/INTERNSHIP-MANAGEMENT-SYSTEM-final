@php
    $user = auth()->user();
@endphp
<aside class="app-sidebar" id="appSidebar">
    <a href="{{ route('dashboard') }}" class="brand text-decoration-none">
        <span class="brand-mark">IT</span>
        <span>
            <span class="brand-name d-block">InternTrack</span>
            <span class="brand-sub">{{ Str::limit(settings('university_name', 'University'), 26) }}</span>
        </span>
    </a>

    <nav class="sidebar-nav">
        @if($user->isAdmin())
            <div class="sidebar-heading">Overview</div>
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-grid-1x2"></i>Dashboard</a>

            <div class="sidebar-heading">People</div>
            <a class="nav-link {{ request()->routeIs('admin.students.*') ? 'active' : '' }}" href="{{ route('admin.students.index') }}"><i class="bi bi-mortarboard"></i>Students</a>
            <a class="nav-link {{ request()->routeIs('admin.supervisors.*') ? 'active' : '' }}" href="{{ route('admin.supervisors.index') }}"><i class="bi bi-person-badge"></i>Company Supervisors</a>
            <a class="nav-link {{ request()->routeIs('admin.lecturers.*') ? 'active' : '' }}" href="{{ route('admin.lecturers.index') }}"><i class="bi bi-person-workspace"></i>Lecturers</a>

            <div class="sidebar-heading">Organisations</div>
            <a class="nav-link {{ request()->routeIs('admin.companies.*') ? 'active' : '' }}" href="{{ route('admin.companies.index') }}"><i class="bi bi-building"></i>Companies</a>

            <div class="sidebar-heading">Internships</div>
            <a class="nav-link {{ request()->routeIs('admin.internships.*') ? 'active' : '' }}" href="{{ route('admin.internships.index') }}"><i class="bi bi-briefcase"></i>Placements</a>
            <a class="nav-link {{ request()->routeIs('admin.periods.*') ? 'active' : '' }}" href="{{ route('admin.periods.index') }}"><i class="bi bi-calendar-range"></i>Internship Periods</a>
            <a class="nav-link {{ request()->routeIs('admin.academics.*') ? 'active' : '' }}" href="{{ route('admin.academics.index') }}"><i class="bi bi-diagram-3"></i>Academic Structure</a>

            <div class="sidebar-heading">Operations</div>
            <a class="nav-link {{ request()->routeIs('admin.attendance.*') ? 'active' : '' }}" href="{{ route('admin.attendance.index') }}"><i class="bi bi-geo-alt"></i>Attendance</a>
            <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}"><i class="bi bi-file-earmark-bar-graph"></i>Reports</a>
            <a class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}"><i class="bi bi-shield-check"></i>Audit Logs</a>
            <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.edit') }}"><i class="bi bi-sliders"></i>System Settings</a>
        @elseif($user->isStudent())
            <div class="sidebar-heading">My Internship</div>
            <a class="nav-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}" href="{{ route('student.dashboard') }}"><i class="bi bi-grid-1x2"></i>Dashboard</a>
            <a class="nav-link {{ request()->routeIs('student.attendance.*') ? 'active' : '' }}" href="{{ route('student.attendance.index') }}"><i class="bi bi-geo-alt"></i>Attendance</a>
            <a class="nav-link {{ request()->routeIs('student.logs.*') ? 'active' : '' }}" href="{{ route('student.logs.index') }}"><i class="bi bi-journal-text"></i>Daily Logbook</a>
            <a class="nav-link {{ request()->routeIs('student.report.*') ? 'active' : '' }}" href="{{ route('student.report.show') }}"><i class="bi bi-file-earmark-pdf"></i>Internship Report</a>

            <div class="sidebar-heading">Account</div>
            <a class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}"><i class="bi bi-bell"></i>Notifications</a>
            <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}"><i class="bi bi-person"></i>My Profile</a>
        @elseif($user->isSupervisor())
            <div class="sidebar-heading">Supervision</div>
            <a class="nav-link {{ request()->routeIs('supervisor.dashboard') ? 'active' : '' }}" href="{{ route('supervisor.dashboard') }}"><i class="bi bi-grid-1x2"></i>Dashboard</a>
            <a class="nav-link {{ request()->routeIs('supervisor.students.*') ? 'active' : '' }}" href="{{ route('supervisor.students.index') }}"><i class="bi bi-people"></i>My Students</a>
            <a class="nav-link {{ request()->routeIs('supervisor.approvals.*') ? 'active' : '' }}" href="{{ route('supervisor.approvals.index') }}"><i class="bi bi-check2-square"></i>Log Approvals</a>
            <a class="nav-link {{ request()->routeIs('supervisor.attendance.*') ? 'active' : '' }}" href="{{ route('supervisor.attendance.index') }}"><i class="bi bi-geo-alt"></i>Attendance</a>
            <a class="nav-link {{ request()->routeIs('supervisor.evaluations.*') ? 'active' : '' }}" href="{{ route('supervisor.evaluations.index') }}"><i class="bi bi-clipboard-data"></i>Weekly Evaluations</a>

            <div class="sidebar-heading">Account</div>
            <a class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}"><i class="bi bi-bell"></i>Notifications</a>
            <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}"><i class="bi bi-person"></i>My Profile</a>
        @elseif($user->isLecturer())
            <div class="sidebar-heading">Monitoring</div>
            <a class="nav-link {{ request()->routeIs('lecturer.dashboard') ? 'active' : '' }}" href="{{ route('lecturer.dashboard') }}"><i class="bi bi-grid-1x2"></i>Dashboard</a>
            <a class="nav-link {{ request()->routeIs('lecturer.students.*') || request()->routeIs('lecturer.reports.*') ? 'active' : '' }}" href="{{ route('lecturer.students.index') }}"><i class="bi bi-people"></i>My Students</a>
            <a class="nav-link {{ request()->routeIs('lecturer.visits.*') ? 'active' : '' }}" href="{{ route('lecturer.visits.index') }}"><i class="bi bi-pin-map"></i>Supervision Visits</a>

            <div class="sidebar-heading">Account</div>
            <a class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}"><i class="bi bi-bell"></i>Notifications</a>
            <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}"><i class="bi bi-person"></i>My Profile</a>
        @endif
    </nav>

    <div class="sidebar-footer">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-shield-lock"></i>
            <span>Secure internship workspace</span>
        </div>
    </div>
</aside>
