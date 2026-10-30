<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InternshipStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\AttendanceStatsService;
use App\Services\AuditService;
use App\Services\LogbookService;
use App\Services\RiskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class StudentController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly LogbookService $logbook,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $facultyId = $request->integer('faculty_id') ?: null;
        $status = (string) $request->query('status', '');
        $placement = (string) $request->query('placement', '');

        $students = Student::query()
            ->with(['user', 'faculty', 'department', 'course', 'latestInternship.company'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($studentQuery) use ($search) {
                    $studentQuery->where('registration_number', 'like', '%'.$search.'%')
                        ->orWhere('student_number', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
                });
            })
            ->when($facultyId, fn ($query) => $query->where('faculty_id', $facultyId))
            ->when(in_array($status, ['active', 'inactive', 'suspended'], true), fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery->where('status', $status)))
            ->when($placement === 'active', fn ($query) => $query->whereHas('internships', fn ($internshipQuery) => $internshipQuery->where('status', 'active')))
            ->when($placement === 'none', fn ($query) => $query->whereDoesntHave('internships'))
            ->orderBy('registration_number')
            ->paginate(15)
            ->withQueryString();

        $faculties = Faculty::query()->orderBy('name')->get();

        $counts = [
            'total' => Student::query()->count(),
            'active' => Student::query()->whereHas('user', fn ($query) => $query->where('status', UserStatus::Active))->count(),
            'placed' => Student::query()->whereHas('internships', fn ($query) => $query->where('status', 'active'))->count(),
            'unplaced' => Student::query()->whereDoesntHave('internships')->count(),
        ];

        return view('admin.students.index', compact('students', 'faculties', 'search', 'facultyId', 'status', 'placement', 'counts'));
    }

    public function create(): View
    {
        return view('admin.students.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $roleId = Role::query()->where('name', RoleName::Student->value)->value('id');

        $student = DB::transaction(function () use ($data, $roleId) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'role_id' => $roleId,
                'status' => $data['status'] ?? UserStatus::Active->value,
            ]);

            return $user->student()->create([
                'registration_number' => $data['registration_number'],
                'student_number' => $data['student_number'] ?? null,
                'faculty_id' => $data['faculty_id'],
                'department_id' => $data['department_id'],
                'course_id' => $data['course_id'],
                'academic_year_id' => $data['academic_year_id'] ?? null,
                'year_of_study' => $data['year_of_study'],
            ]);
        });

        $this->audit->record(
            'student.created',
            'students',
            sprintf('%s created the student account for %s (%s).', $request->user()->name, $student->user->name, $student->registration_number),
            $student,
            $request->user(),
        );

        return redirect()->route('admin.students.show', $student)->with('success', 'Student account created successfully.');
    }

    public function show(Request $request, Student $student, AttendanceStatsService $stats, RiskService $risk): View
    {
        $student->load(['user', 'faculty', 'department', 'course', 'academicYear']);

        $internships = $student->internships()
            ->with(['company', 'supervisor.user', 'lecturer.user', 'period'])
            ->withCount(['dailyLogs', 'attendances'])
            ->orderByDesc('start_date')
            ->get();

        $internship = $internships->firstWhere('status', InternshipStatus::Active) ?? $internships->first();

        $attendances = collect();
        $logs = collect();
        $evaluations = collect();
        $reopenings = collect();
        $assessment = null;
        $percentage = 0.0;

        if ($internship) {
            $attendances = $internship->attendances()->with('reviewer')->orderByDesc('date')->limit(10)->get();
            $logs = $internship->dailyLogs()->withCount('evidenceFiles')->orderByDesc('date')->limit(10)->get();
            $evaluations = $internship->evaluations()->orderByDesc('week_start')->limit(10)->get();
            $reopenings = $internship->reopenings()->with('reopenedBy')->orderByDesc('date')->get();
            $assessment = $risk->assess($internship);
            $percentage = $stats->attendancePercentage($internship);
        }

        $logStatusCounts = $student->dailyLogs()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.students.show', compact(
            'student',
            'internships',
            'internship',
            'attendances',
            'logs',
            'evaluations',
            'reopenings',
            'assessment',
            'percentage',
            'logStatusCounts',
        ));
    }

    public function edit(Student $student): View
    {
        $student->load('user');

        return view('admin.students.edit', array_merge(
            ['student' => $student],
            $this->formData(),
        ));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $data = $this->validated($request, $student);

        DB::transaction(function () use ($student, $data) {
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'] ?? $student->user->status->value,
            ];

            if (filled($data['password'] ?? null)) {
                $userData['password'] = $data['password'];
            }

            $student->user->update($userData);

            $student->update([
                'registration_number' => $data['registration_number'],
                'student_number' => $data['student_number'] ?? null,
                'faculty_id' => $data['faculty_id'],
                'department_id' => $data['department_id'],
                'course_id' => $data['course_id'],
                'academic_year_id' => $data['academic_year_id'] ?? null,
                'year_of_study' => $data['year_of_study'],
            ]);
        });

        $this->audit->record(
            'student.updated',
            'students',
            sprintf('%s updated the student profile for %s (%s).', $request->user()->name, $student->user->name, $student->registration_number),
            $student,
            $request->user(),
        );

        return redirect()->route('admin.students.show', $student)->with('success', 'Student profile updated.');
    }

    public function toggleStatus(Request $request, Student $student): RedirectResponse
    {
        $newStatus = $student->user->status === UserStatus::Active
            ? UserStatus::Suspended
            : UserStatus::Active;

        $student->user->update(['status' => $newStatus]);

        $this->audit->record(
            'student.status_changed',
            'students',
            sprintf('%s set the account of %s to %s.', $request->user()->name, $student->user->name, $newStatus->label()),
            $student,
            $request->user(),
        );

        return redirect()->back()->with('success', 'Account status changed to '.$newStatus->label().'.');
    }

    public function reopenDate(Request $request, Student $student): RedirectResponse
    {
        $internship = $student->internships()
            ->orderByRaw("FIELD(status, 'active', 'pending', 'completed', 'cancelled')")
            ->orderByDesc('start_date')
            ->first();

        if (! $internship) {
            return redirect()->back()->with('error', 'This student has no internship placement to reopen a date for.');
        }

        $data = $request->validate([
            'date' => ['required', 'date', 'before:today'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        try {
            $this->logbook->reopenDate($internship, Carbon::parse($data['date']), $request->user(), $data['reason']);
        } catch (Throwable $exception) {
            if ($exception instanceof ValidationException) {
                throw $exception;
            }

            report($exception);

            return redirect()->back()->withInput()->with('error', 'The date could not be reopened. Please try again.');
        }

        return redirect()->route('admin.students.show', $student)->with('success', 'The date was reopened and the student has been notified.');
    }

    public function importForm(): View
    {
        return view('admin.students.import');
    }

    public function template(): StreamedResponse
    {
        $columns = [
            'name',
            'email',
            'password',
            'phone',
            'registration_number',
            'student_number',
            'faculty_code',
            'department_code',
            'course_code',
            'year_of_study',
            'academic_year',
        ];

        return response()->streamDownload(function () use ($columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);
            fputcsv($handle, [
                'Jane Nakato',
                'jane.nakato@student.test',
                'ChangeMe123!',
                '+256 700 000 000',
                '2026/CS/100',
                'STU-2026-100',
                'FCT',
                'CS',
                'BSC-CS',
                '3',
                AcademicYear::query()->where('is_current', true)->value('name') ?? '2026/2027',
            ]);
            fclose($handle);
        }, 'student-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ], [
            'file.mimes' => 'Upload a CSV file (plain text, .csv).',
            'file.max' => 'The CSV file may not be larger than 2 MB.',
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'rb');

        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'The CSV file could not be read.']);
        }

        $required = [
            'name', 'email', 'password', 'registration_number', 'faculty_code',
            'department_code', 'course_code', 'year_of_study',
        ];

        $header = null;
        $rows = [];
        $line = 0;

        while (($values = fgetcsv($handle)) !== false) {
            $line++;

            if ($values === [null] || trim(implode('', array_map(fn ($value) => (string) $value, $values))) === '') {
                continue;
            }

            if ($header === null) {
                $header = array_map(function ($value) {
                    $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);

                    return preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($value)));
                }, $values);

                $missing = array_diff($required, $header);

                if ($missing !== []) {
                    fclose($handle);

                    throw ValidationException::withMessages([
                        'file' => 'The CSV is missing required columns: '.implode(', ', $missing).'.',
                    ]);
                }

                continue;
            }

            if (count($rows) >= 500) {
                fclose($handle);

                throw ValidationException::withMessages(['file' => 'A single import may contain at most 500 student rows.']);
            }

            $values = array_pad($values, count($header), null);
            $rows[] = ['line' => $line, 'data' => array_combine($header, array_slice($values, 0, count($header)))];
        }

        fclose($handle);

        if ($header === null || $rows === []) {
            throw ValidationException::withMessages(['file' => 'The CSV contains no student rows.']);
        }

        $roleId = Role::query()->where('name', RoleName::Student->value)->value('id');
        $errors = [];
        $prepared = [];
        $seenEmails = [];
        $seenRegistrations = [];
        $seenStudentNumbers = [];

        foreach ($rows as $row) {
            $lineNumber = $row['line'];
            $data = $row['data'];

            $payload = [
                'name' => trim((string) ($data['name'] ?? '')),
                'email' => strtolower(trim((string) ($data['email'] ?? ''))),
                'password' => (string) ($data['password'] ?? ''),
                'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
                'registration_number' => trim((string) ($data['registration_number'] ?? '')),
                'student_number' => trim((string) ($data['student_number'] ?? '')) ?: null,
                'faculty_code' => strtoupper(trim((string) ($data['faculty_code'] ?? ''))),
                'department_code' => strtoupper(trim((string) ($data['department_code'] ?? ''))),
                'course_code' => strtoupper(trim((string) ($data['course_code'] ?? ''))),
                'year_of_study' => trim((string) ($data['year_of_study'] ?? '')),
                'academic_year' => trim((string) ($data['academic_year'] ?? '')) ?: null,
            ];

            $validator = Validator::make($payload, [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
                'password' => ['required', 'string', 'min:8', 'max:72'],
                'phone' => ['nullable', 'string', 'max:30'],
                'registration_number' => ['required', 'string', 'max:40', Rule::unique('students', 'registration_number')],
                'student_number' => ['nullable', 'string', 'max:40', Rule::unique('students', 'student_number')],
                'faculty_code' => ['required', 'string', Rule::exists('faculties', 'code')],
                'department_code' => ['required', 'string', Rule::exists('departments', 'code')],
                'course_code' => ['required', 'string', Rule::exists('courses', 'code')],
                'year_of_study' => ['required', 'integer', 'between:1,6'],
                'academic_year' => ['nullable', 'string', Rule::exists('academic_years', 'name')],
            ]);

            if ($validator->fails()) {
                $errors[] = 'Row '.$lineNumber.': '.implode(' ', $validator->errors()->all());

                continue;
            }

            if (isset($seenEmails[$payload['email']])) {
                $errors[] = 'Row '.$lineNumber.': email duplicates row '.$seenEmails[$payload['email']].'.';

                continue;
            }

            if (isset($seenRegistrations[$payload['registration_number']])) {
                $errors[] = 'Row '.$lineNumber.': registration number duplicates row '.$seenRegistrations[$payload['registration_number']].'.';

                continue;
            }

            if ($payload['student_number'] !== null && isset($seenStudentNumbers[$payload['student_number']])) {
                $errors[] = 'Row '.$lineNumber.': student number duplicates row '.$seenStudentNumbers[$payload['student_number']].'.';

                continue;
            }

            $faculty = Faculty::query()->where('code', $payload['faculty_code'])->first();
            $department = Department::query()->where('code', $payload['department_code'])->first();
            $course = Course::query()->where('code', $payload['course_code'])->first();

            if (! $faculty || ! $department || ! $course) {
                $errors[] = 'Row '.$lineNumber.': invalid academic structure codes.';

                continue;
            }

            if ($department->faculty_id !== $faculty->id) {
                $errors[] = 'Row '.$lineNumber.': department '.$department->code.' does not belong to faculty '.$faculty->code.'.';

                continue;
            }

            if ($course->department_id !== $department->id) {
                $errors[] = 'Row '.$lineNumber.': course '.$course->code.' does not belong to department '.$department->code.'.';

                continue;
            }

            $seenEmails[$payload['email']] = $lineNumber;
            $seenRegistrations[$payload['registration_number']] = $lineNumber;

            if ($payload['student_number'] !== null) {
                $seenStudentNumbers[$payload['student_number']] = $lineNumber;
            }

            $prepared[] = [
                'user' => [
                    'name' => $payload['name'],
                    'email' => $payload['email'],
                    'phone' => $payload['phone'],
                    'password' => $payload['password'],
                    'role_id' => $roleId,
                    'status' => UserStatus::Active->value,
                ],
                'student' => [
                    'registration_number' => $payload['registration_number'],
                    'student_number' => $payload['student_number'],
                    'faculty_id' => $faculty->id,
                    'department_id' => $department->id,
                    'course_id' => $course->id,
                    'academic_year_id' => $payload['academic_year'] ? AcademicYear::query()->where('name', $payload['academic_year'])->value('id') : null,
                    'year_of_study' => (int) $payload['year_of_study'],
                ],
            ];
        }

        if ($errors !== []) {
            $messages = array_slice($errors, 0, 25);

            if (count($errors) > 25) {
                $messages[] = '…and '.(count($errors) - 25).' more error(s). Fix the CSV and upload it again.';
            }

            throw ValidationException::withMessages(['file' => $messages]);
        }

        DB::transaction(function () use ($prepared) {
            foreach ($prepared as $row) {
                $user = User::create($row['user']);
                $user->student()->create($row['student']);
            }
        });

        $this->audit->record(
            'students.imported',
            'students',
            sprintf('%s imported %d student account(s) from a CSV file.', $request->user()->name, count($prepared)),
            null,
            $request->user(),
        );

        return redirect()->route('admin.students.index')->with('success', count($prepared).' student account(s) imported successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'faculties' => Faculty::query()->orderBy('name')->get(),
            'departments' => Department::query()->with('faculty')->orderBy('name')->get(),
            'courses' => Course::query()->with('department')->orderBy('name')->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Student $student = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($student?->user_id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [$student ? 'nullable' : 'required', 'string', 'min:8', 'max:72', 'confirmed'],
            'registration_number' => ['required', 'string', 'max:40', Rule::unique('students', 'registration_number')->ignore($student?->id)],
            'student_number' => ['nullable', 'string', 'max:40', Rule::unique('students', 'student_number')->ignore($student?->id)],
            'faculty_id' => ['required', 'integer', Rule::exists('faculties', 'id')],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')],
            'academic_year_id' => ['nullable', 'integer', Rule::exists('academic_years', 'id')],
            'year_of_study' => ['required', 'integer', 'between:1,6'],
            'status' => ['nullable', Rule::enum(UserStatus::class)],
        ]);

        $department = Department::query()->find($data['department_id']);
        $course = Course::query()->find($data['course_id']);

        if ($department && (int) $department->faculty_id !== (int) $data['faculty_id']) {
            throw ValidationException::withMessages(['department_id' => 'The selected department does not belong to the chosen faculty.']);
        }

        if ($course && (int) $course->department_id !== (int) $data['department_id']) {
            throw ValidationException::withMessages(['course_id' => 'The selected course does not belong to the chosen department.']);
        }

        if ($student && blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data;
    }
}
