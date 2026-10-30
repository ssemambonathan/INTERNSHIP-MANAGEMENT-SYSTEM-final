<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Models\Student;
use App\Services\AttendanceStatsService;
use App\Services\LogbookService;
use App\Services\RiskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class StudentController extends Controller
{
    public function index(Request $request, AttendanceStatsService $stats, RiskService $risk): View
    {
        $lecturer = $request->user()->lecturer;

        abort_unless($lecturer, 403, 'Your lecturer profile is not linked to this account.');

        $status = (string) $request->query('status', 'active');
        $riskFilter = (string) $request->query('risk', '');
        $search = trim((string) $request->query('q'));

        $internships = Internship::query()
            ->where('lecturer_id', $lecturer->id)
            ->with(['student.user', 'student.department', 'student.course', 'company', 'period', 'supervisor.user'])
            ->when(in_array($status, ['active', 'completed', 'pending', 'cancelled'], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('student', function ($studentQuery) use ($search) {
                    $studentQuery->where('registration_number', 'like', '%'.$search.'%')
                        ->orWhere('student_number', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->orderByDesc('start_date')
            ->get()
            ->map(function (Internship $internship) use ($stats, $risk) {
                $internship->attendance_percentage = $stats->attendancePercentage($internship);
                $internship->expected_days = $stats->expectedWorkingDays($internship);
                $internship->attended_days = $stats->attendedDays($internship);
                $internship->missed_logs_count = $stats->missedLogDates($internship)->count();
                $internship->pending_logs_count = $internship->dailyLogs()->pendingReview()->count();
                $internship->risk_assessment = $risk->assess($internship);

                return $internship;
            });

        $summary = [
            'total' => $internships->count(),
            'at_risk' => $internships->filter(fn (Internship $internship) => $internship->risk_assessment->hasReasons())->count(),
            'high_risk' => $internships->filter(fn (Internship $internship) => $internship->risk_assessment->level->value === 'high_risk')->count(),
            'average_attendance' => $internships->isEmpty() ? 0 : round($internships->avg('attendance_percentage'), 1),
        ];

        if (in_array($riskFilter, ['normal', 'warning', 'high_risk'], true)) {
            $internships = $internships
                ->filter(fn (Internship $internship) => $internship->risk_assessment->level->value === $riskFilter)
                ->values();
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 12;
        $paginated = new LengthAwarePaginator(
            $internships->forPage($page, $perPage)->values(),
            $internships->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('lecturer.students.index', compact('lecturer', 'paginated', 'status', 'search', 'riskFilter', 'summary'));
    }

    public function show(Request $request, Student $student, AttendanceStatsService $stats, RiskService $risk): View
    {
        $lecturer = $request->user()->lecturer;

        abort_unless($lecturer, 403, 'Your lecturer profile is not linked to this account.');

        Gate::authorize('view', $student);

        $internship = $student->internships()
            ->where('lecturer_id', $lecturer->id)
            ->with([
                'company',
                'period',
                'supervisor.user',
                'lecturer.user',
                'student.user',
                'student.faculty',
                'student.department',
                'student.course',
                'student.academicYear',
            ])
            ->orderByDesc('start_date')
            ->first();

        abort_unless($internship, 404, 'This student is not assigned to you.');

        $percentage = $stats->attendancePercentage($internship);
        $expectedDays = $stats->expectedWorkingDays($internship);
        $attendedDays = $stats->attendedDays($internship);
        $assessment = $risk->assess($internship);
        $missedDates = $stats->missedLogDates($internship);

        $attendances = $internship->attendances()
            ->with('reviewer')
            ->orderByDesc('date')
            ->limit(20)
            ->get();

        $logs = $internship->dailyLogs()
            ->withCount('evidenceFiles')
            ->with('reviewer')
            ->orderByDesc('date')
            ->limit(20)
            ->get();

        $logCounts = $internship->dailyLogs()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $evaluations = $internship->evaluations()
            ->orderByDesc('week_start')
            ->get();

        $visits = $internship->visits()
            ->withCount('evidenceFiles')
            ->orderByDesc('visit_date')
            ->get();

        $reopenings = $internship->reopenings()
            ->with('reopenedBy')
            ->orderByDesc('date')
            ->get();

        $eligibleDates = $missedDates
            ->filter(fn (Carbon $date) => $date->lt(today()) && ! $internship->isReopenedFor($date))
            ->sortDesc()
            ->values();

        return view('lecturer.students.show', compact(
            'student',
            'internship',
            'percentage',
            'expectedDays',
            'attendedDays',
            'assessment',
            'missedDates',
            'attendances',
            'logs',
            'logCounts',
            'evaluations',
            'visits',
            'reopenings',
            'eligibleDates',
        ));
    }

    public function reopenDate(Request $request, Internship $internship, LogbookService $logbook): RedirectResponse
    {
        Gate::authorize('reopenLogDate', $internship);

        $data = $request->validate([
            'date' => ['required', 'date', 'before:today'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'date.before' => 'Only a past date can be reopened.',
        ]);

        try {
            $logbook->reopenDate($internship, Carbon::parse($data['date']), $request->user(), $data['reason']);
        } catch (Throwable $exception) {
            if ($exception instanceof ValidationException) {
                throw $exception;
            }

            report($exception);

            return redirect()->back()->withInput()->with('error', 'The date could not be reopened. Please try again.');
        }

        return redirect()
            ->route('lecturer.students.show', $internship->student_id)
            ->with('success', 'The date was reopened and the student has been notified.');
    }
}
