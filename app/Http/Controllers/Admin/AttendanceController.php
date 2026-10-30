<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\VerificationMethod;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Company;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): View
    {
        $from = $this->parseDate($request->query('from'), now()->startOfMonth());
        $to = $this->parseDate($request->query('to'), now());

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }

        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status', '');
        $companyId = $request->integer('company_id') ?: null;

        $base = Attendance::query()
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('student', function ($studentQuery) use ($search) {
                    $studentQuery->where('registration_number', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when(in_array($status, array_column(AttendanceStatus::cases(), 'value'), true), fn ($query) => $query->where('status', $status))
            ->when($companyId, fn ($query) => $query->whereHas('internship', fn ($internshipQuery) => $internshipQuery->where('company_id', $companyId)));

        $counts = (clone $base)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $attendance = $base
            ->with(['student.user', 'internship.company', 'reviewer'])
            ->orderByDesc('date')
            ->orderByDesc('check_in_time')
            ->paginate(20)
            ->withQueryString();

        $companies = Company::query()->orderBy('name')->get();

        return view('admin.attendance.index', compact('attendance', 'counts', 'companies', 'from', 'to', 'search', 'status', 'companyId'));
    }

    public function correct(Request $request, Attendance $attendance): RedirectResponse
    {
        $data = $request->validate([
            'check_in_time' => ['required', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'status' => ['required', Rule::enum(AttendanceStatus::class)],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'check_in_time.date_format' => 'Enter the check-in time as HH:MM.',
            'check_out_time.date_format' => 'Enter the check-out time as HH:MM.',
            'reason.min' => 'Provide a clear reason (at least 10 characters) for the correction.',
        ]);

        $date = $attendance->date->toDateString();
        $checkIn = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$data['check_in_time']);
        $checkOut = filled($data['check_out_time'] ?? null)
            ? Carbon::createFromFormat('Y-m-d H:i', $date.' '.$data['check_out_time'])
            : null;

        if ($checkOut && $checkOut->lte($checkIn)) {
            throw ValidationException::withMessages([
                'check_out_time' => 'The check-out time must be after the check-in time.',
            ]);
        }

        $status = AttendanceStatus::from($data['status']);

        if ($status->countsAsAttended() && ! $checkOut) {
            throw ValidationException::withMessages([
                'check_out_time' => 'A check-out time is required for a present or late record.',
            ]);
        }

        $lateAfter = Carbon::createFromFormat('Y-m-d H:i', $date.' '.settings('late_after', '08:30'));
        $lateMinutes = $checkIn->gt($lateAfter) ? (int) $lateAfter->diffInMinutes($checkIn) : 0;
        $workedMinutes = $checkOut ? max(0, (int) $checkIn->diffInMinutes($checkOut)) : null;

        $before = sprintf(
            'in %s, out %s, status %s',
            $attendance->check_in_time?->format('H:i') ?? '—',
            $attendance->check_out_time?->format('H:i') ?? '—',
            $attendance->status->label(),
        );

        $note = sprintf(
            'Corrected by %s on %s. Reason: %s',
            $request->user()->name,
            now()->format('d M Y H:i'),
            $data['reason'],
        );

        $attendance->update([
            'check_in_time' => $checkIn,
            'check_out_time' => $checkOut,
            'worked_minutes' => $workedMinutes,
            'late_minutes' => $lateMinutes,
            'status' => $status,
            'verification_method' => VerificationMethod::Manual,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'notes' => trim(($attendance->notes ? $attendance->notes.' ' : '').$note),
        ]);

        $after = sprintf(
            'in %s, out %s, status %s',
            $checkIn->format('H:i'),
            $checkOut?->format('H:i') ?? '—',
            $status->label(),
        );

        $this->audit->record(
            'attendance.corrected',
            'attendance',
            sprintf(
                '%s corrected %s attendance for %s on %s (%s → %s). Reason: %s',
                $request->user()->name,
                $attendance->student->user->name,
                $attendance->internship->company?->name,
                $attendance->date->format('d M Y'),
                $before,
                $after,
                $data['reason'],
            ),
            $attendance,
            $request->user(),
        );

        $this->notifications->notifyAttendanceCorrected($attendance->refresh(), $request->user(), $data['reason']);

        return redirect()->back()->with('success', 'Attendance record corrected and the student has been notified.');
    }

    private function parseDate(mixed $value, Carbon $default): Carbon
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
            } catch (\Throwable) {
                // Fall back to the default below.
            }
        }

        return $default->copy()->startOfDay();
    }
}
