@extends('layouts.app')

@section('title', 'Import Students')
@section('page-title', 'Import Students')
@section('page-subtitle', 'Bulk-create validated student accounts from a CSV file')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('admin.students.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to students</a>
        <a href="{{ route('admin.students.import.template') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i>Download CSV template</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">Upload CSV</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.students.import') }}" enctype="multipart/form-data">
                        @csrf
                        <label class="form-label" for="file">CSV file</label>
                        <input id="file" type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".csv,text/csv,text/plain" required data-max-size-kb="2048">
                        <div class="form-text" data-file-hint="file">Plain CSV, maximum 2 MB and 500 student rows per file.</div>
                        @error('file')
                            <div class="alert alert-danger py-2 mt-3 mb-0" style="font-size:.8rem;">
                                <div class="fw-semibold mb-1">The file could not be imported:</div>
                                <ul class="mb-0 ps-3">
                                    @foreach($errors->get('file') as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @enderror

                        <div class="alert alert-info mt-3 mb-0" style="font-size:.8rem;">
                            <div class="fw-semibold mb-1"><i class="bi bi-shield-check me-1"></i>Import guarantees</div>
                            The import is atomic: if any row fails validation, no accounts are created. Passwords are hashed before storage and never written to logs or audit entries.
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-primary"><i class="bi bi-upload me-1"></i>Validate and import</button>
                            <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header">Required columns</div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach(['name', 'email', 'password', 'registration_number', 'faculty_code', 'department_code', 'course_code', 'year_of_study'] as $column)
                            <span class="badge badge-soft-primary">{{ $column }}</span>
                        @endforeach
                    </div>
                    <div class="section-label mt-3">Optional columns</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach(['phone', 'student_number', 'academic_year'] as $column)
                            <span class="badge badge-soft-secondary">{{ $column }}</span>
                        @endforeach
                    </div>
                    <div class="text-muted-2 mt-3" style="font-size:.78rem;">
                        Academic codes must already exist in the academic structure. Departments must belong to the stated faculty, and courses to the stated department.
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Rules</div>
                <div class="card-body">
                    <ul class="mb-0 ps-3 text-muted-2" style="font-size:.8rem;">
                        <li>The first row must contain the column headers.</li>
                        <li>Emails, registration numbers and student numbers must be unique, including within the file.</li>
                        <li>Passwords must contain at least 8 characters.</li>
                        <li>Duplicate or invalid rows are reported by line number; correct the file and upload it again.</li>
                        <li>Only the CSV text is read. Executable files and oversized uploads are rejected.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
