<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendance) {}

    public function index(Request $request): View
    {
        $supervisor = $request->user()->companySupervisor;

        abort_unless($supervisor, 403, 'Your supervisor profile is not linked to this account.');

        $month = (string) $request->query('month', now()->format('Y-m'));
        $monthDate = now()->startOfMonth();

        if (preg_match('/^\d{4}-\d{2}$/', $month)) {
            try {
                $monthDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            } catch (Throwable) {
                $monthDate = now()->startOfMonth();
            }
        }

        $status = (string) $request->query('status', 'all');
        $allowedStatuses = array_map(fn (AttendanceStatus $case) => $case->value, AttendanceStatus::cases());
        $search = trim((string) $request->query('q'));

        $query = Attendance::query()
            ->whereHas('internship', fn ($internshipQuery) => $internshipQuery->where('company_supervisor_id', $supervisor->id))
            ->with(['student.user', 'internship.company', 'reviewer'])
            ->whereYear('date', $monthDate->year)
            ->whereMonth('date', $monthDate->month);

        if ($status === 'flagged') {
            $query->flagged();
        } elseif (in_array($status, $allowedStatuses, true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->whereHas('student.user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'));
        }

        $records = $query->orderByDesc('date')->orderByDesc('check_in_time')->paginate(15)->withQueryString();

        $counts = Attendance::query()
            ->whereHas('internship', fn ($internshipQuery) => $internshipQuery->where('company_supervisor_id', $supervisor->id))
            ->whereYear('date', $monthDate->year)
            ->whereMonth('date', $monthDate->month)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $flaggedTotal = Attendance::query()
            ->whereHas('internship', fn ($internshipQuery) => $internshipQuery->where('company_supervisor_id', $supervisor->id))
            ->flagged()
            ->count();

        return view('supervisor.attendance.index', compact('records', 'counts', 'monthDate', 'status', 'search', 'flaggedTotal'));
    }

    public function verify(Request $request, Attendance $attendance): RedirectResponse
    {
        Gate::authorize('review', $attendance);

        try {
            $this->attendance->review($attendance, $request->user(), 'verify');
        } catch (Throwable $exception) {
            return $this->handleError($exception);
        }

        return redirect()->back()->with('success', 'Attendance verified for '.$attendance->student->user->name.'.');
    }

    public function reject(Request $request, Attendance $attendance): RedirectResponse
    {
        Gate::authorize('review', $attendance);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        try {
            $this->attendance->review($attendance, $request->user(), 'reject', $data['reason']);
        } catch (Throwable $exception) {
            return $this->handleError($exception);
        }

        return redirect()->back()->with('success', 'Attendance rejected for '.$attendance->student->user->name.'.');
    }

    private function handleError(Throwable $exception): RedirectResponse
    {
        if ($exception instanceof ValidationException) {
            throw $exception;
        }

        report($exception);

        return redirect()->back()->with('error', 'We could not update this attendance record. Please try again.');
    }
}
