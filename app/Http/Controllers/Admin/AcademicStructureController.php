<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AcademicStructureController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(): View
    {
        $faculties = Faculty::query()
            ->with(['departments' => fn ($query) => $query->withCount(['courses', 'students', 'lecturers'])->orderBy('name')])
            ->withCount('students')
            ->orderBy('name')
            ->get();

        $departments = Department::query()
            ->with('faculty')
            ->withCount(['courses', 'students', 'lecturers'])
            ->orderBy('name')
            ->get();

        $courses = Course::query()
            ->with('department.faculty')
            ->withCount('students')
            ->orderBy('name')
            ->get();

        $academicYears = AcademicYear::query()
            ->withCount(['internshipPeriods', 'students'])
            ->orderByDesc('start_date')
            ->get();

        $counts = [
            'faculties' => $faculties->count(),
            'departments' => $departments->count(),
            'courses' => $courses->count(),
            'academic_years' => $academicYears->count(),
        ];

        return view('admin.academics.index', compact('faculties', 'departments', 'courses', 'academicYears', 'counts'));
    }

    public function storeFaculty(Request $request): RedirectResponse
    {
        $data = $this->validateFaculty($request);

        $faculty = Faculty::create($data);

        $this->audit->record(
            'academic.faculty_created',
            'academics',
            sprintf('%s created the faculty %s (%s).', $request->user()->name, $faculty->name, $faculty->code),
            $faculty,
            $request->user(),
        );

        return redirect()->route('admin.academics.index')->with('success', 'Faculty created.');
    }

    public function updateFaculty(Request $request, Faculty $faculty): RedirectResponse
    {
        $data = $this->validateFaculty($request, $faculty);

        $faculty->update($data);

        $this->audit->record(
            'academic.faculty_updated',
            'academics',
            sprintf('%s updated the faculty %s.', $request->user()->name, $faculty->name),
            $faculty,
            $request->user(),
        );

        return redirect()->route('admin.academics.index')->with('success', 'Faculty updated.');
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $data = $this->validateDepartment($request);

        $department = Department::create($data);
        $department->load('faculty');

        $this->audit->record(
            'academic.department_created',
            'academics',
            sprintf('%s created the department %s under %s.', $request->user()->name, $department->name, $department->faculty->name),
            $department,
            $request->user(),
        );

        return redirect()->route('admin.academics.index')->with('success', 'Department created.');
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $data = $this->validateDepartment($request, $department);

        $department->update($data);

        $this->audit->record(
            'academic.department_updated',
            'academics',
            sprintf('%s updated the department %s.', $request->user()->name, $department->name),
            $department,
            $request->user(),
        );

        return redirect()->route('admin.academics.index')->with('success', 'Department updated.');
    }

    public function storeCourse(Request $request): RedirectResponse
    {
        $data = $this->validateCourse($request);

        $course = Course::create($data);
        $course->load('department');

        $this->audit->record(
            'academic.course_created',
            'academics',
            sprintf('%s created the course %s (%s).', $request->user()->name, $course->name, $course->code),
            $course,
            $request->user(),
        );

        return redirect()->route('admin.academics.index')->with('success', 'Course created.');
    }

    public function updateCourse(Request $request, Course $course): RedirectResponse
    {
        $data = $this->validateCourse($request, $course);

        $course->update($data);

        $this->audit->record(
            'academic.course_updated',
            'academics',
            sprintf('%s updated the course %s.', $request->user()->name, $course->name),
            $course,
            $request->user(),
        );

        return redirect()->route('admin.academics.index')->with('success', 'Course updated.');
    }

    public function storeAcademicYear(Request $request): RedirectResponse
    {
        $data = $this->validateAcademicYear($request);

        $year = DB::transaction(function () use ($data) {
            $year = AcademicYear::create($data);

            if ($year->is_current) {
                AcademicYear::query()->whereKeyNot($year->getKey())->update(['is_current' => false]);
            }

            return $year;
        });

        $this->audit->record(
            'academic.year_created',
            'academics',
            sprintf('%s created the academic year %s.', $request->user()->name, $year->name),
            $year,
            $request->user(),
        );

        return redirect()->route('admin.academics.index')->with('success', 'Academic year created.');
    }

    public function updateAcademicYear(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $data = $this->validateAcademicYear($request, $academicYear);

        DB::transaction(function () use ($academicYear, $data) {
            $academicYear->update($data);

            if ($academicYear->is_current) {
                AcademicYear::query()->whereKeyNot($academicYear->getKey())->update(['is_current' => false]);
            }
        });

        $this->audit->record(
            'academic.year_updated',
            'academics',
            sprintf('%s updated the academic year %s.', $request->user()->name, $academicYear->name),
            $academicYear,
            $request->user(),
        );

        return redirect()->route('admin.academics.index')->with('success', 'Academic year updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateFaculty(Request $request, ?Faculty $faculty = null): array
    {
        $request->merge(['code' => Str::upper(trim((string) $request->input('code')))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('faculties', 'name')->ignore($faculty?->id)],
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('faculties', 'code')->ignore($faculty?->id)],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateDepartment(Request $request, ?Department $department = null): array
    {
        $request->merge(['code' => Str::upper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'faculty_id' => ['required', 'integer', Rule::exists('faculties', 'id')],
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('departments', 'name')->where(fn ($query) => $query->where('faculty_id', $request->integer('faculty_id')))->ignore($department?->id),
            ],
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('departments', 'code')->ignore($department?->id)],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if ($department && (int) $department->faculty_id !== (int) $data['faculty_id']) {
            $hasStudents = $department->students()->exists();
            $hasLecturers = $department->lecturers()->exists();

            if ($hasStudents || $hasLecturers) {
                throw ValidationException::withMessages([
                    'faculty_id' => 'This department has students or lecturers assigned. Move them before changing the faculty.',
                ]);
            }
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCourse(Request $request, ?Course $course = null): array
    {
        $request->merge(['code' => Str::upper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('courses', 'name')->where(fn ($query) => $query->where('department_id', $request->integer('department_id')))->ignore($course?->id),
            ],
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('courses', 'code')->ignore($course?->id)],
            'degree_level' => ['nullable', 'string', 'max:60'],
            'duration_years' => ['required', 'integer', 'between:1,6'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if ($course && (int) $course->department_id !== (int) $data['department_id'] && $course->students()->exists()) {
            throw ValidationException::withMessages([
                'department_id' => 'This course has students assigned. Move them before changing the department.',
            ]);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAcademicYear(Request $request, ?AcademicYear $academicYear = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:30', Rule::unique('academic_years', 'name')->ignore($academicYear?->id)],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after:start_date'],
            'is_current' => ['nullable', 'boolean'],
        ]);

        $data['is_current'] = $request->boolean('is_current');

        return $data;
    }
}
