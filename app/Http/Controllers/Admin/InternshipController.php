<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InternshipStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySupervisor;
use App\Models\Internship;
use App\Models\InternshipPeriod;
use App\Models\Lecturer;
use App\Models\Student;
use App\Services\AttendanceStatsService;
use App\Services\AuditService;
use App\Services\RiskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InternshipController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status', '');
        $companyId = $request->integer('company_id') ?: null;
        $periodId = $request->integer('period_id') ?: null;

        $internships = Internship::query()
            ->with([
                'student.user',
                'student.department',
                'company',
                'supervisor.user',
                'lecturer.user',
                'period',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($internshipQuery) use ($search) {
                    $internshipQuery->whereHas('student', function ($studentQuery) use ($search) {
                        $studentQuery->where('registration_number', 'like', '%'.$search.'%')
                            ->orWhere('student_number', 'like', '%'.$search.'%')
                            ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'));
                    })->orWhereHas('company', fn ($companyQuery) => $companyQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when(in_array($status, ['pending', 'active', 'completed', 'cancelled'], true), fn ($query) => $query->where('status', $status))
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when($periodId, fn ($query) => $query->where('internship_period_id', $periodId))
            ->orderByDesc('start_date')
            ->paginate(15)
            ->withQueryString();

        $counts = Internship::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $companies = Company::query()->orderBy('name')->get();
        $periods = InternshipPeriod::query()->orderByDesc('start_date')->get();

        return view('admin.internships.index', compact('internships', 'counts', 'companies', 'periods', 'search', 'status', 'companyId', 'periodId'));
    }

    public function create(): View
    {
        return view('admin.internships.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $internship = Internship::create($data);

        $this->audit->record(
            'internship.created',
            'internships',
            sprintf('%s created a %s placement for %s at %s.', $request->user()->name, $internship->status->label(), $internship->student->user->name, $internship->company->name),
            $internship,
            $request->user(),
        );

        return redirect()->route('admin.internships.show', $internship)->with('success', 'Internship placement created.');
    }

    public function show(Internship $internship, AttendanceStatsService $stats, RiskService $risk): View
    {
        $internship->load([
            'student.user',
            'student.department',
            'student.faculty',
            'student.course',
            'company',
            'supervisor.user',
            'lecturer.user',
            'period.academicYear',
        ]);

        $percentage = $stats->attendancePercentage($internship);
        $expectedDays = $stats->expectedWorkingDays($internship);
        $attendedDays = $stats->attendedDays($internship);
        $assessment = $risk->assess($internship);

        $logCounts = $internship->dailyLogs()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $evaluations = $internship->evaluations()
            ->with('supervisor.user')
            ->orderByDesc('week_start')
            ->limit(6)
            ->get();

        $attendanceCounts = $internship->attendances()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $visits = $internship->visits()
            ->with('lecturer.user')
            ->orderByDesc('visit_date')
            ->limit(6)
            ->get();

        $recentLogs = $internship->dailyLogs()
            ->withCount('evidenceFiles')
            ->orderByDesc('date')
            ->limit(6)
            ->get();

        $lastAttendance = $internship->attendances()->orderByDesc('date')->limit(5)->get();

        return view('admin.internships.show', compact(
            'internship',
            'percentage',
            'expectedDays',
            'attendedDays',
            'assessment',
            'logCounts',
            'evaluations',
            'attendanceCounts',
            'visits',
            'recentLogs',
            'lastAttendance',
        ));
    }

    public function edit(Internship $internship): View
    {
        $internship->load(['student.user', 'company', 'supervisor.user', 'lecturer.user', 'period']);

        return view('admin.internships.edit', array_merge(
            ['internship' => $internship],
            $this->formData(),
        ));
    }

    public function update(Request $request, Internship $internship): RedirectResponse
    {
        $data = $this->validated($request, $internship);

        $hasRecords = $internship->attendances()->exists() || $internship->dailyLogs()->exists();

        if ($hasRecords) {
            if ((int) $data['student_id'] !== (int) $internship->student_id) {
                throw ValidationException::withMessages([
                    'student_id' => 'This placement already has attendance or logbook records. The student cannot be changed.',
                ]);
            }

            if ((int) $data['company_id'] !== (int) $internship->company_id) {
                throw ValidationException::withMessages([
                    'company_id' => 'This placement already has attendance records tied to the company geofence. The company cannot be changed.',
                ]);
            }
        }

        $internship->update($data);

        $this->audit->record(
            'internship.updated',
            'internships',
            sprintf('%s updated the placement for %s at %s.', $request->user()->name, $internship->student->user->name, $internship->company->name),
            $internship,
            $request->user(),
        );

        return redirect()->route('admin.internships.show', $internship)->with('success', 'Internship placement updated.');
    }

    public function updateStatus(Request $request, Internship $internship): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(InternshipStatus::class)],
        ]);

        $newStatus = InternshipStatus::from($data['status']);
        $previous = $internship->status;

        if ($newStatus === InternshipStatus::Active) {
            $this->assertCanBeActive($internship);
            $this->assertNoOverlap([
                'student_id' => $internship->student_id,
                'start_date' => $internship->start_date->toDateString(),
                'end_date' => $internship->end_date->toDateString(),
                'status' => $newStatus->value,
            ], $internship);
        }

        $internship->update(['status' => $newStatus]);

        $this->audit->record(
            'internship.status_changed',
            'internships',
            sprintf(
                '%s changed the placement of %s at %s from %s to %s.',
                $request->user()->name,
                $internship->student->user->name,
                $internship->company->name,
                $previous->label(),
                $newStatus->label(),
            ),
            $internship,
            $request->user(),
        );

        return redirect()->back()->with('success', 'Placement status changed to '.$newStatus->label().'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'students' => Student::query()->with(['user', 'department'])->orderBy('registration_number')->get(),
            'companies' => Company::query()->orderBy('name')->get(),
            'supervisors' => CompanySupervisor::query()->with(['user', 'company'])->get(),
            'lecturers' => Lecturer::query()->with(['user', 'department'])->get(),
            'periods' => InternshipPeriod::query()->with('academicYear')->orderByDesc('start_date')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Internship $internship = null): array
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')->whereNull('deleted_at')],
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'company_supervisor_id' => ['required', 'integer', Rule::exists('company_supervisors', 'id')->whereNull('deleted_at')],
            'lecturer_id' => ['required', 'integer', Rule::exists('lecturers', 'id')->whereNull('deleted_at')],
            'internship_period_id' => ['required', 'integer', Rule::exists('internship_periods', 'id')],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'status' => ['required', Rule::enum(InternshipStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $supervisor = CompanySupervisor::query()->with('user')->find($data['company_supervisor_id']);

        if ($supervisor && (int) $supervisor->company_id !== (int) $data['company_id']) {
            throw ValidationException::withMessages([
                'company_supervisor_id' => 'The selected supervisor is not attached to the chosen company.',
            ]);
        }

        $period = InternshipPeriod::query()->find($data['internship_period_id']);
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        if ($period && ($start->lt($period->start_date) || $end->gt($period->end_date))) {
            throw ValidationException::withMessages([
                'start_date' => 'The placement dates must fall within the selected internship period ('
                    .$period->start_date->format('d M Y').' – '.$period->end_date->format('d M Y').').',
            ]);
        }

        if ($internship) {
            $earliest = collect([
                $internship->attendances()->min('date'),
                $internship->dailyLogs()->withTrashed()->min('date'),
            ])->filter()->min();

            $latest = collect([
                $internship->attendances()->max('date'),
                $internship->dailyLogs()->withTrashed()->max('date'),
            ])->filter()->max();

            if ($earliest && $start->gt(Carbon::parse($earliest))) {
                throw ValidationException::withMessages([
                    'start_date' => 'The start date cannot be after existing attendance or logbook records (earliest '.Carbon::parse($earliest)->format('d M Y').').',
                ]);
            }

            if ($latest && $end->lt(Carbon::parse($latest))) {
                throw ValidationException::withMessages([
                    'end_date' => 'The end date cannot be before existing attendance or logbook records (latest '.Carbon::parse($latest)->format('d M Y').').',
                ]);
            }
        }

        if (in_array($data['status'], [InternshipStatus::Active->value, InternshipStatus::Pending->value], true)) {
            $this->assertNoOverlap($data, $internship);
        }

        if ($data['status'] === InternshipStatus::Active->value) {
            $draft = $internship ? tap(clone $internship)->fill($data) : new Internship($data);
            $this->assertCanBeActive($draft);
        }

        return $data;
    }

    private function assertCanBeActive(Internship $internship): void
    {
        $company = $internship->company;
        $period = $internship->period;
        $supervisor = $internship->supervisor;
        $lecturer = $internship->lecturer;

        if ($company && ! $company->isActive()) {
            throw ValidationException::withMessages([
                'status' => 'This placement cannot be active because the company is inactive.',
            ]);
        }

        if ($period && ! $period->isActive()) {
            throw ValidationException::withMessages([
                'status' => 'This placement cannot be active because its internship period is not open.',
            ]);
        }

        if ($supervisor && ! $supervisor->user?->isActive()) {
            throw ValidationException::withMessages([
                'status' => 'This placement cannot be active because the company supervisor account is not active.',
            ]);
        }

        if ($lecturer && ! $lecturer->user?->isActive()) {
            throw ValidationException::withMessages([
                'status' => 'This placement cannot be active because the lecturer account is not active.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertNoOverlap(array $data, ?Internship $ignore = null): void
    {
        $overlap = Internship::query()
            ->where('student_id', $data['student_id'])
            ->whereIn('status', [InternshipStatus::Active->value, InternshipStatus::Pending->value])
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->whereDate('start_date', '<=', $data['end_date'])
            ->whereDate('end_date', '>=', $data['start_date'])
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'student_id' => 'This student already has an active or pending placement that overlaps these dates.',
            ]);
        }
    }
}
