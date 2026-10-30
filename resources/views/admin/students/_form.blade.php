@php
    $current = $student ?? null;
    $currentUser = $current?->user;
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @method($method)

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="name">Full name</label>
            <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $currentUser?->name) }}" required maxlength="120" autocomplete="name">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="email">Email address</label>
            <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $currentUser?->email) }}" required maxlength="150" autocomplete="email">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="phone">Phone number</label>
            <input id="phone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $currentUser?->phone) }}" maxlength="30">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="status">Account status</label>
            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                @foreach(\App\Enums\UserStatus::cases() as $case)
                    <option value="{{ $case->value }}" @selected(old('status', $currentUser?->status?->value ?? 'active') === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label" for="password">{{ $current ? 'Reset password (optional)' : 'Temporary password' }}</label>
            <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" {{ $current ? '' : 'required' }} autocomplete="new-password" minlength="8">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @if($current)
                <div class="form-text">Leave blank to keep the current password.</div>
            @else
                <div class="form-text">At least 8 characters. The student should change it after first login.</div>
            @endif
        </div>
        <div class="col-md-6">
            <label class="form-label" for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" {{ $current ? '' : 'required' }} autocomplete="new-password">
        </div>

        <div class="col-md-6">
            <label class="form-label" for="registration_number">Registration number</label>
            <input id="registration_number" name="registration_number" type="text" class="form-control @error('registration_number') is-invalid @enderror" value="{{ old('registration_number', $current?->registration_number) }}" required maxlength="40">
            @error('registration_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="student_number">Student number</label>
            <input id="student_number" name="student_number" type="text" class="form-control @error('student_number') is-invalid @enderror" value="{{ old('student_number', $current?->student_number) }}" maxlength="40">
            @error('student_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label" for="faculty_id">Faculty</label>
            <select id="faculty_id" name="faculty_id" class="form-select @error('faculty_id') is-invalid @enderror" required>
                <option value="">Select faculty</option>
                @foreach($faculties as $faculty)
                    <option value="{{ $faculty->id }}" @selected((string) old('faculty_id', $current?->faculty_id) === (string) $faculty->id)>{{ $faculty->name }}</option>
                @endforeach
            </select>
            @error('faculty_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="department_id">Department</label>
            <select id="department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                <option value="">Select department</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" data-faculty="{{ $department->faculty_id }}" @selected((string) old('department_id', $current?->department_id) === (string) $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
            @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="course_id">Course</label>
            <select id="course_id" name="course_id" class="form-select @error('course_id') is-invalid @enderror" required>
                <option value="">Select course</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" data-department="{{ $course->department_id }}" @selected((string) old('course_id', $current?->course_id) === (string) $course->id)>{{ $course->name }} ({{ $course->code }})</option>
                @endforeach
            </select>
            @error('course_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="academic_year_id">Academic year</label>
            <select id="academic_year_id" name="academic_year_id" class="form-select @error('academic_year_id') is-invalid @enderror">
                <option value="">Not set</option>
                @foreach($academicYears as $academicYear)
                    <option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id', $current?->academic_year_id) === (string) $academicYear->id)>{{ $academicYear->name }}</option>
                @endforeach
            </select>
            @error('academic_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="year_of_study">Year of study</label>
            <select id="year_of_study" name="year_of_study" class="form-select @error('year_of_study') is-invalid @enderror" required>
                @for($year = 1; $year <= 6; $year++)
                    <option value="{{ $year }}" @selected((string) old('year_of_study', $current?->year_of_study ?? 3) === (string) $year)>Year {{ $year }}</option>
                @endfor
            </select>
            @error('year_of_study')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>{{ $current ? 'Save changes' : 'Create student' }}</button>
        <a href="{{ $current ? route('admin.students.show', $current) : route('admin.students.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

@push('scripts')
<script>
(function () {
    const faculty = document.getElementById('faculty_id');
    const department = document.getElementById('department_id');
    const course = document.getElementById('course_id');

    if (!faculty || !department || !course) return;

    const departments = Array.from(department.options);
    const courses = Array.from(course.options);

    function filterDepartments() {
        departments.forEach((option) => {
            if (!option.value) return;
            const visible = option.dataset.faculty === faculty.value;
            option.hidden = !visible;
            option.disabled = !visible;
        });

        if (department.selectedOptions[0]?.disabled) {
            department.value = '';
        }
    }

    function filterCourses() {
        courses.forEach((option) => {
            if (!option.value) return;
            const visible = option.dataset.department === department.value;
            option.hidden = !visible;
            option.disabled = !visible;
        });

        if (course.selectedOptions[0]?.disabled) {
            course.value = '';
        }
    }

    faculty.addEventListener('change', () => {
        department.value = '';
        course.value = '';
        filterDepartments();
        filterCourses();
    });

    department.addEventListener('change', () => {
        course.value = '';
        filterCourses();
    });

    filterDepartments();
    filterCourses();
})();
</script>
@endpush
