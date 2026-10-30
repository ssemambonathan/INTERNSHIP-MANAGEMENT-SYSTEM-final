<?php

namespace Tests\Feature;

use App\Models\Evaluation;
use App\Notifications\EvaluationSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesInternshipContext;
use Tests\TestCase;

class EvaluationTest extends TestCase
{
    use CreatesInternshipContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpInternshipContext();
    }

    public function test_supervisor_can_record_a_weekly_evaluation_for_their_student(): void
    {
        $placement = $this->createPlacement();

        $this->actingAs($placement['supervisorUser'])->post(route('supervisor.evaluations.store'), $this->evaluationPayload([
            'internship_id' => $placement['internship']->id,
            'week_start' => '2026-10-19',
        ]))->assertRedirect();

        $evaluation = Evaluation::firstOrFail();

        $this->assertSame($placement['internship']->id, $evaluation->internship_id);
        $this->assertSame($placement['supervisor']->id, $evaluation->company_supervisor_id);
        $this->assertSame('2026-10-19', $evaluation->week_start->toDateString());
        $this->assertSame('2026-10-25', $evaluation->week_end->toDateString());
        $this->assertSame(4.0, $evaluation->averageScore());
        $this->assertDatabaseHas('audit_logs', ['action' => 'evaluation.created']);
        $this->assertDatabaseHas('notifications', [
            'type' => EvaluationSubmittedNotification::class,
            'notifiable_id' => $placement['studentUser']->id,
        ]);
    }

    public function test_scores_outside_the_configured_scale_are_rejected(): void
    {
        $placement = $this->createPlacement();

        $this->actingAs($placement['supervisorUser'])->post(route('supervisor.evaluations.store'), $this->evaluationPayload([
            'internship_id' => $placement['internship']->id,
            'week_start' => '2026-10-19',
            'technical_skills' => 6,
            'communication' => 0,
        ]))->assertSessionHasErrors(['technical_skills', 'communication']);

        $this->assertSame(0, Evaluation::count());
    }

    public function test_a_second_evaluation_for_the_same_week_is_rejected(): void
    {
        $placement = $this->createPlacement();

        $this->actingAs($placement['supervisorUser'])->post(route('supervisor.evaluations.store'), $this->evaluationPayload([
            'internship_id' => $placement['internship']->id,
            'week_start' => '2026-10-19',
        ]))->assertRedirect();

        $this->actingAs($placement['supervisorUser'])->post(route('supervisor.evaluations.store'), $this->evaluationPayload([
            'internship_id' => $placement['internship']->id,
            'week_start' => '2026-10-21',
        ]))->assertSessionHasErrors('week_start');

        $this->assertSame(1, Evaluation::count());
    }

    public function test_supervisor_cannot_evaluate_another_supervisors_student(): void
    {
        $placement = $this->createPlacement();
        $unrelated = $this->createPlacement();

        $this->actingAs($unrelated['supervisorUser'])->post(route('supervisor.evaluations.store'), $this->evaluationPayload([
            'internship_id' => $placement['internship']->id,
            'week_start' => '2026-10-19',
        ]))->assertForbidden();

        $this->assertSame(0, Evaluation::count());
    }

    public function test_supervisor_can_update_their_evaluation(): void
    {
        $placement = $this->createPlacement();

        $this->actingAs($placement['supervisorUser'])->post(route('supervisor.evaluations.store'), $this->evaluationPayload([
            'internship_id' => $placement['internship']->id,
            'week_start' => '2026-10-19',
        ]));

        $evaluation = Evaluation::firstOrFail();

        $this->actingAs($placement['supervisorUser'])->put(route('supervisor.evaluations.update', $evaluation), $this->evaluationPayload([
            'technical_skills' => 5,
            'remarks' => 'Improved steadily throughout the week.',
        ]))->assertRedirect(route('supervisor.evaluations.show', $evaluation));

        $evaluation->refresh();

        $this->assertSame(5, $evaluation->technical_skills);
        $this->assertSame(4.17, $evaluation->averageScore());
        $this->assertDatabaseHas('audit_logs', ['action' => 'evaluation.updated']);
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluationPayload(array $overrides = []): array
    {
        return array_merge([
            'internship_id' => null,
            'week_start' => '2026-10-19',
            'technical_skills' => 4,
            'communication' => 4,
            'professionalism' => 4,
            'teamwork' => 4,
            'punctuality' => 4,
            'initiative' => 4,
            'strengths' => 'Reliable and eager to learn.',
            'areas_for_improvement' => 'Ask more clarifying questions early.',
            'remarks' => 'Good week overall.',
            'recommendation' => 'Continue',
        ], $overrides);
    }
}
