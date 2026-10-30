@extends('layouts.app')

@section('title', 'Academic Structure')
@section('page-title', 'Academic Structure')
@section('page-subtitle', 'Faculties, departments, courses and academic years')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="section-label">University configuration</div>
            <div class="text-muted-2" style="font-size:.82rem;">Students, lecturers and placements inherit this hierarchy. Codes are stored in uppercase.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.students.index') }}" class="btn btn-outline-primary"><i class="bi bi-mortarboard me-1"></i>Students</a>
            <a href="{{ route('admin.lecturers.index') }}" class="btn btn-outline-primary"><i class="bi bi-person-workspace me-1"></i>Lecturers</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-stat label="Faculties" :value="$counts['faculties']" icon="bi-building" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Departments" :value="$counts['departments']" icon="bi-diagram-3" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Courses" :value="$counts['courses']" icon="bi-book" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat label="Academic years" :value="$counts['academic_years']" icon="bi-calendar3" tone="primary" /></div>
    </div>

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-faculties" type="button" role="tab">Faculties <span class="badge badge-soft-primary ms-1">{{ $counts['faculties'] }}</span></button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-departments" type="button" role="tab">Departments <span class="badge badge-soft-primary ms-1">{{ $counts['departments'] }}</span></button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-courses" type="button" role="tab">Courses <span class="badge badge-soft-primary ms-1">{{ $counts['courses'] }}</span></button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-years" type="button" role="tab">Academic years <span class="badge badge-soft-primary ms-1">{{ $counts['academic_years'] }}</span></button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-faculties" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <span>Faculties</span>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#facultyCreateModal"><i class="bi bi-plus-lg me-1"></i>Add faculty</button>
                </div>
                @if($faculties->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Faculty</th><th>Code</th><th>Departments</th><th>Students</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                                @foreach($faculties as $faculty)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $faculty->name }}</div>
                                            <div class="text-muted-2" style="font-size:.72rem;">{{ $faculty->description ?: 'No description' }}</div>
                                        </td>
                                        <td><span class="badge badge-soft-secondary">{{ $faculty->code }}</span></td>
                                        <td style="font-size:.8rem;">{{ $faculty->departments->count() }}</td>
                                        <td style="font-size:.8rem;">
                                            {{ $faculty->students_count }}
                                            <a href="{{ route('admin.students.index', ['faculty_id' => $faculty->id]) }}" class="text-muted-2 ms-1" style="font-size:.7rem;">View</a>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-secondary py-0"
                                                    data-bs-toggle="modal" data-bs-target="#facultyEditModal"
                                                    data-id="{{ $faculty->id }}"
                                                    data-name="{{ $faculty->name }}"
                                                    data-code="{{ $faculty->code }}"
                                                    data-description="{{ $faculty->description }}">Edit</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-empty-state icon="bi-building" title="No faculties" text="Add a faculty to start building the academic structure." />
                @endif
            </div>
        </div>

        <div class="tab-pane fade" id="tab-departments" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <span>Departments</span>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#departmentCreateModal"><i class="bi bi-plus-lg me-1"></i>Add department</button>
                </div>
                @if($departments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Department</th><th>Faculty</th><th>Code</th><th>Courses</th><th>Students</th><th>Lecturers</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                                @foreach($departments as $department)
                                    <tr>
                                        <td class="fw-semibold">{{ $department->name }}</td>
                                        <td style="font-size:.78rem;">{{ $department->faculty?->name }}</td>
                                        <td><span class="badge badge-soft-secondary">{{ $department->code }}</span></td>
                                        <td style="font-size:.8rem;">{{ $department->courses_count }}</td>
                                        <td style="font-size:.8rem;">{{ $department->students_count }}</td>
                                        <td style="font-size:.8rem;">{{ $department->lecturers_count }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-secondary py-0"
                                                    data-bs-toggle="modal" data-bs-target="#departmentEditModal"
                                                    data-id="{{ $department->id }}"
                                                    data-faculty="{{ $department->faculty_id }}"
                                                    data-name="{{ $department->name }}"
                                                    data-code="{{ $department->code }}"
                                                    data-description="{{ $department->description }}">Edit</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-empty-state icon="bi-diagram-3" title="No departments" text="Add a department under a faculty before registering courses and staff." />
                @endif
            </div>
        </div>

        <div class="tab-pane fade" id="tab-courses" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <span>Courses</span>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#courseCreateModal"><i class="bi bi-plus-lg me-1"></i>Add course</button>
                </div>
                @if($courses->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Course</th><th>Department</th><th>Code</th><th>Level</th><th>Duration</th><th>Students</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                                @foreach($courses as $course)
                                    <tr>
                                        <td class="fw-semibold">{{ $course->name }}</td>
                                        <td style="font-size:.78rem;">
                                            {{ $course->department?->name }}
                                            <div class="text-muted-2" style="font-size:.7rem;">{{ $course->department?->faculty?->name }}</div>
                                        </td>
                                        <td><span class="badge badge-soft-secondary">{{ $course->code }}</span></td>
                                        <td style="font-size:.78rem;">{{ $course->degree_level ?: '—' }}</td>
                                        <td style="font-size:.78rem;">{{ $course->duration_years }} year{{ $course->duration_years === 1 ? '' : 's' }}</td>
                                        <td style="font-size:.8rem;">{{ $course->students_count }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-secondary py-0"
                                                    data-bs-toggle="modal" data-bs-target="#courseEditModal"
                                                    data-id="{{ $course->id }}"
                                                    data-department="{{ $course->department_id }}"
                                                    data-name="{{ $course->name }}"
                                                    data-code="{{ $course->code }}"
                                                    data-level="{{ $course->degree_level }}"
                                                    data-duration="{{ $course->duration_years }}"
                                                    data-description="{{ $course->description }}">Edit</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-empty-state icon="bi-book" title="No courses" text="Add a course under a department so students can be registered." />
                @endif
            </div>
        </div>

        <div class="tab-pane fade" id="tab-years" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <span>Academic years</span>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#yearCreateModal"><i class="bi bi-plus-lg me-1"></i>Add academic year</button>
                </div>
                @if($academicYears->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Academic year</th><th>Dates</th><th>Internship periods</th><th>Students</th><th>Current</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                                @foreach($academicYears as $year)
                                    <tr>
                                        <td class="fw-semibold">{{ $year->name }}</td>
                                        <td class="text-nowrap" style="font-size:.78rem;">{{ $year->start_date->format('d M Y') }} – {{ $year->end_date->format('d M Y') }}</td>
                                        <td style="font-size:.8rem;">
                                            {{ $year->internship_periods_count }}
                                            <a href="{{ route('admin.periods.index', ['academic_year_id' => $year->id]) }}" class="text-muted-2 ms-1" style="font-size:.7rem;">View</a>
                                        </td>
                                        <td style="font-size:.8rem;">{{ $year->students_count }}</td>
                                        <td>
                                            @if($year->is_current)
                                                <span class="badge badge-soft-success">Current</span>
                                            @else
                                                <span class="text-muted-2" style="font-size:.75rem;">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-secondary py-0"
                                                    data-bs-toggle="modal" data-bs-target="#yearEditModal"
                                                    data-id="{{ $year->id }}"
                                                    data-name="{{ $year->name }}"
                                                    data-start="{{ $year->start_date->toDateString() }}"
                                                    data-end="{{ $year->end_date->toDateString() }}"
                                                    data-current="{{ $year->is_current ? '1' : '0' }}">Edit</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-empty-state icon="bi-calendar3" title="No academic years" text="Add an academic year before creating internship periods." />
                @endif
            </div>
            <div class="alert alert-info mt-3 mb-0" style="font-size:.78rem;">
                <i class="bi bi-info-circle me-1"></i>Only one academic year can be marked current. Marking another year current clears the flag on all others.
            </div>
        </div>
    </div>

    <div class="modal fade" id="facultyCreateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.academics.faculties.store') }}">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Add faculty</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label" for="faculty_name">Name</label><input id="faculty_name" name="name" class="form-control" value="{{ old('name') }}" required maxlength="150"></div>
                        <div class="mb-3"><label class="form-label" for="faculty_code">Code</label><input id="faculty_code" name="code" class="form-control text-uppercase" value="{{ old('code') }}" required maxlength="20" placeholder="e.g. FCT"></div>
                        <div><label class="form-label" for="faculty_description">Description <span class="text-muted-2 fw-normal">(optional)</span></label><input id="faculty_description" name="description" class="form-control" value="{{ old('description') }}" maxlength="255"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Create faculty</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="facultyEditModal" tabindex="-1" aria-hidden="true" data-update-url="{{ route('admin.academics.faculties.update', ['faculty' => '__ID__']) }}">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header"><h5 class="modal-title">Edit faculty</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label" for="edit_faculty_name">Name</label><input id="edit_faculty_name" name="name" class="form-control" required maxlength="150"></div>
                        <div class="mb-3"><label class="form-label" for="edit_faculty_code">Code</label><input id="edit_faculty_code" name="code" class="form-control text-uppercase" required maxlength="20"></div>
                        <div><label class="form-label" for="edit_faculty_description">Description <span class="text-muted-2 fw-normal">(optional)</span></label><input id="edit_faculty_description" name="description" class="form-control" maxlength="255"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save changes</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="departmentCreateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.academics.departments.store') }}">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Add department</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="department_faculty">Faculty</label>
                            <select id="department_faculty" name="faculty_id" class="form-select" required>
                                <option value="">Select a faculty…</option>
                                @foreach($faculties as $faculty)
                                    <option value="{{ $faculty->id }}" @selected((int) old('faculty_id') === $faculty->id)>{{ $faculty->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3"><label class="form-label" for="department_name">Name</label><input id="department_name" name="name" class="form-control" value="{{ old('name') }}" required maxlength="150"></div>
                        <div class="mb-3"><label class="form-label" for="department_code">Code</label><input id="department_code" name="code" class="form-control text-uppercase" value="{{ old('code') }}" required maxlength="20" placeholder="e.g. CS"></div>
                        <div><label class="form-label" for="department_description">Description <span class="text-muted-2 fw-normal">(optional)</span></label><input id="department_description" name="description" class="form-control" value="{{ old('description') }}" maxlength="255"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Create department</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="departmentEditModal" tabindex="-1" aria-hidden="true" data-update-url="{{ route('admin.academics.departments.update', ['department' => '__ID__']) }}">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header"><h5 class="modal-title">Edit department</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="edit_department_faculty">Faculty</label>
                            <select id="edit_department_faculty" name="faculty_id" class="form-select" required>
                                @foreach($faculties as $faculty)
                                    <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3"><label class="form-label" for="edit_department_name">Name</label><input id="edit_department_name" name="name" class="form-control" required maxlength="150"></div>
                        <div class="mb-3"><label class="form-label" for="edit_department_code">Code</label><input id="edit_department_code" name="code" class="form-control text-uppercase" required maxlength="20"></div>
                        <div><label class="form-label" for="edit_department_description">Description <span class="text-muted-2 fw-normal">(optional)</span></label><input id="edit_department_description" name="description" class="form-control" maxlength="255"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save changes</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="courseCreateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.academics.courses.store') }}">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Add course</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="course_department">Department</label>
                            <select id="course_department" name="department_id" class="form-select" required>
                                <option value="">Select a department…</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" @selected((int) old('department_id') === $department->id)>{{ $department->name }} — {{ $department->faculty?->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3"><label class="form-label" for="course_name">Name</label><input id="course_name" name="name" class="form-control" value="{{ old('name') }}" required maxlength="150"></div>
                        <div class="row g-3 mb-3">
                            <div class="col-6"><label class="form-label" for="course_code">Code</label><input id="course_code" name="code" class="form-control text-uppercase" value="{{ old('code') }}" required maxlength="20"></div>
                            <div class="col-6"><label class="form-label" for="course_duration">Duration (years)</label><input id="course_duration" name="duration_years" type="number" min="1" max="6" class="form-control" value="{{ old('duration_years', 3) }}" required></div>
                        </div>
                        <div class="mb-3"><label class="form-label" for="course_level">Degree level <span class="text-muted-2 fw-normal">(optional)</span></label><input id="course_level" name="degree_level" class="form-control" value="{{ old('degree_level') }}" maxlength="60" placeholder="e.g. Bachelor"></div>
                        <div><label class="form-label" for="course_description">Description <span class="text-muted-2 fw-normal">(optional)</span></label><input id="course_description" name="description" class="form-control" value="{{ old('description') }}" maxlength="255"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Create course</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="courseEditModal" tabindex="-1" aria-hidden="true" data-update-url="{{ route('admin.academics.courses.update', ['course' => '__ID__']) }}">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header"><h5 class="modal-title">Edit course</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="edit_course_department">Department</label>
                            <select id="edit_course_department" name="department_id" class="form-select" required>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }} — {{ $department->faculty?->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3"><label class="form-label" for="edit_course_name">Name</label><input id="edit_course_name" name="name" class="form-control" required maxlength="150"></div>
                        <div class="row g-3 mb-3">
                            <div class="col-6"><label class="form-label" for="edit_course_code">Code</label><input id="edit_course_code" name="code" class="form-control text-uppercase" required maxlength="20"></div>
                            <div class="col-6"><label class="form-label" for="edit_course_duration">Duration (years)</label><input id="edit_course_duration" name="duration_years" type="number" min="1" max="6" class="form-control" required></div>
                        </div>
                        <div class="mb-3"><label class="form-label" for="edit_course_level">Degree level</label><input id="edit_course_level" name="degree_level" class="form-control" maxlength="60"></div>
                        <div><label class="form-label" for="edit_course_description">Description</label><input id="edit_course_description" name="description" class="form-control" maxlength="255"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save changes</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="yearCreateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.academics.years.store') }}">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Add academic year</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label" for="year_name">Name</label><input id="year_name" name="name" class="form-control" value="{{ old('name') }}" required maxlength="30" placeholder="e.g. 2025/2026"></div>
                        <div class="row g-3 mb-3">
                            <div class="col-6"><label class="form-label" for="year_start">Start date</label><input id="year_start" name="start_date" type="date" class="form-control" value="{{ old('start_date') }}" required></div>
                            <div class="col-6"><label class="form-label" for="year_end">End date</label><input id="year_end" name="end_date" type="date" class="form-control" value="{{ old('end_date') }}" required></div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="year_current" name="is_current" @checked(old('is_current'))>
                            <label class="form-check-label" for="year_current">Set as the current academic year</label>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Create academic year</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="yearEditModal" tabindex="-1" aria-hidden="true" data-update-url="{{ route('admin.academics.years.update', ['academicYear' => '__ID__']) }}">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header"><h5 class="modal-title">Edit academic year</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label" for="edit_year_name">Name</label><input id="edit_year_name" name="name" class="form-control" required maxlength="30"></div>
                        <div class="row g-3 mb-3">
                            <div class="col-6"><label class="form-label" for="edit_year_start">Start date</label><input id="edit_year_start" name="start_date" type="date" class="form-control" required></div>
                            <div class="col-6"><label class="form-label" for="edit_year_end">End date</label><input id="edit_year_end" name="end_date" type="date" class="form-control" required></div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="edit_year_current" name="is_current">
                            <label class="form-check-label" for="edit_year_current">Current academic year</label>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save changes</button></div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const bindEdit = (modalId, mapping) => {
                const modal = document.getElementById(modalId);
                if (!modal) return;

                modal.addEventListener('show.bs.modal', event => {
                    const trigger = event.relatedTarget;
                    if (!trigger) return;
                    const data = trigger.dataset;
                    const form = modal.querySelector('form');
                    form.action = modal.dataset.updateUrl.replace('__ID__', data.id);

                    Object.entries(mapping).forEach(([field, key]) => {
                        const input = form.elements.namedItem(field);
                        if (!input) return;
                        if (input.type === 'checkbox') {
                            input.checked = data[key] === '1';
                        } else {
                            input.value = data[key] ?? '';
                        }
                    });
                });
            };

            bindEdit('facultyEditModal', { name: 'name', code: 'code', description: 'description' });
            bindEdit('departmentEditModal', { faculty_id: 'faculty', name: 'name', code: 'code', description: 'description' });
            bindEdit('courseEditModal', { department_id: 'department', name: 'name', code: 'code', degree_level: 'level', duration_years: 'duration', description: 'description' });
            bindEdit('yearEditModal', { name: 'name', start_date: 'start', end_date: 'end', is_current: 'current' });
        })();
    </script>
@endpush
