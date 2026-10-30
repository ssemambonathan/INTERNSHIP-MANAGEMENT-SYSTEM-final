<?php

namespace App\Services;

use App\Models\Evaluation;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class EvaluationService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly NotificationService $notifications,
    ) {}

    public function create(Internship $internship, User $actor, array $data): Evaluation
    {
        $this->assertSupervisor($internship, $actor);

        if (! $internship->isActive()) {
            throw ValidationException::withMessages([
                'internship_id' => 'Evaluations can only be recorded for active internships.',
            ]);
        }

        $weekStart = Carbon::parse($data['week_start'])->startOfWeek(Carbon::MONDAY)->startOfDay();

        if ($weekStart->lt($internship->start_date->copy()->startOfWeek(Carbon::MONDAY))) {
            throw ValidationException::withMessages([
                'week_start' => 'The evaluation week cannot be before the internship started.',
            ]);
        }

        if ($weekStart->gt($internship->end_date)) {
            throw ValidationException::withMessages([
                'week_start' => 'The evaluation week must fall within the internship period.',
            ]);
        }

        if ($internship->evaluations()->whereDate('week_start', $weekStart->toDateString())->exists()) {
            throw ValidationException::withMessages([
                'week_start' => 'An evaluation already exists for that week.',
            ]);
        }

        $this->assertScores($data);

        $evaluation = Evaluation::create([
            'internship_id' => $internship->id,
            'company_supervisor_id' => $actor->companySupervisor->id,
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekStart->copy()->addDays(6)->toDateString(),
            'technical_skills' => (int) $data['technical_skills'],
            'communication' => (int) $data['communication'],
            'professionalism' => (int) $data['professionalism'],
            'teamwork' => (int) $data['teamwork'],
            'punctuality' => (int) $data['punctuality'],
            'initiative' => (int) $data['initiative'],
            'strengths' => trim((string) ($data['strengths'] ?? '')),
            'areas_for_improvement' => trim((string) ($data['areas_for_improvement'] ?? '')),
            'remarks' => $data['remarks'] ?? null,
            'recommendation' => $data['recommendation'] ?? null,
        ]);

        $this->audit->record(
            'evaluation.created',
            'evaluations',
            sprintf('%s submitted a weekly evaluation for %s (week of %s).', $actor->name, $internship->student->user->name, $weekStart->format('d M Y')),
            $evaluation,
            $actor,
        );

        $this->notifications->notifyEvaluationSubmitted($evaluation);

        return $evaluation;
    }

    public function update(Evaluation $evaluation, User $actor, array $data): Evaluation
    {
        $this->assertSupervisor($evaluation->internship, $actor);

        $this->assertScores($data);

        $evaluation->update([
            'technical_skills' => (int) $data['technical_skills'],
            'communication' => (int) $data['communication'],
            'professionalism' => (int) $data['professionalism'],
            'teamwork' => (int) $data['teamwork'],
            'punctuality' => (int) $data['punctuality'],
            'initiative' => (int) $data['initiative'],
            'strengths' => trim((string) ($data['strengths'] ?? '')),
            'areas_for_improvement' => trim((string) ($data['areas_for_improvement'] ?? '')),
            'remarks' => $data['remarks'] ?? null,
            'recommendation' => $data['recommendation'] ?? null,
        ]);

        $this->audit->record(
            'evaluation.updated',
            'evaluations',
            sprintf('%s updated the weekly evaluation for %s (week of %s).', $actor->name, $evaluation->internship->student->user->name, $evaluation->week_start->format('d M Y')),
            $evaluation,
            $actor,
        );

        return $evaluation->refresh();
    }

    private function assertSupervisor(Internship $internship, User $actor): void
    {
        $supervisor = $actor->companySupervisor;

        if (! $actor->isSupervisor() || ! $supervisor || $internship->company_supervisor_id !== $supervisor->id) {
            throw ValidationException::withMessages([
                'internship_id' => 'You can only evaluate students assigned to you.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertScores(array $data): void
    {
        $scale = (int) settings('evaluation_scale', 5);
        $errors = [];

        foreach (array_keys(Evaluation::$criteria) as $criterion) {
            $score = $data[$criterion] ?? null;

            if (! is_numeric($score) || (int) $score < 1 || (int) $score > $scale) {
                $errors[$criterion] = 'Each criterion must be scored between 1 and '.$scale.'.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
