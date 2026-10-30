<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supervisor\EvaluationRequest;
use App\Models\Evaluation;
use App\Models\Internship;
use App\Services\EvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class EvaluationController extends Controller
{
    public function __construct(private readonly EvaluationService $evaluations) {}

    public function index(Request $request): View
    {
        $supervisor = $request->user()->companySupervisor;

        abort_unless($supervisor, 403, 'Your supervisor profile is not linked to this account.');

        $evaluations = Evaluation::query()
            ->where('company_supervisor_id', $supervisor->id)
            ->with(['internship.student.user', 'internship.company'])
            ->orderByDesc('week_start')
            ->paginate(12);

        $activeInternships = $this->activeInternships($request);

        return view('supervisor.evaluations.index', compact('evaluations', 'activeInternships'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Evaluation::class);

        $internships = $this->activeInternships($request);
        $internshipId = (int) $request->query('internship_id');
        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        return view('supervisor.evaluations.create', compact('internships', 'internshipId', 'weekStart'));
    }

    public function store(EvaluationRequest $request): RedirectResponse
    {
        $internship = Internship::query()
            ->with(['student.user', 'company'])
            ->findOrFail($request->integer('internship_id'));

        Gate::authorize('evaluate', $internship);

        try {
            $evaluation = $this->evaluations->create($internship, $request->user(), $request->validated());
        } catch (Throwable $exception) {
            return $this->handleError($exception);
        }

        return redirect()->route('supervisor.evaluations.show', $evaluation)
            ->with('success', 'Weekly evaluation saved and the student has been notified.');
    }

    public function show(Evaluation $evaluation): View
    {
        Gate::authorize('view', $evaluation);

        $evaluation->load(['internship.student.user', 'internship.company', 'supervisor.user']);

        return view('supervisor.evaluations.show', compact('evaluation'));
    }

    public function edit(Evaluation $evaluation): View
    {
        Gate::authorize('update', $evaluation);

        $evaluation->load(['internship.student.user', 'internship.company']);

        return view('supervisor.evaluations.edit', compact('evaluation'));
    }

    public function update(EvaluationRequest $request, Evaluation $evaluation): RedirectResponse
    {
        Gate::authorize('update', $evaluation);

        try {
            $this->evaluations->update($evaluation, $request->user(), $request->validated());
        } catch (Throwable $exception) {
            return $this->handleError($exception);
        }

        return redirect()->route('supervisor.evaluations.show', $evaluation)->with('success', 'Evaluation updated.');
    }

    private function activeInternships(Request $request)
    {
        $supervisor = $request->user()->companySupervisor;

        abort_unless($supervisor, 403, 'Your supervisor profile is not linked to this account.');

        return Internship::query()
            ->where('company_supervisor_id', $supervisor->id)
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

        return redirect()->back()->withInput()->with('error', 'We could not save the evaluation. Please try again.');
    }
}
