<?php

namespace App\Http\Controllers\Student;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\DailyLog;
use App\Services\AttendanceStatsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, AttendanceStatsService $stats): View
    {
        $user = $request->user();
        $student = $user->student;

        if (! $student) {
            return view('student.dashboard', ['student' => null, 'internship' => null]);
        }

        $internship = $student->activeInternship()
            ->with(['company', 'supervisor.user', 'lecturer.user', 'period'])
            ->first();

        $todayAttendance = Attendance::query()
            ->where('student_id', $student->id)
            ->whereDate('date', today())
            ->first();

        $todayLog = DailyLog::query()
            ->where('student_id', $student->id)
            ->whereDate('date', today())
            ->first();

        $expectedDays = $internship ? $stats->expectedWorkingDays($internship) : 0;
        $attendedDays = $internship ? $stats->attendedDays($internship) : 0;
        $attendancePercentage = $internship ? $stats->attendancePercentage($internship) : 0.0;

        $approvedLogs = DailyLog::query()
            ->where('student_id', $student->id)
            ->where('status', DailyLogStatus::Approved)
            ->count();

        $pendingCorrections = DailyLog::query()
            ->where('student_id', $student->id)
            ->where('status', DailyLogStatus::Rejected)
            ->count();

        $recentLogs = DailyLog::query()
            ->where('student_id', $student->id)
            ->orderByDesc('date')
            ->limit(6)
            ->get();

        $canCheckIn = $internship
            && $internship->period?->isActive()
            && $internship->isOperational()
            && $stats->isWorkingDay(now())
            && ! $todayAttendance;

        $isWorkingDay = $stats->isWorkingDay(now());
        $lastAttendance = $internship ? $stats->lastAttendanceDate($internship) : null;

        $canCheckOut = $todayAttendance
            && ! $todayAttendance->hasCheckedOut()
            && $todayAttendance->status !== AttendanceStatus::Rejected;

        $canSubmitTodayLog = $todayAttendance
            && $todayAttendance->qualifiesForLog()
            && ! $todayLog;

        $missedLogs = $internship ? $stats->missedLogDates($internship, now()->subDays(30))->count() : 0;

        $chart = null;

        if ($internship) {
            $counts = $internship->attendances()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $chart = [
                'type' => 'doughnut',
                'data' => [
                    'labels' => ['Present', 'Late', 'Outside location', 'Incomplete', 'Rejected'],
                    'datasets' => [[
                        'data' => [
                            (int) ($counts[AttendanceStatus::Present->value] ?? 0),
                            (int) ($counts[AttendanceStatus::Late->value] ?? 0),
                            (int) ($counts[AttendanceStatus::OutsideLocation->value] ?? 0),
                            (int) ($counts[AttendanceStatus::Incomplete->value] ?? 0),
                            (int) ($counts[AttendanceStatus::Rejected->value] ?? 0),
                        ],
                        'backgroundColor' => ['#16a34a', '#d97706', '#dc2626', '#0891b2', '#64748b'],
                        'borderWidth' => 2,
                        'borderColor' => '#fff',
                    ]],
                ],
                'options' => [
                    'cutout' => '68%',
                    'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 10, 'padding' => 12]]],
                ],
            ];
        }

        return view('student.dashboard', compact(
            'student',
            'internship',
            'todayAttendance',
            'todayLog',
            'expectedDays',
            'attendedDays',
            'attendancePercentage',
            'approvedLogs',
            'pendingCorrections',
            'recentLogs',
            'canCheckIn',
            'canCheckOut',
            'canSubmitTodayLog',
            'missedLogs',
            'chart',
            'isWorkingDay',
            'lastAttendance',
        ));
    }
}
