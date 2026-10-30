<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\DailyLogStatus;
use App\Http\Controllers\Controller;
use App\Models\DailyLog;
use App\Services\LogbookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ApprovalController extends Controller
{
    public function __construct(private readonly LogbookService $logbook) {}

    public function index(Request $request): View
    {
        $supervisor = $request->user()->companySupervisor;

        abort_unless($supervisor, 403, 'Your supervisor profile is not linked to this account.');

        $filter = (string) $request->query('status', 'pending');
        $allowed = array_map(fn (DailyLogStatus $case) => $case->value, DailyLogStatus::cases());

        $query = DailyLog::query()
            ->whereHas('internship', fn ($internshipQuery) => $internshipQuery->where('company_supervisor_id', $supervisor->id))
            ->where('status', '!=', DailyLogStatus::Draft)
            ->with(['student.user', 'internship.company'])
            ->withCount('evidenceFiles');

        if ($filter === 'pending') {
            $query->pendingReview();
        } elseif (in_array($filter, $allowed, true)) {
            $query->where('status', $filter);
        }

        $logs = $query->orderByDesc('submitted_at')->orderByDesc('date')->paginate(15)->withQueryString();

        $counts = DailyLog::query()
            ->whereHas('internship', fn ($internshipQuery) => $internshipQuery->where('company_supervisor_id', $supervisor->id))
            ->where('status', '!=', DailyLogStatus::Draft)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pendingCount = (int) ($counts[DailyLogStatus::Submitted->value] ?? 0) + (int) ($counts[DailyLogStatus::UnderReview->value] ?? 0);

        return view('supervisor.approvals.index', compact('logs', 'counts', 'filter', 'pendingCount'));
    }

    public function show(DailyLog $log): View
    {
        Gate::authorize('review', $log);

        abort_if($log->status === DailyLogStatus::Draft, 404);

        $log->load([
            'student.user',
            'student.department',
            'internship.company',
            'internship.period',
            'attendance',
            'reviewer',
            'evidenceFiles.uploader',
            'approvals.reviewer',
        ]);

        return view('supervisor.approvals.show', compact('log'));
    }

    public function startReview(DailyLog $log): RedirectResponse
    {
        Gate::authorize('review', $log);

        try {
            $this->logbook->startReview($log, request()->user());
        } catch (Throwable $exception) {
            return $this->handleError($exception);
        }

        return redirect()->route('supervisor.approvals.show', $log)->with('success', 'Review started. You can now approve or return this log.');
    }

    public function approve(Request $request, DailyLog $log): RedirectResponse
    {
        Gate::authorize('review', $log);

        $data = $request->validate([
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->logbook->approve($log, $request->user(), $data['comment'] ?? null);
        } catch (Throwable $exception) {
            return $this->handleError($exception);
        }

        return redirect()->route('supervisor.approvals.show', $log)->with('success', 'Daily log approved and the student has been notified.');
    }

    public function reject(Request $request, DailyLog $log): RedirectResponse
    {
        Gate::authorize('review', $log);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'decision' => ['required', 'in:reject,correction'],
        ], [
            'reason.min' => 'Provide a clear reason of at least 10 characters.',
        ]);

        try {
            $this->logbook->reject($log, $request->user(), $data['reason'], $data['decision'] === 'correction');
        } catch (Throwable $exception) {
            return $this->handleError($exception);
        }

        return redirect()->route('supervisor.approvals.show', $log)->with('success', $data['decision'] === 'correction' ? 'Correction requested. The student can now revise and resubmit.' : 'Daily log rejected and the student has been notified.');
    }

    public function comment(Request $request, DailyLog $log): RedirectResponse
    {
        Gate::authorize('review', $log);

        $data = $request->validate([
            'comment' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        try {
            $this->logbook->comment($log, $request->user(), $data['comment']);
        } catch (Throwable $exception) {
            return $this->handleError($exception);
        }

        return redirect()->route('supervisor.approvals.show', $log)->with('success', 'Comment added to the review history.');
    }

    private function handleError(Throwable $exception): RedirectResponse
    {
        if ($exception instanceof ValidationException) {
            throw $exception;
        }

        report($exception);

        return redirect()->back()->withInput()->with('error', 'We could not complete this review action. Please try again.');
    }
}
