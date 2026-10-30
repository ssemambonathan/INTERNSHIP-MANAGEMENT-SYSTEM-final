<?php

namespace App\Http\Controllers\Student;

use App\Enums\DailyLogStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\LogRequest;
use App\Models\Attendance;
use App\Models\DailyLog;
use App\Models\EvidenceFile;
use App\Services\AttendanceStatsService;
use App\Services\LogbookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class LogController extends Controller
{
    public function __construct(
        private readonly LogbookService $logbook,
        private readonly AttendanceStatsService $stats,
    ) {}

    public function index(Request $request): View
    {
        $student = $request->user()->student;

        abort_unless($student, 403, 'Your student profile is not linked to this account.');

        $status = $request->query('status');
        $allowedStatuses = array_map(fn (DailyLogStatus $case) => $case->value, DailyLogStatus::cases());

        $logs = DailyLog::query()
            ->where('student_id', $student->id)
            ->when(in_array($status, $allowedStatuses, true), fn ($query) => $query->where('status', $status))
            ->withCount('evidenceFiles')
            ->orderByDesc('date')
            ->paginate(12)
            ->withQueryString();

        $counts = DailyLog::query()
            ->where('student_id', $student->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $internship = $student->activeInternship()->with('period')->first();
        $missedDates = $internship ? $this->stats->missedLogDates($internship, now()->subDays(45)) : collect();

        $todayLog = DailyLog::query()
            ->where('student_id', $student->id)
            ->whereDate('date', today())
            ->first();

        $todayAttendance = Attendance::query()
            ->where('student_id', $student->id)
            ->whereDate('date', today())
            ->first();

        $canCreateToday = $internship
            && $internship->period?->isActive()
            && ! $todayLog
            && $todayAttendance?->qualifiesForLog();

        $recentReopenings = $internship
            ? $internship->reopenings()
                ->whereDate('date', '>=', now()->subDays(45)->toDateString())
                ->orderByDesc('date')
                ->limit(5)
                ->get()
            : collect();

        return view('student.logs.index', compact(
            'logs',
            'counts',
            'internship',
            'missedDates',
            'todayLog',
            'todayAttendance',
            'canCreateToday',
            'recentReopenings',
            'status',
        ));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', DailyLog::class);

        $student = $request->user()->student;
        $internship = $student->activeInternship()->with(['period', 'company'])->first();

        $openDates = collect();
        $blockReason = null;

        if (! $internship) {
            $blockReason = 'You do not have an active internship placement, so a daily log cannot be created.';
        } elseif (! $internship->period?->isActive()) {
            $blockReason = 'Your internship period is closed. Log submissions are no longer accepted.';
        } else {
            $existingToday = DailyLog::query()
                ->where('student_id', $student->id)
                ->whereDate('date', today())
                ->exists();

            $todayAttendance = Attendance::query()
                ->where('student_id', $student->id)
                ->whereDate('date', today())
                ->first();

            if ($existingToday) {
                $blockReason = 'You already have a log for today.';
            } elseif (! $todayAttendance) {
                $blockReason = 'You must check in today before writing a daily log.';
            } elseif (! $todayAttendance->qualifiesForLog()) {
                $blockReason = 'Your attendance today is '.$todayAttendance->status->label().'. A supervisor must verify it before you can submit a log.';
            } else {
                $openDates->push([
                    'value' => today()->toDateString(),
                    'label' => 'Today, '.today()->format('d M Y'),
                    'type' => 'today',
                ]);
            }

            $reopenings = $internship->reopenings()
                ->whereDate('date', '>=', $internship->start_date->toDateString())
                ->orderByDesc('date')
                ->get();

            foreach ($reopenings as $reopening) {
                $date = $reopening->date->copy();

                $hasLog = DailyLog::query()
                    ->where('student_id', $student->id)
                    ->whereDate('date', $date->toDateString())
                    ->exists();

                $attendance = Attendance::query()
                    ->where('student_id', $student->id)
                    ->whereDate('date', $date->toDateString())
                    ->first();

                if (! $hasLog && $attendance?->qualifiesForLog()) {
                    $openDates->push([
                        'value' => $date->toDateString(),
                        'label' => $date->format('d M Y').' (reopened)',
                        'type' => 'reopened',
                    ]);
                }
            }
        }

        $selectedDate = $request->query('date');
        $dateValue = $openDates->firstWhere('value', $selectedDate)['value'] ?? $openDates->first()['value'] ?? today()->toDateString();

        return view('student.logs.create', compact('internship', 'openDates', 'blockReason', 'dateValue'));
    }

    public function store(LogRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $submit = $data['action'] === 'submit';
        unset($data['action']);

        $student = $request->user()->student;

        abort_unless($student, 403, 'Your student profile is not linked to this account.');

        try {
            $log = $this->logbook->create($student, $data, $submit);
        } catch (Throwable $exception) {
            return $this->handleLogError($exception);
        }

        return redirect()
            ->route('student.logs.show', $log)
            ->with('success', $submit ? 'Daily log submitted for supervisor review.' : 'Draft saved. Remember to submit it for review.');
    }

    public function show(DailyLog $log): View
    {
        Gate::authorize('view', $log);

        $log->load([
            'internship.company',
            'internship.supervisor.user',
            'attendance',
            'reviewer',
            'evidenceFiles.uploader',
            'approvals.reviewer',
        ]);

        return view('student.logs.show', compact('log'));
    }

    public function edit(DailyLog $log): View|RedirectResponse
    {
        Gate::authorize('update', $log);

        if (! $log->isEditableByStudent()) {
            return redirect()->route('student.logs.show', $log)
                ->with('error', 'This log is awaiting review or has been approved and can no longer be edited.');
        }

        $log->load(['internship.company', 'attendance', 'evidenceFiles']);

        return view('student.logs.edit', compact('log'));
    }

    public function update(LogRequest $request, DailyLog $log): RedirectResponse
    {
        Gate::authorize('update', $log);

        $data = $request->validated();
        $submit = $data['action'] === 'submit';
        unset($data['action']);
        $data['date'] = $log->date->toDateString();

        try {
            $this->logbook->update($log, $data, $submit);
        } catch (Throwable $exception) {
            return $this->handleLogError($exception);
        }

        return redirect()
            ->route('student.logs.show', $log)
            ->with('success', $submit ? 'Daily log resubmitted for review.' : 'Draft updated.');
    }

    public function submit(DailyLog $log): RedirectResponse
    {
        Gate::authorize('submit', $log);

        try {
            $this->logbook->submit($log);
        } catch (Throwable $exception) {
            return $this->handleLogError($exception);
        }

        return redirect()->route('student.logs.show', $log)->with('success', 'Daily log submitted for supervisor review.');
    }

    public function uploadEvidence(Request $request, DailyLog $log): RedirectResponse
    {
        Gate::authorize('uploadEvidence', $log);

        $maxKb = (int) settings('max_upload_size_kb', 10240);

        $request->validate([
            'evidence' => ['required', 'file', 'max:'.$maxKb, 'mimes:jpg,jpeg,png,pdf,docx'],
        ], [
            'evidence.max' => 'The evidence file may not be larger than '.round($maxKb / 1024).' MB.',
            'evidence.mimes' => 'Evidence must be a JPG, JPEG, PNG, PDF, or DOCX file.',
        ]);

        try {
            $this->logbook->attachEvidence($log, $request->file('evidence'), $request->user());
        } catch (Throwable $exception) {
            return $this->handleLogError($exception);
        }

        return redirect()->route('student.logs.show', $log)->with('success', 'Evidence uploaded.');
    }

    public function deleteEvidence(EvidenceFile $evidence): RedirectResponse
    {
        Gate::authorize('delete', $evidence);

        $log = $evidence->attachable;

        try {
            $this->logbook->deleteEvidence($evidence);
        } catch (Throwable $exception) {
            return $this->handleLogError($exception);
        }

        if ($log instanceof DailyLog) {
            return redirect()->route('student.logs.show', $log)->with('success', 'Evidence removed.');
        }

        return redirect()->back()->with('success', 'Evidence removed.');
    }

    private function handleLogError(Throwable $exception): RedirectResponse
    {
        if ($exception instanceof ValidationException) {
            throw $exception;
        }

        report($exception);

        return redirect()->back()->withInput()->with('error', 'We could not save your logbook entry. Please try again.');
    }
}
