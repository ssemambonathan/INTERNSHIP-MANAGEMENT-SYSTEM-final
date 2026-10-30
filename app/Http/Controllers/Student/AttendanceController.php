<?php

namespace App\Http\Controllers\Student;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\AttendanceService;
use App\Services\AttendanceStatsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AttendanceController extends Controller
{
    public function index(Request $request, AttendanceStatsService $stats): View
    {
        $student = $request->user()->student;

        abort_unless($student, 403, 'Your student profile is not linked to this account.');

        $internship = $student->activeInternship()
            ->with(['company', 'period'])
            ->first();

        $month = $request->query('month', now()->format('Y-m'));
        $monthDate = now()->startOfMonth();

        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month)) {
            try {
                $monthDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            } catch (Throwable) {
                $monthDate = now()->startOfMonth();
            }
        }

        $records = Attendance::query()
            ->where('student_id', $student->id)
            ->whereYear('date', $monthDate->year)
            ->whereMonth('date', $monthDate->month)
            ->orderByDesc('date')
            ->paginate(15)
            ->withQueryString();

        $today = Attendance::query()
            ->where('student_id', $student->id)
            ->whereDate('date', today())
            ->first();

        $expectedDays = $internship ? $stats->expectedWorkingDays($internship) : 0;
        $attendedDays = $internship ? $stats->attendedDays($internship) : 0;
        $percentage = $internship ? $stats->attendancePercentage($internship) : 0.0;
        $lastAttendance = $internship ? $stats->lastAttendanceDate($internship) : null;
        $isWorkingDay = $stats->isWorkingDay(now());

        $canCheckIn = $internship
            && $internship->period?->isActive()
            && $internship->isOperational()
            && $isWorkingDay
            && ! $today;

        $canCheckOut = $today
            && ! $today->hasCheckedOut()
            && $today->status !== AttendanceStatus::Rejected;

        $trend = null;

        if ($internship) {
            $rawTrend = $stats->attendanceTrend(collect([$internship]), 14);
            $trend = [
                'type' => 'bar',
                'data' => [
                    'labels' => $rawTrend['labels'],
                    'datasets' => [
                        [
                            'label' => 'On-site present',
                            'data' => $rawTrend['present'],
                            'backgroundColor' => '#16a34a',
                            'borderRadius' => 4,
                        ],
                        [
                            'label' => 'Late',
                            'data' => $rawTrend['late'],
                            'backgroundColor' => '#d97706',
                            'borderRadius' => 4,
                        ],
                        [
                            'label' => 'Flagged',
                            'data' => $rawTrend['flagged'],
                            'backgroundColor' => '#dc2626',
                            'borderRadius' => 4,
                        ],
                    ],
                ],
                'options' => [
                    'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 10, 'padding' => 14]]],
                    'scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true]],
                ],
            ];
        }

        return view('student.attendance.index', compact(
            'student',
            'internship',
            'records',
            'today',
            'monthDate',
            'expectedDays',
            'attendedDays',
            'percentage',
            'lastAttendance',
            'trend',
            'isWorkingDay',
            'canCheckIn',
            'canCheckOut',
        ));
    }

    public function checkIn(Request $request, AttendanceService $attendance): RedirectResponse
    {
        Gate::authorize('checkIn', Attendance::class);

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        $student = $request->user()->student;

        abort_unless($student, 403, 'Your student profile is not linked to this account.');

        try {
            $record = $attendance->checkIn(
                $student,
                (float) $data['latitude'],
                (float) $data['longitude'],
                isset($data['accuracy']) ? (float) $data['accuracy'] : null,
            );
        } catch (Throwable $exception) {
            return $this->handleAttendanceError($exception);
        }

        $message = match ($record->status) {
            AttendanceStatus::OutsideLocation => 'Check-in recorded at '.$record->check_in_time->format('H:i').', but you are outside the company geofence. Your supervisor will review the record.',
            AttendanceStatus::Late => 'Checked in at '.$record->check_in_time->format('H:i').'. You are '.$record->late_minutes.' minutes late.',
            default => 'Checked in at '.$record->check_in_time->format('H:i').'. Have a productive day.',
        };

        return redirect()->route('student.attendance.index')->with(
            $record->status === AttendanceStatus::OutsideLocation ? 'error' : 'success',
            $message
        );
    }

    public function checkOut(Request $request, AttendanceService $attendance): RedirectResponse
    {
        Gate::authorize('checkOut', Attendance::class);

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        $student = $request->user()->student;

        abort_unless($student, 403, 'Your student profile is not linked to this account.');

        try {
            $record = $attendance->checkOut(
                $student,
                (float) $data['latitude'],
                (float) $data['longitude'],
                isset($data['accuracy']) ? (float) $data['accuracy'] : null,
            );
        } catch (Throwable $exception) {
            return $this->handleAttendanceError($exception);
        }

        $message = 'Checked out at '.$record->check_out_time->format('H:i').'. You worked '.intdiv($record->worked_minutes ?? 0, 60).'h '.str_pad((string) (($record->worked_minutes ?? 0) % 60), 2, '0', STR_PAD_LEFT).'.';

        if ($record->status === AttendanceStatus::Incomplete) {
            return redirect()->route('student.attendance.index')
                ->with('error', $message.' This is below the minimum working hours and has been flagged as incomplete.');
        }

        return redirect()->route('student.attendance.index')->with('success', $message);
    }

    private function handleAttendanceError(Throwable $exception): RedirectResponse
    {
        if ($exception instanceof ValidationException) {
            throw $exception;
        }

        report($exception);

        return redirect()->back()->with('error', 'We could not complete your attendance request. Please try again or contact the internship office.');
    }
}
