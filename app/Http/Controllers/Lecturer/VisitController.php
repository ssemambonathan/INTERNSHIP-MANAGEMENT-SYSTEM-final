<?php

namespace App\Http\Controllers\Lecturer;

use App\Enums\VisitStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lecturer\VisitRequest;
use App\Models\EvidenceFile;
use App\Models\Internship;
use App\Models\LecturerVisit;
use App\Services\VisitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class VisitController extends Controller
{
    public function __construct(private readonly VisitService $visits) {}

    public function index(Request $request): View
    {
        $lecturer = $request->user()->lecturer;

        abort_unless($lecturer, 403, 'Your lecturer profile is not linked to this account.');

        $status = (string) $request->query('status', 'all');
        $search = trim((string) $request->query('q'));

        $query = LecturerVisit::query()
            ->where('lecturer_id', $lecturer->id)
            ->with(['internship.student.user', 'internship.company'])
            ->withCount('evidenceFiles');

        if (in_array($status, ['scheduled', 'completed', 'cancelled'], true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->whereHas('internship.student.user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'));
        }

        $visits = $query->orderByDesc('visit_date')->paginate(12)->withQueryString();

        $counts = LecturerVisit::query()
            ->where('lecturer_id', $lecturer->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $upcomingCount = LecturerVisit::query()
            ->where('lecturer_id', $lecturer->id)
            ->where('status', VisitStatus::Scheduled)
            ->whereDate('visit_date', '>=', today())
            ->count();

        $activeInternships = $this->activeInternships($request);

        return view('lecturer.visits.index', compact('visits', 'counts', 'status', 'search', 'upcomingCount', 'activeInternships'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', LecturerVisit::class);

        $internships = $this->activeInternships($request);
        $internshipId = (int) $request->query('internship_id');

        return view('lecturer.visits.create', compact('internships', 'internshipId'));
    }

    public function store(VisitRequest $request): RedirectResponse
    {
        $internship = Internship::query()->with(['student.user', 'company'])->findOrFail($request->integer('internship_id'));

        Gate::authorize('recordVisit', $internship);

        try {
            $visit = $this->visits->create($request->user(), $request->validated());
        } catch (Throwable $exception) {
            return $this->handleError($exception);
        }

        return redirect()->route('lecturer.visits.show', $visit)->with('success', 'Visit scheduled and the student has been notified.');
    }

    public function show(LecturerVisit $visit): View
    {
        Gate::authorize('view', $visit);

        $visit->load([
            'internship.student.user',
            'internship.student.department',
            'internship.company',
            'internship.supervisor.user',
            'lecturer.user',
            'evidenceFiles.uploader',
        ]);

        return view('lecturer.visits.show', compact('visit'));
    }

    public function edit(LecturerVisit $visit): View
    {
        Gate::authorize('update', $visit);

        $visit->load(['internship.student.user', 'internship.company']);

        return view('lecturer.visits.edit', compact('visit'));
    }

    public function update(VisitRequest $request, LecturerVisit $visit): RedirectResponse
    {
        Gate::authorize('update', $visit);

        try {
            $this->visits->update($visit, $request->user(), $request->validated());
        } catch (Throwable $exception) {
            return $this->handleError($exception);
        }

        return redirect()->route('lecturer.visits.show', $visit)->with('success', 'Visit details updated.');
    }

    public function destroy(LecturerVisit $visit): RedirectResponse
    {
        Gate::authorize('delete', $visit);

        try {
            $this->visits->cancel($visit, request()->user());
        } catch (Throwable $exception) {
            return $this->handleError($exception);
        }

        return redirect()->route('lecturer.visits.index')->with('success', 'Visit cancelled. The record is kept for audit purposes.');
    }

    public function complete(Request $request, LecturerVisit $visit): RedirectResponse
    {
        Gate::authorize('update', $visit);

        $data = $request->validate([
            'observations' => ['required', 'string', 'min:10', 'max:5000'],
            'student_performance' => ['nullable', 'string', 'max:3000'],
            'company_feedback' => ['nullable', 'string', 'max:3000'],
            'recommendations' => ['nullable', 'string', 'max:3000'],
            'action_items' => ['nullable', 'string', 'max:3000'],
        ]);

        try {
            $this->visits->complete($visit, $request->user(), $data);
        } catch (Throwable $exception) {
            return $this->handleError($exception);
        }

        return redirect()->route('lecturer.visits.show', $visit)->with('success', 'Visit completed and outcomes recorded.');
    }

    public function uploadEvidence(Request $request, LecturerVisit $visit): RedirectResponse
    {
        Gate::authorize('uploadEvidence', $visit);

        $maxKb = max(1024, (int) settings('max_upload_size_kb', 10240));

        $request->validate([
            'evidence' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,docx', 'max:'.$maxKb],
        ], [
            'evidence.mimes' => 'Evidence must be a JPG, JPEG, PNG, PDF, or DOCX file.',
            'evidence.max' => 'Evidence may not be larger than '.round($maxKb / 1024).' MB.',
        ]);

        try {
            $this->visits->attachEvidence($visit, $request->file('evidence'), $request->user());
        } catch (Throwable $exception) {
            return $this->handleError($exception);
        }

        return redirect()->route('lecturer.visits.show', $visit)->with('success', 'Visit evidence uploaded.');
    }

    public function deleteEvidence(EvidenceFile $evidence): RedirectResponse
    {
        $visit = $evidence->attachable;

        abort_unless($visit instanceof LecturerVisit, 404);

        Gate::authorize('delete', $evidence);

        try {
            $this->visits->deleteEvidence($evidence, request()->user());
        } catch (Throwable $exception) {
            return $this->handleError($exception);
        }

        return redirect()->route('lecturer.visits.show', $visit)->with('success', 'Evidence removed.');
    }

    private function activeInternships(Request $request)
    {
        $lecturer = $request->user()->lecturer;

        abort_unless($lecturer, 403, 'Your lecturer profile is not linked to this account.');

        return Internship::query()
            ->where('lecturer_id', $lecturer->id)
            ->where('status', 'active')
            ->with(['student.user', 'company'])
            ->orderBy('start_date')
            ->get();
    }

    private function handleError(Throwable $exception): RedirectResponse
    {
        if ($exception instanceof ValidationException) {
            throw $exception;
        }

        report($exception);

        return redirect()->back()->withInput()->with('error', 'We could not complete this visit action. Please try again.');
    }
}
