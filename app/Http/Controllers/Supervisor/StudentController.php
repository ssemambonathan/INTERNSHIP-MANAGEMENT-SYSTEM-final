<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\DailyLog;
use App\Models\Internship;
use App\Models\Student;
use App\Services\AttendanceStatsService;
use App\Services\RiskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request, AttendanceStatsService $stats, RiskService $risk): View
    {
        $supervisor = $request->user()->companySupervisor;

        abort_unless($supervisor, 403, 'Your supervisor profile is not linked to this account.');

        $status = $request->query('status', 'active');
        $search = trim((string) $request->query('q'));

        $internships = Internship::query()
            ->where('company_supervisor_id', $supervisor->id)
            ->with(['student.user', 'student.department', 'student.course', 'period', 'company'])
            ->when(in_array($status, ['active', 'completed', 'pending', 'cancelled'], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('student', function ($studentQuery) use ($search) {
                    $studentQuery->where('registration_number', 'like', '%'.$search.'%')
                        ->orWhere('student_number', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->orderByDesc('start_date')
            ->paginate(12)
            ->withQueryString();

        $internships->getCollection()->transform(function (Internship $internship) use ($stats, $risk) {
            $internship->attendance_percentage = $stats->attendancePercentage($internship);
            $internship->expected_days = $stats->expectedWorkingDays($internship);
            $internship->attended_days = $stats->attendedDays($internship);
            $internship->risk_assessment = $risk->assess($internship);
            $internship->pending_logs_count = $internship->dailyLogs()->pendingReview()->count();

            return $internship;
        });

        return view('supervisor.students.index', compact('supervisor', 'internships', 'status', 'search'));
    }

    public function show(Request $request, Student $student, AttendanceStatsService $stats, RiskService $risk): View
    {
        $supervisor = $request->user()->companySupervisor;

        abort_unless($supervisor, 403, 'Your supervisor profile is not linked to this account.');

        Gate::authorize('view', $student);

        $internship = $student->internships()
            ->where('company_supervisor_id', $supervisor->id)
            ->with(['company', 'period', 'lecturer.user'])
            ->orderByDesc('start_date')
            ->first();

        abort_unless($internship, 404, 'This student is not assigned to you.');

        $percentage = $stats->attendancePercentage($internship);
        $expectedDays = $stats->expectedWorkingDays($internship);
        $attendedDays = $stats->attendedDays($internship);
        $missedDates = $stats->missedLogDates($internship, now()->subDays(45));
        $assessment = $risk->assess($internship);

        $attendances = $internship->attendances()
            ->orderByDesc('date')
            ->limit(15)
            ->get();

        $logs = DailyLog::query()
            ->where('internship_id', $internship->id)
            ->withCount('evidenceFiles')
            ->orderByDesc('date')
            ->limit(12)
            ->get();

        $evaluations = $internship->evaluations()
            ->orderByDesc('week_start')
            ->get();

        return view('supervisor.students.show', compact(
            'student',
            'internship',
            'percentage',
            'expectedDays',
            'attendedDays',
            'missedDates',
            'assessment',
            'attendances',
            'logs',
            'evaluations',
        ));
    }
}
