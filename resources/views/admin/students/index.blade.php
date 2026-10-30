@extends('layouts.app')

@section('title', 'Students')
@section('page-title', 'Students')
@section('page-subtitle', 'Student accounts, academic details and placement status')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="section-label">Student register</div>
            <div class="text-muted-2" style="font-size:.82rem;">Accounts are linked to a faculty, department and course. Passwords are hashed on creation.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.students.import.form') }}" class="btn btn-outline-primary"><i class="bi bi-filetype-csv me-1"></i>Import CSV</a>
            <a href="{{ route('admin.students.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Add student</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="Total students" :value="$counts['total']" icon="bi-mortarboard" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Active accounts" :value="$counts['active']" icon="bi-person-check" tone="success" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Currently placed" :value="$counts['placed']" icon="bi-briefcase" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="No placement" :value="$counts['unplaced']" icon="bi-person-dash" tone="warning" /></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="q">Search</label>
                    <input id="q" type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Name, email or registration number">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="faculty_id">Faculty</label>
                    <select id="faculty_id" name="faculty_id" class="form-select">
                        <option value="">All faculties</option>
                        @foreach($faculties as $faculty)
                            <option value="{{ $faculty->id }}" @selected((int) $facultyId === $faculty->id)>{{ $faculty->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="status">Account</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Any status</option>
                        @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'] as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="placement">Placement</label>
                    <select id="placement" name="placement" class="form-select">
                        <option value="">Any</option>
                        <option value="active" @selected($placement === 'active')>Active placement</option>
                        <option value="none" @selected($placement === 'none')>Never placed</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex gap-2">
                    <button class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
            @if($search !== '' || $facultyId || $status !== '' || $placement !== '')
                <div class="mt-2"><a href="{{ route('admin.students.index') }}" class="btn btn-sm btn-link text-decoration-none px-0">Clear filters</a></div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span>{{ $students->total() }} student{{ $students->total() === 1 ? '' : 's' }}</span>
            <span class="text-muted-2 fw-normal" style="font-size:.74rem;">Page {{ $students->currentPage() }} of {{ $students->lastPage() }}</span>
        </div>

        @if($students->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Student</th><th>Academic</th><th>Year</th><th>Latest placement</th><th>Account</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar">{{ strtoupper(Str::substr($student->user->name, 0, 1)) }}</span>
                                        <div>
                                            <div class="fw-semibold">{{ $student->user->name }}</div>
                                            <div class="text-muted-2" style="font-size:.72rem;">{{ $student->registration_number }}{{ $student->student_number ? ' · '.$student->student_number : '' }}</div>
                                            <div class="text-muted-2" style="font-size:.7rem;">{{ $student->user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size:.8rem;">{{ $student->course?->name ?? '—' }}</div>
                                    <div class="text-muted-2" style="font-size:.7rem;">{{ $student->department?->name }} · {{ $student->faculty?->code }}</div>
                                </td>
                                <td>
                                    <div>Year {{ $student->year_of_study }}</div>
                                    <div class="text-muted-2" style="font-size:.7rem;">{{ $student->academicYear?->name ?? 'No academic year' }}</div>
                                </td>
                                <td>
                                    @if($student->latestInternship)
                                        <div style="font-size:.8rem;">{{ $student->latestInternship->company?->name ?? 'Company removed' }}</div>
                                        <div class="mt-1"><x-status-badge :status="$student->latestInternship->status" /></div>
                                    @else
                                        <span class="badge badge-soft-secondary">Not placed</span>
                                    @endif
                                </td>
                                <td><x-status-badge :status="$student->user->status" /></td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('admin.students.show', $student) }}" class="btn btn-sm btn-outline-primary py-0">View</a>
                                    <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-sm btn-outline-secondary py-0">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body border-top">{{ $students->links() }}</div>
        @else
            <x-empty-state icon="bi-mortarboard" title="No students found" text="Create a student account or adjust the filters. CSV import supports bulk onboarding." :action="route('admin.students.create')" action-label="Add student" />
        @endif
    </div>
@endsection
